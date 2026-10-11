<?php

namespace Tests\Unit;

use App\Jobs\SendTelegramAlertJob;
use App\Services\Alerts\TelegramAlertService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use PDOException;
use Tests\TestCase;

class TelegramInternalFailureAlertTest extends TestCase
{
    public function test_query_exception_keeps_actionable_database_message(): void
    {
        Bus::fake();
        config([
            'cache.default' => 'array',
            'queue.default' => 'database',
            'services.telegram_alert.token' => 'test-token',
            'services.telegram_alert.chat_id' => 'test-chat',
        ]);
        Cache::flush();

        $previous = new PDOException(
            "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'calendar_window_days'"
        );

        $exception = new QueryException(
            'mysql',
            'select `calendar_window_days` from `accommodation_provider_details`',
            [],
            $previous,
        );

        app(TelegramAlertService::class)->internalFailure($exception);

        Bus::assertDispatched(SendTelegramAlertJob::class, function (SendTelegramAlertJob $job): bool {
            $notification = $job->notification ?? [];
            $fields = is_array($notification['fields'] ?? null) ? $notification['fields'] : [];
            $details = (string) ($notification['details'] ?? '');

            return ($notification['message'] ?? null) === 'در اجرای Domestic Hotel خطا رخ داده است.'
                && str_contains((string) ($fields['علت'] ?? ''), 'calendar_window_days')
                && str_contains($details, 'SQLSTATE[42S22]')
                && str_contains($details, 'accommodation_provider_details')
                && !str_contains($details, 'intentionally hidden from Telegram')
                && !str_contains((string) ($notification['message'] ?? ''), 'خطای واقعی');
        });
    }
}
