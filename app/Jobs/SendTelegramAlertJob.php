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

        // This proxy request is never tagged as a provider; delivery cannot recurse.
        $send = static fn (array $data) => Http::asJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->post((string) ($config['url'] ?? ''), $data);

        $response = $send($payload);

        // If the company's published four-field schema rejects parse_mode,
        // still deliver a readable plain-text alert instead of losing it.
        if ($field !== '' && in_array($response->status(), [400, 422], true)) {
            unset($payload[$field]);
            $plain = str_replace(
                ['<pre>', '</pre>', '<br>', '<br/>', '<br />'],
                ["\n", "\n", "\n", "\n", "\n"],
                $this->message
            );
            $payload['message'] = html_entity_decode(
                strip_tags($plain),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            $response = $send($payload);
        }

        // The Laravel HTTP client does not throw on unsuccessful HTTP responses by default.
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
