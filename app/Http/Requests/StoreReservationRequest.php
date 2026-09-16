<?php

namespace App\Http\Requests;

use App\Support\Reservation\ReservationGuestGender;
use App\Support\Reservation\ReservationGuestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accommodationId = $this->input('hotel.accommodation_id');

        return [
            'agency_id' => ['required', 'integer', 'min:1'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'sale_amount' => ['required', 'integer', 'min:0'],
            'acc_code' => ['nullable', 'string', 'max:64'],

            'booker' => ['required', 'array'],
            'booker.first_name' => ['required', 'string', 'max:100'],
            'booker.last_name' => ['required', 'string', 'max:100'],
            'booker.mobile' => ['required', 'string', 'max:32'],
            'booker.email' => ['nullable', 'email', 'max:255'],

            'hotel' => ['required', 'array'],
            'hotel.accommodation_id' => ['required', 'integer', 'exists:accommodations,id'],
            'hotel.rooms' => ['required', 'array', 'min:1'],

            'hotel.rooms.*.room_type_id' => [
                'nullable',
                'integer',
                Rule::exists('room_types', 'id')->where(
                    fn ($query) => $query->where('accommodation_id', $accommodationId)
                ),
            ],
            'hotel.rooms.*.rate_plan_id' => [
                'nullable',
                'integer',
                Rule::exists('rate_plans', 'id')->where(
                    fn ($query) => $query->where('accommodation_id', $accommodationId)
                ),
            ],
            'hotel.rooms.*.room_name' => ['nullable', 'string', 'max:255'],
            'hotel.rooms.*.guests' => ['required', 'array', 'min:1'],

            'hotel.rooms.*.guests.*.type' => [
                'required',
                'integer',
                Rule::in(ReservationGuestType::all()),
            ],
            'hotel.rooms.*.guests.*.first_name' => ['required', 'string', 'max:100'],
            'hotel.rooms.*.guests.*.last_name' => ['required', 'string', 'max:100'],
            'hotel.rooms.*.guests.*.gender' => [
                'nullable',
                'integer',
                Rule::in(ReservationGuestGender::all()),
            ],
            'hotel.rooms.*.guests.*.birth_date' => ['nullable', 'date', 'before:today'],
            'hotel.rooms.*.guests.*.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'hotel.rooms.*.guests.*.national_id' => ['nullable', 'string', 'max:32'],
            'hotel.rooms.*.guests.*.passport_number' => ['nullable', 'string', 'max:64'],
            'hotel.rooms.*.guests.*.passport_issuer_country_id' => [
                'nullable',
                'required_with:hotel.rooms.*.guests.*.passport_number',
                'integer',
                'exists:countries,id',
            ],
            'hotel.rooms.*.guests.*.passport_expiry_date' => [
                'nullable',
                'required_with:hotel.rooms.*.guests.*.passport_number',
                'date',
                'after:today',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('hotel.rooms', []) as $index => $room) {
                if (empty($room['room_type_id']) && empty($room['room_name'])) {
                    $validator->errors()->add(
                        "hotel.rooms.{$index}.room_type_id",
                        'Either room_type_id or room_name is required.'
                    );
                }
            }
        });
    }
}
