<?php

namespace App\Services\Alerts;

use App\Jobs\SendTelegramAlertJob;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class TelegramAlertService
{
    public function __construct(private readonly TelegramAlertFormatter $formatter) {}

    public function providerFailure(
        string $provider,
        string $operation,
        string $kind,
        ?int $httpStatus = null,
        ?string $errorCode = null,
        ?string $reason = null,
    ): void {
        $description = match ($kind) {
            'connection' => 'ارتباط شبکه‌ای با تأمین‌کننده برقرار نشد یا درخواست Timeout شد.',
            'business' => 'تأمین‌کننده پاسخ کسب‌وکاری ناموفق برگرداند.',
            'invalid_response' => 'ساختار پاسخ تأمین‌کننده قابل پردازش نیست.',
            default => 'تأمین‌کننده پاسخ HTTP ناموفق برگرداند.',
        };

        $category = match ($kind) {
            'connection' => 'Network / provider connection',
            'business' => 'Provider business error',
            'invalid_response' => 'Provider response format',
            default => 'Provider HTTP error',
        };

        $fields = [
            'دسته' => $category,
            'تأمین‌کننده' => $provider,
            'عملیات' => $operation,
        ];
        if ($httpStatus !== null) {
            $fields['HTTP'] = $httpStatus;
        }
        if ($errorCode !== null && preg_match('/^[A-Za-z0-9_.:-]{1,48}$/D', $errorCode)) {
            $fields['کد خطا'] = $errorCode;
        }
        if ($reason !== null && trim($reason) !== '') {
            $fields['علت'] = $reason;
        }

        $technical = $httpStatus === null
            ? "Type: {$kind}"
            : "HTTP: {$httpStatus}\nType: {$kind}";

        $this->enqueue(
            'خطای تأمین‌کننده',
            $description,
            $fields,
            $technical,
            implode('|', [$provider, $operation, $kind, $httpStatus, $errorCode, $reason]),
        );
    }

    public function providerDataIssue(
        string $provider,
        string $operation,
        string $resourceId,
        string $reason,
    ): void {
        $this->enqueue(
            'داده ناقص تأمین‌کننده',
            'پاسخ تأمین‌کننده برای این هتل ناقص بود؛ هتل skip شد و پردازش بقیه ادامه پیدا کرد.',
            [
                'دسته' => 'Provider data',
                'تأمین‌کننده' => $provider,
                'عملیات' => $operation,
                'شناسه' => $resourceId,
                'علت' => $reason,
                'اقدام' => 'skip hotel / continue job',
            ],
            null,
            implode('|', ['provider-data', $provider, $operation, $resourceId, $reason]),
        );
    }

    public function internalFailure(Throwable $exception): void
    {
        $class = $exception::class;
        $location = basename($exception->getFile()) . ':' . $exception->getLine();
        $category = $exception instanceof QueryException
            ? 'Database'
            : ($exception instanceof \TypeError || $exception instanceof \Error
                ? 'Application code'
                : 'Application runtime');

        $message = $exception instanceof QueryException
            ? 'Database query failed; SQL and bindings are intentionally hidden from Telegram.'
            : $exception->getMessage();

        $this->enqueue(
            'خطای داخلی سرویس',
            'یک خطای واقعی در اجرای Domestic Hotel رخ داده است.',
            [
                'دسته' => $category,
                'نوع خطا' => $class,
                'علت' => $message,
                'محل' => $location,
            ],
            "Exception: {$class}\nMessage: {$message}\nLocation: {$location}",
            "internal|{$class}|{$location}|".$message,
        );
    }

    /**
     * Explicit extension point for sanitized application alerts. When the proxy
     * supports HTML parse mode the optional snippet renders as a code block.
     *
     * @param array<string, string|int> $fields
     */
    public function custom(
        string $title,
        string $description,
        array $fields = [],
        ?string $technicalSnippet = null,
    ): void {
        $this->enqueue(
            $title,
            $description,
            $fields,
            $technicalSnippet,
            'custom|' . sha1($title . '|' . $description . '|' . json_encode($fields)),
        );
    }

    /** @param array<string, string|int> $fields */
    private function enqueue(
        string $title,
        string $description,
        array $fields,
        ?string $snippet,
        string $fingerprint,
    ): void {
        $config = config('services.telegram_alert', []);
        if (empty($config['token']) || empty($config['chat_id'])) {
            return;
        }

        // A synchronous queue would make the customer wait for Telegram.
        if (config('queue.default') === 'sync') {
            error_log('Telegram alerts require an asynchronous queue; QUEUE_CONNECTION=sync is unsupported.');
            return;
        }

        $key = 'domestic_hotel:telegram:' . sha1($fingerprint);
        try {
            if (!Cache::add($key, true, 300)) {
                return;
            }
        } catch (Throwable $exception) {
            // Cache downtime should not silently disable reporting.
            error_log('Telegram deduplication unavailable: ' . $exception::class);
        }

        try {
            SendTelegramAlertJob::dispatch(
                $this->formatter->format($title, $description, $fields, $snippet)
            )->onQueue('default')->afterCommit();
        } catch (Throwable $exception) {
            // Alerting must never change the outcome of provider calls.
            error_log('Telegram alert enqueue unavailable: ' . $exception::class);
        }
    }
}
