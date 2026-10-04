<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Models\Provider;
use App\Models\ProviderCancellation;
use App\Models\ProviderCreditBalance;
use Illuminate\Support\Collection;

final class SnappTripOperationsRepository
{
    public function saveBalance(Provider $provider, int $balance): ProviderCreditBalance
    {
        return ProviderCreditBalance::query()->updateOrCreate(
            ['provider_id' => $provider->id],
            [
                'balance' => $balance,
                'synced_at' => now(),
            ],
        );
    }

    public function cancellation(Provider $provider, string $trackingCode): ?ProviderCancellation
    {
        return ProviderCancellation::query()
            ->where('provider_id', $provider->id)
            ->where('tracking_code', $trackingCode)
            ->first();
    }

    public function saveCancellationCreated(
        Provider $provider,
        string $trackingCode,
        array $payload,
        ?int $reservationPurchaseId = null,
    ): ProviderCancellation {
        return ProviderCancellation::query()->updateOrCreate(
            ['provider_id' => $provider->id, 'tracking_code' => $trackingCode],
            [
                'reservation_purchase_id' => $reservationPurchaseId,
                'provider_cancellation_id' => isset($payload['id']) ? (string) $payload['id'] : null,
                'status' => isset($payload['status']) ? strtoupper(trim((string) $payload['status'])) : null,
                'requested_at' => now(),
            ],
        );
    }

    public function saveCancellationInquiry(
        Provider $provider,
        string $trackingCode,
        array $payload,
    ): ProviderCancellation {
        $status = isset($payload['status']) ? strtoupper(trim((string) $payload['status'])) : null;
        $finalized = in_array($status, ['PAID', 'REJECTED', 'FAILED'], true) ? now() : null;

        $model = ProviderCancellation::query()->firstOrNew([
            'provider_id' => $provider->id,
            'tracking_code' => $trackingCode,
        ]);

        $model->fill([
            'status' => $status,
            'manual' => array_key_exists('manual', $payload) ? (bool) $payload['manual'] : null,
            'service_fee' => $payload['service_fee'] ?? null,
            'user_penalty' => $payload['user_penalty'] ?? null,
            'user_penalty_percent' => $payload['user_penalty_percent'] ?? null,
            'user_penalty_total' => $payload['user_penalty_total'] ?? null,
            'user_refund_amount' => $payload['user_refund_amount'] ?? null,
            'provider_rules' => is_array($payload['provider_rules'] ?? null) ? $payload['provider_rules'] : null,
            'last_inquired_at' => now(),
        ]);
        if ($finalized !== null && $model->finalized_at === null) {
            $model->finalized_at = $finalized;
        }
        $model->save();

        return $model;
    }

    public function markCancellationDecision(
        Provider $provider,
        string $trackingCode,
        string $status,
    ): ProviderCancellation {
        $model = ProviderCancellation::query()->firstOrNew([
            'provider_id' => $provider->id,
            'tracking_code' => $trackingCode,
        ]);
        $model->fill([
            'status' => strtoupper($status),
            'decided_at' => now(),
        ]);
        $model->save();

        return $model;
    }

    /** @return Collection<int,ProviderCancellation> */
    public function pendingCancellations(Provider $provider, int $limit = 100): Collection
    {
        return ProviderCancellation::query()
            ->where('provider_id', $provider->id)
            ->whereIn('status', ['REQUESTED', 'CALCULATED', 'ACCEPTED', 'DONE'])
            ->orderBy('last_inquired_at')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();
    }
}
