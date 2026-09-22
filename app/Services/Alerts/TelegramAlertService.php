<?php

namespace App\Services\Alerts;

use App\Jobs\SendTelegramAlertJob;
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
    ): void {
        $description = match ($kind) {
            'connection' => 'ارتباط با تأمین‌کننده برقرار نشد یا درخواست Timeout شد.',
            'business' => 'تأمین‌کننده پاسخ ناموفق برگرداند.',
            'invalid_response' => 'پاسخ تأمین‌کننده قابل پردازش نیست.',
            default => 'در ارتباط با تأمین‌کننده خطای HTTP رخ داد.',
        };

        $fields = [
            'تأمین‌کننده' => $provider,
            'عملیات' => $operation,
        ];
        if ($httpStatus !== null) {
            $fields['HTTP'] = $httpStatus;
        }
        if ($errorCode !== null && preg_match('/^[A-Za-z0-9_.:-]{1,48}$/D', $errorCode)) {
            $fields['کد خطا'] = $errorCode;
        }

        $technical = $httpStatus === null
            ? "Type: {$kind}"
            : "HTTP: {$httpStatus}\nType: {$kind}";

        $this->enqueue(
            'خطای تأمین‌کننده',
            $description,
            $fields,
            $technical,
            implode('|', [$provider, $operation, $kind, $httpStatus, $errorCode]),
        );
    }

    public function internalFailure(Throwable $exception): void
    {
        // Exception messages may contain SQL bindings, tokens and passenger data.
        $class = $exception::class;
        $location = basename($exception->getFile()) . ':' . $exception->getLine();
        $this->enqueue(
            'خطای داخلی سرویس',
            'یک خطای غیرمنتظره در Domestic Hotel ثبت شد؛ جزئیات کامل در لاگ سرور است.',
            ['نوع خطا' => $class, 'محل' => $location],
            "Exception: {$class}\nLocation: {$location}",
            "internal|{$class}|{$location}",
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
