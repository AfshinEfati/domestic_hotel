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

    /**
     * Provider-side throttling is temporary and should never be reported as an
     * application failure. The fingerprint intentionally ignores hotel context
     * so a burst of 429 responses produces one operational alert, not one per job.
     *
     * @param array<string, string|int> $context
     */
    public function providerRateLimited(
        string $provider,
        string $operation,
        ?int $retryAfterSeconds = null,
        ?string $reason = null,
        array $context = [],
    ): void {
        $fields = [
            'دسته' => 'Provider rate limit',
            'تأمین‌کننده' => $provider,
            'عملیات' => $operation,
            'HTTP' => 429,
        ];

        if ($retryAfterSeconds !== null && $retryAfterSeconds > 0) {
            $fields['تلاش مجدد پس از'] = $retryAfterSeconds.' ثانیه';
        }

        foreach ($context as $key => $value) {
            if (is_string($key) && (is_string($value) || is_int($value))) {
                $fields[$key] = $value;
            }
        }

        if ($reason !== null && trim($reason) !== '') {
            $fields['علت تأمین‌کننده'] = $reason;
        }

        $snippet = 'HTTP: 429';
        if ($retryAfterSeconds !== null && $retryAfterSeconds > 0) {
            $snippet .= "\nRetry after: {$retryAfterSeconds}s";
        }

        $this->enqueue(
            'محدودیت تعداد درخواست تأمین‌کننده',
            'تعداد درخواست‌ها از حد مجاز تأمین‌کننده عبور کرده است؛ درخواست با تأخیر دوباره ارسال می‌شود.',
            $fields,
            $snippet,
            implode('|', ['rate-limit', $provider, $operation]),
        );
    }

    public function providerAvailabilityIssue(
        string $provider,
        string $hotelName,
        int $accommodationId,
        string $providerPropertyId,
        string $from,
        string $to,
        int $requestedDays,
        int $receivedDays,
        string $issue,
        ?int $httpStatus = null,
        ?string $missingDates = null,
        ?string $reason = null,
    ): void {
        $description = match ($issue) {
            'not_found' => 'تأمین‌کننده هتل را برای نرخ و ظرفیت پیدا نکرد؛ Job بدون خطای سیستمی تمام شد.',
            'empty' => 'تأمین‌کننده برای کل بازه درخواست‌شده هیچ نرخ و ظرفیتی برنگرداند.',
            'partial' => 'تأمین‌کننده فقط بخشی از بازه درخواست‌شده را با نرخ و ظرفیت برگرداند.',
            default => 'پاسخ نرخ و ظرفیت تأمین‌کننده نیاز به بررسی دارد.',
        };

        $fields = [
            'دسته' => 'Provider availability data',
            'تأمین‌کننده' => $provider,
            'هتل' => $hotelName,
            'شناسه هتل' => $accommodationId,
            'شناسه هتل تأمین‌کننده' => $providerPropertyId,
            'بازه درخواست' => "{$from} تا قبل از {$to}",
            'روزهای درخواستی' => $requestedDays,
            'روزهای دریافتی' => $receivedDays,
        ];

        if ($requestedDays >= $receivedDays) {
            $fields['روزهای بدون داده'] = $requestedDays - $receivedDays;
        }

        if ($missingDates !== null && trim($missingDates) !== '') {
            $fields['تاریخ‌های بدون نرخ/ظرفیت'] = $missingDates;
        }

        if ($httpStatus !== null) {
            $fields['HTTP'] = $httpStatus;
        }

        if ($reason !== null && trim($reason) !== '') {
            $fields['علت تأمین‌کننده'] = $reason;
        }

        $this->enqueue(
            'هشدار نرخ و ظرفیت تأمین‌کننده',
            $description,
            $fields,
            $httpStatus === null ? null : "HTTP: {$httpStatus}\nIssue: {$issue}",
            implode('|', [
                'availability',
                $provider,
                $accommodationId,
                $providerPropertyId,
                $from,
                $to,
                $issue,
                $httpStatus,
                $receivedDays,
                $missingDates,
                $reason,
            ]),
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
            return;
        }

        $key = 'domestic_hotel:telegram:' . sha1($fingerprint);
        try {
            if (!Cache::add($key, true, 300)) {
                return;
            }
        } catch (Throwable) {
            // Cache downtime must not block best-effort alert delivery.
        }

        try {
            SendTelegramAlertJob::dispatch(
                $this->formatter->format($title, $description, $fields, $snippet)
            )->onQueue('default')->afterCommit();
        } catch (Throwable) {
            // Alerting must never change the outcome of provider calls.
        }
    }
}
