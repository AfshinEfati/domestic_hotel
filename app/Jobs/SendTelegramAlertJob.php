<?php

namespace App\Jobs;

use App\Exceptions\TelegramAlertDeliveryException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendTelegramAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(public readonly string $message) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $config = config('services.telegram_alert', []);
        if (empty($config['token']) || empty($config['chat_id'])) {
            return;
        }

        $payload = [
            'token' => $config['token'],
            'chatId' => $config['chat_id'],
            'message' => $this->message,
        ];

        // HTML styling requires the company proxy to forward this parameter.
        $field = (string) ($config['parse_mode_field'] ?? '');
        if ($field !== '') {
            $payload[$field] = 'HTML';
        }

        // The proxy is not tagged as a supplier: delivery failures cannot recurse.
        $response = Http::asJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->post((string) ($config['url'] ?? ''), $payload);

        // Laravel's HTTP client does not throw on 4xx/5xx by default.
        $response->throw();

        $body = $response->json();
        if (is_array($body) && (
            ($body['ok'] ?? null) === false
            || ($body['success'] ?? null) === false
        )) {
            throw new TelegramAlertDeliveryException('Company Telegram proxy rejected the message.');
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Telegram alert delivery failed.', [
            'exception_type' => $exception::class,
        ]);
    }
}
