<?php

namespace App\Http\Requests\Reservation;

use App\Models\Country;
use App\Support\Reservation\ReservationGuestGender;
use App\Support\Reservation\ReservationGuestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $countries = Country::query()
            ->select('id', 'iso3')
            ->get()
            ->keyBy('iso3');
        $rooms = $this->input('hotel.rooms', []);
        foreach ($rooms as $roomIndex => &$room) {

            foreach ($room['guests'] ?? [] as $guestIndex => &$guest) {

                $nationality = strtoupper(
                    trim($guest['nationality'] ?? '')
                );
                if ($nationality && isset($countries[$nationality])) {
                    $countryId = $countries[$nationality]->id;
                    $guest['country_id'] = $countryId;
                    $guest['passport_issuer_country_id'] = $countryId;
                }
            }
        }


        $this->merge([
            'hotel' => [
                'rooms' => $rooms
            ]
        ]);
    }


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency_id' => ['required', 'integer', 'min:1'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'hotel' => ['required', 'array'],
            'hotel.rooms' => ['required', 'array', 'min:1', 'max:5'],
            // The existing Availability calendar ID resolves the hotel, room, rate plan and provider.
            // A missing/pruned calendar must still produce a ticket, so do NOT use exists here.
            'hotel.rooms.*.room_calendar_id' => ['required', 'integer', 'min:1'],
            'hotel.rooms.*.price' => ['required', 'integer', 'min:0'],
            'hotel.rooms.*.guests' => ['required', 'array', 'min:1'],
            'hotel.rooms.*.guests.*.type' => ['required', 'integer', Rule::in(ReservationGuestType::all())],
            'hotel.rooms.*.guests.*.first_name' => ['required', 'string', 'max:100'],
            'hotel.rooms.*.guests.*.last_name' => ['required', 'string', 'max:100'],
            'hotel.rooms.*.guests.*.gender' => ['nullable', 'integer', Rule::in(ReservationGuestGender::all())],
            'hotel.rooms.*.guests.*.birth_date' => ['nullable', 'date', 'before:today'],
            // country_id is the guest's nationality, not the passport issuing country.
            'hotel.rooms.*.guests.*.country_id' => ['required', 'integer', 'exists:countries,id'],
            'hotel.rooms.*.guests.*.national_id' => ['nullable', 'string', 'digits:10'],
            'hotel.rooms.*.guests.*.passport_number' => ['nullable', 'string', 'max:64'],
            'hotel.rooms.*.guests.*.passport_issuer_country_id' => [
                'nullable', 'required_with:hotel.rooms.*.guests.*.passport_number', 'integer', 'exists:countries,id',
            ],
            'hotel.rooms.*.guests.*.passport_expiry_date' => [
                'nullable', 'required_with:hotel.rooms.*.guests.*.passport_number', 'date', 'after:today',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Check identity only after basic nationality and guest data validation.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->input('hotel.rooms', []) as $roomIndex => $room) {
                foreach ($room['guests'] as $guestIndex => $guest) {
                    $path = "hotel.rooms.{$roomIndex}.guests.{$guestIndex}";
                    $isIranian = (int)$guest['country_id'] === 1;
                    $field = $isIranian ? 'national_id' : 'passport_number';
                    $value = $guest[$field] ?? null;

                    if (!is_string($value) || trim($value) === '') {
                        $validator->errors()->add(
                            "{$path}.{$field}",
                            $isIranian
                                ? 'National ID is required for Iranian guests.'
                                : 'Passport number is required for non-Iranian guests.'
                        );
                    }
                }
            }
        });
    }
}
