<?php

namespace App\Http\Requests\Reservation;

use App\Models\Country;
use App\Support\Reservation\ReservationGuestGender;
use App\Support\Reservation\ReservationGuestType;
use Carbon\CarbonImmutable;
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
            ->keyBy(fn(Country $country): string => strtoupper($country->iso3));

        $rooms = $this->input('hotel.rooms', []);

        foreach ($rooms as &$room) {
            foreach ($room['guests'] ?? [] as &$guest) {
                $guest['country_id'] = $this->resolveCountryId($guest['country_code'] ?? null, $countries);

                if (array_key_exists('passport_issuer_country_code', $guest)) {
                    $guest['passport_issuer_country_id'] = $this->resolveCountryId(
                        $guest['passport_issuer_country_code'] ?? null,
                        $countries
                    );
                }
            }
        }

        $this->merge([
            'hotel' => [
                'accommodation_id' => $this->input('hotel.accommodation_id'),
                'rooms' => $rooms,
            ],
        ]);
    }

    private function resolveCountryId(?string $code, $countries): ?int
    {
        $code = strtoupper(trim((string)$code));

        return $code !== '' && isset($countries[$code]) ? $countries[$code]->id : null;
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

            // Sum of hotel.rooms.*.expected_total_price; checked in withValidator.
            'expected_total_price' => ['required', 'integer', 'min:0'],

            'hotel' => ['required', 'array'],
            // The accommodation is now sent explicitly by the caller and cross-checked
            // against every selected calendar's accommodation in ReservationCreateValidator.
            'hotel.accommodation_id' => ['required', 'integer', 'min:1', 'exists:accommodations,id'],
            'hotel.rooms' => ['required', 'array', 'min:1', 'max:5'],

            'hotel.rooms.*.room_number' => ['required', 'integer', 'min:1', 'distinct'],
            // Sum of this room's calendar.*.expected_price; checked in withValidator.
            'hotel.rooms.*.expected_total_price' => ['required', 'integer', 'min:0'],

            // One row per stay night. A missing/pruned calendar must still produce a ticket,
            // so do NOT use exists here — ReservationCreateValidator resolves it and, if it
            // no longer matches, marks the ticket NO_AVAILABILITY without failing the request.
            'hotel.rooms.*.calendar' => ['required', 'array', 'min:1'],
            'hotel.rooms.*.calendar.*.calendar_id' => ['required', 'integer', 'min:1'],
            'hotel.rooms.*.calendar.*.date' => ['required', 'date_format:Y-m-d'],
            'hotel.rooms.*.calendar.*.expected_price' => ['required', 'integer', 'min:0'],

            'hotel.rooms.*.guests' => ['required', 'array', 'min:1'],
            'hotel.rooms.*.guests.*.type' => ['required', 'integer', Rule::in(ReservationGuestType::all())],
            'hotel.rooms.*.guests.*.first_name' => ['required', 'string', 'max:100'],
            'hotel.rooms.*.guests.*.last_name' => ['required', 'string', 'max:100'],
            'hotel.rooms.*.guests.*.gender' => ['nullable', 'integer', Rule::in(ReservationGuestGender::all())],
            'hotel.rooms.*.guests.*.birth_date' => ['nullable', 'date', 'before:today'],

            // country_code is the guest's nationality (ISO3, e.g. "IRN"); resolved to
            // country_id in prepareForValidation. country_id itself is never accepted from the client.
            'hotel.rooms.*.guests.*.country_code' => ['required', 'string', 'size:3'],
            'hotel.rooms.*.guests.*.country_id' => ['required', 'integer', 'exists:countries,id'],

            'hotel.rooms.*.guests.*.national_id' => ['nullable', 'string', 'digits:10'],
            'hotel.rooms.*.guests.*.passport_number' => ['nullable', 'string', 'max:64'],
            // passport_issuer_country_code is the ISO3 of the country that issued the passport;
            // resolved to passport_issuer_country_id in prepareForValidation.
            'hotel.rooms.*.guests.*.passport_issuer_country_code' => ['nullable', 'string', 'size:3'],
            'hotel.rooms.*.guests.*.passport_issuer_country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'hotel.rooms.*.guests.*.passport_expiry_date' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateCalendarCoverage($validator);
            $this->validatePriceTotals($validator);
            $this->validateGuestIdentity($validator);
        });
    }

    /** Every room's calendar dates must exactly cover check_in..check_out-1, once each. */
    private function validateCalendarCoverage(Validator $validator): void
    {
        $checkIn = CarbonImmutable::createFromFormat('Y-m-d', (string)$this->input('check_in'));
        $checkOut = CarbonImmutable::createFromFormat('Y-m-d', (string)$this->input('check_out'));

        $expectedDates = [];
        for ($date = $checkIn; $date->lessThan($checkOut); $date = $date->addDay()) {
            $expectedDates[] = $date->toDateString();
        }
        $expectedDates = array_flip($expectedDates);

        foreach ($this->input('hotel.rooms', []) as $roomIndex => $room) {
            $seen = [];
            foreach ($room['calendar'] ?? [] as $nightIndex => $night) {
                $date = $night['date'] ?? null;
                $path = "hotel.rooms.{$roomIndex}.calendar.{$nightIndex}.date";

                if (!isset($expectedDates[$date])) {
                    $validator->errors()->add($path, 'Calendar date is outside the check_in/check_out range.');
                    continue;
                }
                if (isset($seen[$date])) {
                    $validator->errors()->add($path, 'Calendar date is duplicated for this room.');
                    continue;
                }
                $seen[$date] = true;
            }

            if (count($seen) !== count($expectedDates)) {
                $validator->errors()->add(
                    "hotel.rooms.{$roomIndex}.calendar",
                    'Calendar must include exactly one row for every night of the stay.'
                );
            }
        }
    }

    /** expected_total_price at every level must equal the sum of its parts. */
    private function validatePriceTotals(Validator $validator): void
    {
        $roomsTotal = 0;

        foreach ($this->input('hotel.rooms', []) as $roomIndex => $room) {
            $nightsTotal = array_sum(array_column($room['calendar'] ?? [], 'expected_price'));
            $roomTotal = (int)($room['expected_total_price'] ?? 0);

            if ($nightsTotal !== $roomTotal) {
                $validator->errors()->add(
                    "hotel.rooms.{$roomIndex}.expected_total_price",
                    'Room expected_total_price must equal the sum of its calendar.*.expected_price.'
                );
            }

            $roomsTotal += $roomTotal;
        }

        if ($roomsTotal !== (int)$this->input('expected_total_price', 0)) {
            $validator->errors()->add(
                'expected_total_price',
                'expected_total_price must equal the sum of hotel.rooms.*.expected_total_price.'
            );
        }
    }

    private function validateGuestIdentity(Validator $validator): void
    {
        foreach ($this->input('hotel.rooms', []) as $roomIndex => $room) {
            foreach ($room['guests'] as $guestIndex => $guest) {
                $path = "hotel.rooms.{$roomIndex}.guests.{$guestIndex}";
                $isIranian = (int)$guest['country_id'] === 1;

                if ($isIranian) {
                    if (!is_string($guest['national_id'] ?? null) || trim($guest['national_id']) === '') {
                        $validator->errors()->add("{$path}.national_id", 'National ID is required for Iranian guests.');
                    }
                    continue;
                }

                $required = [
                    'passport_number' => 'Passport number is required for non-Iranian guests.',
                    'passport_expiry_date' => 'Passport expiry date is required for non-Iranian guests.',
                    'passport_issuer_country_id' => 'Passport issuer country is required for non-Iranian guests.',
                ];
                foreach ($required as $field => $message) {
                    $value = $guest[$field] ?? null;
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        $validator->errors()->add("{$path}.{$field}", $message);
                    }
                }
            }
        }
    }
}
