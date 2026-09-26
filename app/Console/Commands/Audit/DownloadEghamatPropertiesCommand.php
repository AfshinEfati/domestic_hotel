<?php

namespace App\Console\Commands\Audit;

use App\Models\Provider;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DownloadEghamatPropertiesCommand extends Command
{
    protected $signature = 'audit:eghamat-properties';

    protected $description = 'Download Eghamat24 properties snapshot for audit';

    /**
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $provider = Provider::find(1);

        if (!$provider) {
            throw new RuntimeException('Eghamat24 provider with id 1 not found.');
        }

        /*
         * Adjust these fields based on current provider structure.
         * This is intentionally isolated because this command is temporary.
         */
        $config = $provider->config ?? [];

        $baseUrl = rtrim(
            $config['base_url'] ?? $config['url'] ?? '',
            '/'
        );
        $baseUrl = $baseUrl . '/v1';
        $this->info('Base url: ' . $baseUrl);


        if ($baseUrl === '') {
            throw new RuntimeException('Provider base URL not found.');
        }

        $this->info('Requesting Eghamat24 properties...');

        $request = Http::withAttributes([
            'domestic_provider' => [
                'id' => (int) $provider->id,
                'code' => (string) $provider->code,
                'log' => [
                    'enabled' => true,
                    'reservation_id' => null,
                    'handler_class' => self::class,
                    'handler_method' => __FUNCTION__,
                    'attempt' => 1,
                    'started_at' => now()->toISOString(),
                    'started_microtime' => microtime(true),
                ],
            ],
        ])->timeout(120);


        $response = $request->withHeaders([
            'Client-Token' => 'https://api.grschannel.com-$2y$10$/iQviVsfD1mKLS58OYdNve9',
            'Content-type' => 'application/json'
        ])->get(
            $baseUrl . '/properties',
            [
                'page' => 1,
                'count' => 10000000,
            ]
        );

        if (!$response->successful()) {
            $this->error(
                'Eghamat response failed: ' . $response->status()
            );

            return self::FAILURE;
        }

        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'provider_id' => 1,
            'status' => $response->status(),
            'data' => $response->json(),
        ];

        Storage::disk('local')->put(
            'audits/eghamat/properties-' . now()->format('Y-m-d-H-i-s') . '.json',
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );

        $this->info('Snapshot saved.');

        return self::SUCCESS;
    }
}
