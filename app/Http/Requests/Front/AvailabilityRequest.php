<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'state' => [
                'required',
                'string',
            ],

            'city' => [
                'required',
                'string',
            ],

            'check_in' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],

            'check_out' => [
                'required',
                'date_format:Y-m-d',
                'after:check_in',
            ],

            'rooms' => [
                'required',
                'array',
                'min:1',
                'max:5',
            ],

            'rooms.*.passengers' => [
                'required',
                'array',
                'min:1',
            ],

            'rooms.*.passengers.*.type' => [
                'required',
                'string',
                'in:adult,adl,child,chd,infant,inf',
            ],

            'rooms.*.passengers.*.age' => [
                'nullable',
                'integer',
                'min:0',
                'max:120',
            ],

            'rooms.*.passengers.*.title' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('rooms', []) as $roomIndex => $room) {
                if (!is_array($room)) {
                    continue;
                }

                foreach (
                    (array) ($room['passengers'] ?? [])
                    as $passengerIndex => $passenger
                ) {
                    if (!is_array($passenger)) {
                        continue;
                    }

                    $type = $passenger['type'] ?? null;

                    if (
                        !in_array(
                            $type,
                            ['child', 'chd', 'infant', 'inf'],
                            true
                        )
                    ) {
                        continue;
                    }

                    if (
                        !array_key_exists('age', $passenger)
                        || $passenger['age'] === null
                        || $passenger['age'] === ''
                    ) {
                        $validator->errors()->add(
                            "rooms.{$roomIndex}.passengers.{$passengerIndex}.age",
                            'Child and infant age is required.'
                        );
                    }
                }
            }
        });
    }

    public function authorize(): bool
    {
        return true;
    }
}
