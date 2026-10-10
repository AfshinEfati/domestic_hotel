<?php

namespace Tests\Unit;

use App\Jobs\SendTelegramAlertJob;
use App\Services\Alerts\TelegramAlertService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TelegramProviderAlertContextTest extends TestCase
{
    public function test_provider_failure_contains_provider_and_hotel_identifiers(): void
    {
        Bus::fake();
        config([
            'cache.default' => 'array',
            'queue.default' => 'database',
            'services.telegram_alert.token' => 'test-token',
            'services.telegram_alert.chat_id' => 'test-chat',
        ]);
        Cache::flush();

        app(TelegramAlertService::class)->providerFailure(
            'snap',
            'GET /availability/hotels/{id}/calendar',
            'http',
            400,
            null,
            'invalid range',
            [
                'شناسه تأمین‌کننده' => 4,
                'شناسه هتل' => 2076,
                'شناسه هتل تأمین‌کننده' => '3810',
                'بازه درخواست' => '2026-10-10 تا 2027-01-08',
            ],
        );

        Bus::assertDispatched(SendTelegramAlertJob::class, function (SendTelegramAlertJob $job): bool {
            $fields = $job->notification['fields'] ?? [];

            return ($fields['تأمین‌کننده'] ?? null) === 'snap'
                && ($fields['شناسه تأمین‌کننده'] ?? null) === 4
                && ($fields['شناسه هتل'] ?? null) === 2076
                && ($fields['شناسه هتل تأمین‌کننده'] ?? null) === '3810'
                && ($fields['بازه درخواست'] ?? null) === '2026-10-10 تا 2027-01-08'
                && ($fields['HTTP'] ?? null) === 400;
        });
    }
}
