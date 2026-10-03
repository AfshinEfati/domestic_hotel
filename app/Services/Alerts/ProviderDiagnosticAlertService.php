<?php

namespace App\Services\Alerts;

use App\Jobs\SendTelegramDiagnosticFileJob;
use Illuminate\Support\Facades\Cache;
use JsonException;
use Throwable;

final class ProviderDiagnosticAlertService
{
    public function __construct(private readonly TelegramAlertService $alerts)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $issues
     * @param array<string, mixed>|null $exchange
     */
    public function invalidGrsAvailability(
        string $hotelName,
        int $accommodationId,
        string $providerPropertyId,
        array $issues,
        ?array $exchange,
    ): void {
        $first = $issues[0] ?? [];
        $firstField = is_scalar($first['field'] ?? null)
            ? (string) $first['field']
            : 'unknown';
        $firstValue = $this->printable($first['value'] ?? null);

        $this->alerts->custom(
            'داده نامعتبر نرخ و ظرفیت GRS',
            'پاسخ تأمین‌کننده قبل از ذخیره در دیتابیس رد شد و هیچ بخشی از این پاسخ در room_calendars ذخیره نشد.',
            [
                'تأمین‌کننده' => 'grs',
                'هتل' => $hotelName,
                'شناسه هتل' => $accommodationId,
                'شناسه هتل تأمین‌کننده' => $providerPropertyId,
                'تعداد ایراد' => count($issues),
                'اولین فیلد نامعتبر' => $firstField,
                'اولین مقدار نامعتبر' => $firstValue,
            ],
            technicalSnippet: 'Provider payload rejected before persistence.',
            level: 'warning',
            tags: ['DomesticHotel', 'ProviderData', 'GRS', 'Availability'],
        );

        $this->queueDiagnosticFile(
            $hotelName,
            $accommodationId,
            $providerPropertyId,
            $issues,
            $exchange,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $issues
     * @param array<string, mixed>|null $exchange
     */
    private function queueDiagnosticFile(
        string $hotelName,
        int $accommodationId,
        string $providerPropertyId,
        array $issues,
        ?array $exchange,
    ): void {
        $config = config('services.telegram_alert', []);
        if (
            trim((string) ($config['file_url'] ?? '')) === ''
            || trim((string) ($config['token'] ?? '')) === ''
            || trim((string) ($config['chat_id'] ?? '')) === ''
            || config('queue.default') === 'sync'
        ) {
            return;
        }

        $fingerprint = sha1(json_encode([
            $accommodationId,
            $providerPropertyId,
            $issues,
        ], JSON_INVALID_UTF8_SUBSTITUTE));

        try {
            if (!Cache::add('domestic_hotel:telegram:diagnostic:'.$fingerprint, true, 300)) {
                return;
            }
        } catch (Throwable) {
            // Cache downtime must not prevent a best-effort diagnostic upload.
        }

        $document = [
            'generated_at' => now()->toISOString(),
            'source' => 'domestic_hotel',
            'provider' => 'grs',
            'context' => [
                'hotel' => $hotelName,
                'accommodation_id' => $accommodationId,
                'provider_property_id' => $providerPropertyId,
            ],
            'anomalies' => $issues,
            'http_exchange' => $exchange,
        ];

        try {
            $content = json_encode(
                $document,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return;
        }

        $safePropertyId = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $providerPropertyId) ?: 'unknown';
        $filename = sprintf(
            'grs-availability-anomaly-%d-%s-%s.json',
            $accommodationId,
            $safePropertyId,
            now()->format('Ymd-His')
        );

        $caption = sprintf(
            'GRS availability anomaly | hotel=%d | property=%s | issues=%d',
            $accommodationId,
            $providerPropertyId,
            count($issues)
        );

        try {
            SendTelegramDiagnosticFileJob::dispatch($filename, $content, $caption)
                ->onQueue('default')
                ->afterCommit();
        } catch (Throwable) {
            // Diagnostics are best-effort and must never change provider processing.
        }
    }

    private function printable(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return mb_substr((string) $value, 0, 120);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        return mb_substr($encoded === false ? get_debug_type($value) : $encoded, 0, 120);
    }
}
