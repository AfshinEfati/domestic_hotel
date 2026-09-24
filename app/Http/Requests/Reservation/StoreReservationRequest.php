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
            ->keyBy(
                fn (Country $country): string => strtoupper($country->iso3)
            );
        // مرجع محاسبه سن = تاریخ ثبت رزرو؛ سن ارسالی کاربر قابل اعتماد نیست.
        $reservationDate = CarbonImmutable::now('Asia/Tehran')->startOfDay();

        $rooms = $this->input('hotel.rooms', []);

        foreach ($rooms as $roomIndex => $room) {

            if (!isset($room['guests']) || !is_array($room['guests'])) {
                continue;
            }

            foreach ($room['guests'] as $guestIndex => $guest) {

                $guest['country_code'] = strtoupper(
                    trim($guest['country_code'] ?? '')
                );

                $guest['country_id'] = $this->resolveCountryId(
                    $guest['country_code'],
                    $countries
                );

                if (array_key_exists('passport_issuer_country_code', $guest)) {

                    $guest['passport_issuer_country_code'] = strtoupper(
                        trim($guest['passport_issuer_country_code'] ?? '')
                    );

                    $guest['passport_issuer_country_id'] = $this->resolveCountryId(
                        $guest['passport_issuer_country_code'],
                        $countries
                    );
                }

                // محاسبه سن نسبت به تاریخ ورود
                $guest['age'] = $this->calculateAge(
                    $guest['birthday'] ?? null,
                    $reservationDate
                );

                // نوشتن مقدار اصلاح‌شده به آرایه‌ی اصلی
                $rooms[$roomIndex]['guests'][$guestIndex] = $guest;
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
        $code = strtoupper(trim((string) $code));

        return $code !== '' && isset($countries[$code])
            ? $countries[$code]->id
            : null;
    }


    private function parseDate(?string $value): ?CarbonImmutable
    {
        if (!$value) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof CarbonImmutable ? $date : null;
    }


    private function calculateAge(
        ?string $birthDate,
        ?CarbonImmutable $reference
    ): ?int {

        if (!$birthDate || !$reference) {
            return null;
        }

        $birth = CarbonImmutable::createFromFormat('Y-m-d', $birthDate);

        if (!$birth instanceof CarbonImmutable) {
            return null;
        }
        $age = $birth->diffInYears($reference);
        return max(0, (int) $age);
    }


    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'agency_id' => [
                'required',
                'integer',
                'min:1'
            ],

            'check_in' => [
                'required',
                'date_format:Y-m-d'
            ],

            'check_out' => [
                'required',
                'date_format:Y-m-d',
                'after:check_in'
            ],

            'first_name' => [
                'required',
                'string',
                'max:100'
            ],

            'last_name' => [
                'required',
                'string',
                'max:100'
            ],

            'mobile' => [
                'required',
                'string',
                'max:32'
            ],

            'email' => [
                'nullable',
                'email',
                'max:255'
            ],

            'expected_total_price' => [
                'required',
                'integer',
                'min:0'
            ],


            'hotel' => [
                'required',
                'array'
            ],

            'hotel.accommodation_id' => [
                'required',
                'integer',
                'min:1',
                'exists:accommodations,id'
            ],

            'hotel.rooms' => [
                'required',
                'array',
                'min:1',
                'max:5'
            ],


            'hotel.rooms.*.room_number' => [
                'required',
                'integer',
                'min:1',
                'distinct'
            ],


            'hotel.rooms.*.expected_total_price' => [
                'required',
                'integer',
                'min:0'
            ],


            'hotel.rooms.*.calendar' => [
                'required',
                'array',
                'min:1'
            ],

            'hotel.rooms.*.calendar.*.calendar_id' => [
                'required',
                'integer',
                'min:1'
            ],

            'hotel.rooms.*.calendar.*.date' => [
                'required',
                'date_format:Y-m-d'
            ],

            'hotel.rooms.*.calendar.*.expected_price' => [
                'required',
                'integer',
                'min:0'
            ],


            'hotel.rooms.*.guests' => [
                'required',
                'array',
                'min:1'
            ],

            'hotel.rooms.*.guests.*.type' => [
                'required',
                'integer',
                Rule::in(ReservationGuestType::all())
            ],

            'hotel.rooms.*.guests.*.first_name' => [
                'required',
                'string',
                'max:100'
            ],

            'hotel.rooms.*.guests.*.last_name' => [
                'required',
                'string',
                'max:100'
            ],

            'hotel.rooms.*.guests.*.gender' => [
                'nullable',
                'integer',
                Rule::in(ReservationGuestGender::all())
            ],

            'hotel.rooms.*.guests.*.birthday' => [
                'required',
                'date',
                'before:today'
            ],

            // سن به صورت خودکار از birthday و check_in محاسبه می‌شود
            'hotel.rooms.*.guests.*.age' => [
                'nullable',
                'integer',
                'min:0',
                'max:120'
            ],

            'hotel.rooms.*.guests.*.country_code' => [
                'required',
                'string',
                'size:3',
                Rule::exists('countries', 'iso3')
            ],

            'hotel.rooms.*.guests.*.country_id' => [
                'required',
                'integer',
                'exists:countries,id'
            ],


            'hotel.rooms.*.guests.*.national_id' => [
                'nullable',
                'string',
                'digits:10'
            ],

            'hotel.rooms.*.guests.*.passport_number' => [
                'nullable',
                'string',
                'max:64'
            ],


            'hotel.rooms.*.guests.*.passport_issuer_country_code' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists('countries', 'iso3')
            ],

            'hotel.rooms.*.guests.*.passport_issuer_country_id' => [
                'nullable',
                'integer',
                'exists:countries,id'
            ],

            'hotel.rooms.*.guests.*.passport_expiry_date' => [
                'nullable',
                'date',
                'after:today'
            ],
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


    private function validateCalendarCoverage(Validator $validator): void
    {
        $checkIn = CarbonImmutable::createFromFormat(
            'Y-m-d',
            (string) $this->input('check_in')
        );

        $checkOut = CarbonImmutable::createFromFormat(
            'Y-m-d',
            (string) $this->input('check_out')
        );


        $expectedDates = [];

        for (
            $date = $checkIn;
            $date->lessThan($checkOut);
            $date = $date->addDay()
        ) {
            $expectedDates[] = $date->toDateString();
        }

        $expectedDates = array_flip($expectedDates);


        foreach ($this->input('hotel.rooms', []) as $roomIndex => $room) {

            $seen = [];

            foreach ($room['calendar'] ?? [] as $nightIndex => $night) {

                $date = $night['date'] ?? null;

                $path =
                    "hotel.rooms.{$roomIndex}.calendar.{$nightIndex}.date";


                if (!isset($expectedDates[$date])) {

                    $validator->errors()->add(
                        $path,
                        'Calendar date is outside the stay range.'
                    );

                    continue;
                }


                if (isset($seen[$date])) {

                    $validator->errors()->add(
                        $path,
                        'Calendar date is duplicated.'
                    );

                    continue;
                }


                $seen[$date] = true;
            }


            if (count($seen) !== count($expectedDates)) {

                $validator->errors()->add(
                    "hotel.rooms.{$roomIndex}.calendar",
                    'Calendar must contain one row for every night.'
                );
            }
        }
    }


    private function validatePriceTotals(Validator $validator): void
    {
        $roomsTotal = 0;


        foreach ($this->input('hotel.rooms', []) as $roomIndex => $room) {

            $calendarTotal = array_sum(
                array_column(
                    $room['calendar'] ?? [],
                    'expected_price'
                )
            );


            $roomTotal = (int) ($room['expected_total_price'] ?? 0);


            if ($calendarTotal !== $roomTotal) {

                $validator->errors()->add(
                    "hotel.rooms.{$roomIndex}.expected_total_price",
                    'Room total does not match calendar prices.'
                );
            }


            $roomsTotal += $roomTotal;
        }


        if (
            $roomsTotal !==
            (int) $this->input('expected_total_price', 0)
        ) {

            $validator->errors()->add(
                'expected_total_price',
                'Reservation total does not match rooms total.'
            );
        }
    }


    private function validateGuestIdentity(Validator $validator): void
    {
        foreach ($this->input('hotel.rooms', []) as $roomIndex => $room) {

            foreach ($room['guests'] ?? [] as $guestIndex => $guest) {

                $path =
                    "hotel.rooms.{$roomIndex}.guests.{$guestIndex}";


                if (empty($guest['country_id'])) {

                    $validator->errors()->add(
                        "{$path}.country_code",
                        'Invalid country code.'
                    );

                    continue;
                }


                $isIranian =
                    strtoupper($guest['country_code']) === 'IRN';


                if ($isIranian) {

                    if (empty($guest['national_id'])) {

                        $validator->errors()->add(
                            "{$path}.national_id",
                            'National ID is required for Iranian guests.'
                        );
                    }

                    continue;
                }


                foreach (
                    [
                        'passport_number' =>
                            'Passport number is required for foreign guests.',

                        'passport_expiry_date' =>
                            'Passport expiry date is required for foreign guests.',

                        'passport_issuer_country_id' =>
                            'Passport issuer country is required for foreign guests.',
                    ]
                    as $field => $message
                ) {

                    if (empty($guest[$field])) {

                        $validator->errors()->add(
                            "{$path}.{$field}",
                            $message
                        );
                    }
                }
            }
        }
    }
}
