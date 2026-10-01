<?php

namespace App\Jobs;

use App\Exceptions\TelegramAlertDeliveryException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

final class SendTelegramAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 25;

    /** @var array<string, mixed>|null */
    public ?array $notification = null;

    /**
     * Kept for jobs that were serialized before the rich-message migration.
     * New jobs use $notification only.
     */
    public ?string $message = null;

    /** @param array<string, mixed>|string $notification */
    public function __construct(array|string $notification)
    {
        if (is_array($notification)) {
            $this->notification = $notification;
            return;
        }

        $this->message = $notification;
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $config = config('services.telegram_alert', []);
        $url = trim((string) ($config['url'] ?? ''));
        $token = trim((string) ($config['token'] ?? ''));
        $chatId = trim((string) ($config['chat_id'] ?? ''));

        if ($url === '' || $token === '' || $chatId === '') {
            return;
        }

        $notification = $this->notification;

        if ($notification === null && $this->message !== null) {
            $notification = [
                'level' => 'error',
                'source' => 'domestic_hotel',
                'title' => 'هشدار سرویس هتل',
                'message' => $this->message,
                'fields' => [],
                'tags' => ['DomesticHotel', 'LegacyAlert'],
                'actions' => [
                    [
                        'text' => 'مشاهده Horizon',
                        'url' => (string) ($config['horizon_url']
                            ?? 'https://newhotel.shahansafar.ir/horizon/dashboard'),
                        'style' => 'primary',
                    ],
                ],
                'silent' => false,
                'rtl' => true,
            ];
        }

        if ($notification === null) {
            return;
        }

        $payload = array_merge(
            $notification,
            [
                'token' => $token,
                'chat_id' => $chatId,
            ]
        );

        try {
            // The gateway URL never contains the Telegram bot token.
            $response = Http::asJson()
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(12)
                ->post($url, $payload);
        } catch (ConnectionException) {
            throw new TelegramAlertDeliveryException(
                'Unable to connect to the company Telegram rich-message gateway.'
            );
        } catch (Throwable $e) {
            throw new TelegramAlertDeliveryException(
                'Telegram rich-message delivery failed before receiving a gateway response.',
                previous: $e
            );
        }

        $body = $response->json();

        if (!$response->successful()) {
            throw new TelegramAlertDeliveryException(
                'Company Telegram rich-message gateway returned HTTP '.$response->status().'.'
            );
        }

        if (
            is_array($body)
            && (
                ($body['success'] ?? null) === false
                || ($body['ok'] ?? null) === false
            )
        ) {
            throw new TelegramAlertDeliveryException(
                'Company Telegram rich-message gateway rejected the alert.'
            );
        }
    }
}
