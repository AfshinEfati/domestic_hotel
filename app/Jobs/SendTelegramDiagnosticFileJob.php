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

final class SendTelegramDiagnosticFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $filename,
        public string $content,
        public string $caption,
    ) {
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $config = config('services.telegram_alert', []);
        $url = trim((string) ($config['file_url'] ?? ''));
        $token = trim((string) ($config['token'] ?? ''));
        $chatId = trim((string) ($config['chat_id'] ?? ''));

        if ($url === '' || $token === '' || $chatId === '') {
            return;
        }

        try {
            // File delivery still goes through the company Telegram gateway.
            // The bot token is a multipart field and never appears in the URL.
            $response = Http::attach(
                'document',
                $this->content,
                $this->filename,
                ['Content-Type' => 'application/json']
            )
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(20)
                ->post($url, [
                    'token' => $token,
                    'chat_id' => $chatId,
                    'caption' => $this->caption,
                ]);
        } catch (ConnectionException) {
            throw new TelegramAlertDeliveryException(
                'Unable to connect to the company Telegram diagnostic-file gateway.'
            );
        } catch (Throwable $e) {
            throw new TelegramAlertDeliveryException(
                'Telegram diagnostic-file delivery failed before receiving a gateway response.',
                previous: $e
            );
        }

        $body = $response->json();

        if (!$response->successful()) {
            throw new TelegramAlertDeliveryException(
                'Company Telegram diagnostic-file gateway returned HTTP '.$response->status().'.'
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
                'Company Telegram diagnostic-file gateway rejected the document.'
            );
        }
    }
}
