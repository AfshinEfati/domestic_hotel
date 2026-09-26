<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use App\Support\Reservation\PaymentSource;
use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationPurchaseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reservation_hotel_id' => $this->reservation_hotel_id,
            'provider' => [
                'id' => $this->provider_id,
                'code' => $this->provider?->code,
                'name' => $this->provider?->name,
            ],
            'quoted_provider' => [
                'id' => $this->quoted_provider_id,
                'code' => $this->quotedProvider?->code,
                'name' => $this->quotedProvider?->name,
            ],
            'status' => (int) $this->status,
            'status_detail' => ReservationStatus::get((int) $this->status),
            'purchase_mode' => PurchaseMethod::options()[(int) $this->purchase_mode] ?? null,
            'manual_reason' => $this->manual_reason === null
                ? null
                : PurchaseManualReason::get((int) $this->manual_reason),
            'manual_rule_id' => $this->manual_rule_id,
            'provider_quoted_amount' => $this->provider_quoted_amount,
            'purchase_amount' => $this->purchase_amount,
            'confirmation_code' => $this->confirmation_code,
            'provider_status' => $this->provider_status,
            'expires_at' => StatusHelper::formatDates($this->expires_at),
            'issued_at' => StatusHelper::formatDates($this->issued_at),

            'manual_purchase' => $this->relationLoaded('manualPurchase') && $this->manualPurchase !== null
                ? [
                    'id' => $this->manualPurchase->id,
                    'acc_code' => $this->manualPurchase->acc_code,
                    'purchased_at' => StatusHelper::formatDates($this->manualPurchase->purchased_at),
                    'description' => $this->manualPurchase->description,
                    'created_at' => StatusHelper::formatDates($this->manualPurchase->created_at),
                    'updated_at' => StatusHelper::formatDates($this->manualPurchase->updated_at),
                ]
                : null,

            'segments' => $this->relationLoaded('segments')
                ? $this->segments->map(fn ($segment) => [
                    'id' => $segment->id,
                    'reservation_room_id' => $segment->reservation_room_id,
                    'from_date' => $segment->from_date?->format('Y-m-d'),
                    'to_date' => $segment->to_date?->format('Y-m-d'),
                    'nightly_purchase_amount' => $segment->nightly_purchase_amount,
                    'nightly_extra_bed_purchase_amount' => $segment->nightly_extra_bed_purchase_amount,
                    'nightly_child_purchase_amount' => $segment->nightly_child_purchase_amount,
                    'nightly_infant_purchase_amount' => $segment->nightly_infant_purchase_amount,
                ])->values()
                : [],

            'payments' => $this->relationLoaded('payments')
                ? $this->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'source' => PaymentSource::get((int) $payment->source),
                    'bank_account_id' => $payment->bank_account_id,
                    'card_id' => $payment->card_id,
                    'paid_at' => StatusHelper::formatDates($payment->paid_at),
                    'reference' => $payment->reference,
                    'receipt_document_id' => $payment->receipt_document_id,
                    'status' => $payment->status,
                    'description' => $payment->description,
                    'created_at' => StatusHelper::formatDates($payment->created_at),
                    'updated_at' => StatusHelper::formatDates($payment->updated_at),
                ])->values()
                : [],

            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
        ];
    }
}
