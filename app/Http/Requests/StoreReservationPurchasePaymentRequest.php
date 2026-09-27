<?php

namespace App\Http\Requests;

use App\Support\Reservation\PaymentSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationPurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acc_code' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1'],
            'source' => [
                'required',
                'integer',
                Rule::in(PaymentSource::manualPurchaseSources()),
            ],
            'paid_at' => ['required', 'date'],
        ];
    }
}
