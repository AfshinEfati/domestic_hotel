<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AvailabilityFilterService
{
    /**
     * فیلتر کردن هتل‌ها و اتاق‌های موجود
     * بر اساس شرایط رزرو: شهر، تاریخ، تعداد و نوع مسافران
     *
     * @param array $data
     *   - state: string (نام استان)
     *   - city: string (نام شهر)
     *   - check_in: string (Y-m-d)
     *   - check_out: string (Y-m-d)
     *   - rooms: array (آرایه‌ای از اتاق‌ها با مسافران)
     * @return Collection
     */
    public function filterAvailability(array $data): Collection
    {
        $checkIn = Carbon::createFromFormat('Y-m-d', $data['check_in']);
        $checkOut = Carbon::createFromFormat('Y-m-d', $data['check_out']);
        $city = $data['city'];
        $rooms = $data['rooms'];

        // گام 1: دریافت هتل‌های موجود در شهر
        // شهر ممکن است انگلیسی یا فارسی باشد، بنابراین جستجو به صورت case-insensitive انجام می‌دهیم
        $accommodations = Accommodation::query()
            ->whereHas('city', function (Builder $query) use ($city) {
                $query->where('en_name', 'like', '%' . strtolower($city) . '%')
                    ->orWhere('fa_name', 'like', '%' . $city . '%');
            })
            ->with(['childPolicy', 'rooms', 'rules'])
            ->where('is_active', true)
            ->get();

        $availableAccommodations = collect();

        foreach ($accommodations as $accommodation) {
            $availableRooms = $this->filterRoomsForAccommodation(
                $accommodation,
                $rooms,
                $checkIn,
                $checkOut
            );

            if ($availableRooms->isNotEmpty()) {
                $accommodation->setAttribute('available_rooms', $availableRooms);
                $availableAccommodations->push($accommodation);
            }
        }
        return $availableAccommodations;
    }

    /**
     * فیلتر کردن اتاق‌های یک هتل مشخص
     *
     * @param Accommodation $accommodation
     * @param array $requestedRooms
     * @param Carbon $checkIn
     * @param Carbon $checkOut
     * @return Collection
     */
    private function filterRoomsForAccommodation(
        Accommodation $accommodation,
        array         $requestedRooms,
        Carbon        $checkIn,
        Carbon        $checkOut
    ): Collection
    {
        $availableRooms = collect();
        $childPolicy = $accommodation->childPolicy;

        foreach ($requestedRooms as $requestedRoom) {
            $passengers = $requestedRoom['passengers'] ?? [];

            // نرمال‌سازی نوع مسافران
            $passengers = $this->normalizePassengerTypes($passengers, $childPolicy);
//            dd($passengers);
            // بررسی صحت و سالمت مسافران برای این هتل
            if (!$this->isValidPassengersForAccommodation($passengers, $childPolicy)) {
                continue;
            }

            // تقسیم مسافران بر اساس نوع
            $adultCount = collect($passengers)->where('type', 'adult')->count();
            $childCount = collect($passengers)->where('type', 'child')->count();
            $infantCount = collect($passengers)->where('type', 'infant')->count();

            // جستجوی اتاق‌های مناسب
            $suitableRoom = $this->findSuitableRoom(
                $accommodation,
                $adultCount,
                $childCount,
                $infantCount,
                $checkIn,
                $checkOut,
                $childPolicy
            );

            if ($suitableRoom) {
                $availableRooms->push($suitableRoom);
            }
        }

        return $availableRooms;
    }

    /**
     * نرمال‌سازی نوع مسافران
     * تبدیل ابخصار‌ها به نام کامل
     * adl -> adult, chd -> child, inf -> infant
     */
    private function normalizePassengerTypes(array $passengers, $childPolicy): array
    {
        return collect($passengers)->map(function ($passenger) use ($childPolicy) {
            $typeMap = [
                'adl' => 'adult',
                'adult' => 'adult',
                'chd' => 'child',
                'child' => 'child',
                'inf' => 'infant',
                'infant' => 'infant',
            ];
            $passenger['type'] = $typeMap[$passenger['type']] ?? 'adult';
            if (!$childPolicy) {
                $passenger['type'] = 'adult';
            } elseif ($childPolicy->max_infant_age >= $passenger['age']) {
                $passenger['type'] = 'infant';
            } elseif ($childPolicy->max_child_age <= $passenger['age']) {
                $passenger['type'] = 'adult';
            }
            return $passenger;
        })->toArray();
    }

    /**
     * بررسی اینکه آیا مسافران برای سیاست کودک/نوزاد هتل مناسب هستند
     *
     * @param array $passengers
     * @param mixed $childPolicy
     * @return bool
     */
    private function isValidPassengersForAccommodation(array $passengers, mixed $childPolicy): bool
    {
        if (!$childPolicy) {
            // اگر هتل سیاست کودک/نوزاد تعریف نکرده، فقط بزرگسالان را قبول کن
            return collect($passengers)->every(function ($p) {
                return $p['type'] === 'adult';
            });
        }

        $childCount = 0;
        $infantCount = 0;

        foreach ($passengers as $passenger) {
            if ($passenger['type'] === 'child') {
                $age = $passenger['age'] ?? 0;
                // بررسی سن کودک با سیاست
                if ($age > $childPolicy->max_child_age) {
                    return false;
                }
                $childCount++;
            } elseif ($passenger['type'] === 'infant') {
                $age = $passenger['age'] ?? 0;
                // بررسی سن نوزاد با سیاست
                if ($age > $childPolicy->max_infant_age) {
                    return false;
                }
                $infantCount++;
            }
        }

        // بررسی حداکثر تعداد کودکان و نوزادان
        if ($childCount > ($childPolicy->max_children_covered ?? PHP_INT_MAX)) {
            return false;
        }

        if ($infantCount > ($childPolicy->max_infants_covered ?? PHP_INT_MAX)) {
            return false;
        }

        return true;
    }

    /**
     * جستجوی اتاق مناسب برای مسافران
     *
     * @param Accommodation $accommodation
     * @param int $adultCount
     * @param int $childCount
     * @param int $infantCount
     * @param Carbon $checkIn
     * @param Carbon $checkOut
     * @return RoomType|Model|null
     */
    private function findSuitableRoom(
        Accommodation         $accommodation,
        int                   $adultCount,
        int                   $childCount,
        int                   $infantCount,
        Carbon                $checkIn,
        Carbon                $checkOut,
        HotelChildPolicy|null $childPolicy
    ): RoomType|null|Model
    {
        $totalPeople = $adultCount + $childCount;

        $roomType = $accommodation->rooms()
            ->where('out_of_service', false)
            ->orderBy('capacity', 'asc')
            ->get()
            ->first(function (RoomType $room) use ($totalPeople) {
                // بررسی اینکه آیا اتاق می‌تواند مسافران را تحمل کند
                return $room->capacity >= $totalPeople ||
                    ($room->capacity + $room->extra_capacity) >= $totalPeople;
            });

        if (!$roomType) {
            return null;
        }

        // بررسی موجودی و دریافت قیمت‌ها برای تمام روزهای سفر
        $priceData = $this->getRoomPricesForDateRange($roomType, $checkIn, $checkOut, $childCount, $infantCount, $childPolicy);

        if (!$priceData) {
            return null;
        }

        // اضافه کردن اطلاعات قیمت به اتاق
        $roomType->setAttribute('pricing', $priceData['pricing']);
        $roomType->setAttribute('total_price', $priceData['total_price']);
        $roomType->setAttribute('nightly_prices', $priceData['nightly_prices']);
        $roomType->setAttribute('ratePlan', $priceData['ratePlan']);

        return $roomType;
    }

    /**
     * دریافت قیمت‌ها برای محدوده تاریخی
     * محاسبه‌ی قیمت برای بزرگسالان و کودکان/نوزادان
     *
     * @param RoomType $roomType
     * @param Carbon $checkIn
     * @param Carbon $checkOut
     * @param int $childCount
     * @param int $infantCount
     * @return array|null
     */
    private function getRoomPricesForDateRange(
        RoomType              $roomType,
        Carbon                $checkIn,
        Carbon                $checkOut,
        int                   $childCount,
        int                   $infantCount,
        HotelChildPolicy|null $childPolicy
    ): ?array
    {
        $currentDate = $checkIn->copy();
        $nightly_prices = [];
        $totalAdultPrice = 0;
        $totalChildPrice = 0;
        $totalInfantPrice = 0;
        $totalExtraPrice = 0;
        $ratePlan = null;
        $extraPrice = 0;


        while ($currentDate < $checkOut) {
            $calendar = RoomCalendar::query()->with(['roomType', 'ratePlan'])->where('room_type_id', $roomType->id)
                ->where('day', $currentDate->toDateString())
                ->first();
            // اگر روز بسته است یا موجودی کافی نیست
            if (!$calendar || $calendar->closed || $calendar->inventory <= 0) {
                return null;
            }
            if (!$ratePlan)
                $ratePlan = $calendar->ratePlan;
            // محاسبه نرخ هر نفر
            $adlPrice = $calendar->daily_rate / $calendar->roomType->capacity;
            // محاسبه نرخ تخت اضافه . در صورتی که قیمت مجزا ثبت نشده باشه به قیمت یک نفر محاسبه میشه
            if ($roomType->extra_capacity)
                $extraPrice = $calendar->extend_bed_daily_rate ?? $adlPrice;

            $nightlyPrice = [
                'date' => $currentDate->toDateString(),
                'extra_price' => $extraPrice,
                'adult' => [
                    'rack_rate' => $calendar->rack_rate,
                    'daily_rate' => $calendar->daily_rate,
                    'grs_rate' => $calendar->grs_rate,
                    'adult_price' => $adlPrice,
                ],
            ];

            // اگر کودک یا نوزاد داریم، قیمت مختص آن‌ها را اضافه کنیم
//            if ($childCount > 0) {
            $nightlyPrice['child'] = [
                'rack_rate' => $calendar->baby_cot_rack_rate,
                'daily_rate' => $calendar->baby_cot_daily_rate,
                'grs_rate' => $calendar->baby_cot_grs_rate,
            ];
//            }

//            if ($infantCount > 0) {
            $nightlyPrice['infant'] = [
                'rack_rate' => $calendar->baby_cot_rack_rate,
                'daily_rate' => $calendar->baby_cot_daily_rate,
                'grs_rate' => $calendar->baby_cot_grs_rate,
            ];
//            }
            // اگر هتل نرخ کودک نداشت و قوانین هتل هم نداشت
            if (!$childPolicy && !$calendar->baby_cot_daily_rate) {
                $nightlyPrice['child'] = [
                    'rack_rate' => $calendar->rack_rate,
                    'daily_rate' => $calendar->daily_rate,
                    'grs_rate' => $calendar->grs_rate,
                    'child_price' => $adlPrice,
                ];
                $nightlyPrice['infant'] = [
                    'rack_rate' => $calendar->baby_cot_rack_rate,
                    'daily_rate' => $calendar->baby_cot_daily_rate,
                    'grs_rate' => $calendar->baby_cot_grs_rate,
                    'infant_price' => $adlPrice,
                ];
            }
            if (!$calendar->baby_cot_daily_rate && $childPolicy) {
                $childPrice = 0;
                $infantPrice = 0;
                if ($childPolicy->child_pricing_type === 'adult')
                    $childPrice = $adlPrice;
                if ($childPolicy->child_pricing_type === 'half')
                    $childPrice = $adlPrice / 2;
                if ($childPolicy->child_pricing_type === 'percent')
                    $childPrice = ($adlPrice / 100) * $childPolicy->child_pricing_value;
                if ($childPolicy->child_pricing_type === 'fixed')
                    $childPrice = $childPolicy->child_pricing_value;

                if ($childPolicy->infant_pricing_type === 'adult')
                    $infantPrice = $adlPrice;
                if ($childPolicy->infant_pricing_type === 'half')
                    $infantPrice = $adlPrice / 2;
                if ($childPolicy->infant_pricing_type === 'percent')
                    $infantPrice = ($adlPrice / 100) * $childPolicy->infant_pricing_value;
                if ($childPolicy->infant_pricing_type === 'fixed')
                    $infantPrice = $childPolicy->child_pricing_value;
                $nightlyPrice['child'] = [
                    'rack_rate' => $childPrice,
                    'daily_rate' => $childPrice,
                    'grs_rate' => $childPrice,
                    'child_price' => $childPrice,
                ];
                $nightlyPrice['infant'] = [
                    'rack_rate' => $infantPrice,
                    'daily_rate' => $infantPrice,
                    'grs_rate' => $infantPrice,
                    'infant_price' => $infantPrice,
                ];
            }
            $nightly_prices[] = $nightlyPrice;

            // محاسبه‌ی قیمت کل - استفاده از daily_rate
//            $totalAdultPrice += $calendar->daily_rate;
//            if ($childCount > 0) {
//                $totalChildPrice += $calendar->baby_cot_daily_rate;
//            }
//            if ($infantCount > 0) {
//                $totalInfantPrice += $calendar->baby_cot_daily_rate;
//            }

            $currentDate->addDay();
        }
        foreach ($nightly_prices as $nightlyPrice) {
            $totalAdultPrice += $nightlyPrice['adult']['adult_price'];
            $totalInfantPrice += $nightlyPrice['infant']['infant_price'];
            $totalChildPrice += $nightlyPrice['child']['child_price'];
            $totalExtraPrice += $nightlyPrice['extra_price'];
        }
        return [
            'pricing' => [
                'adult' => $totalAdultPrice,
                'child' => $totalChildPrice,
                'infant' => $totalInfantPrice,
                'extra_price' => $totalExtraPrice,
            ],
            'ratePlan' => $ratePlan,
            'total_price' => $totalAdultPrice + $totalChildPrice + $totalInfantPrice,
            'nightly_prices' => $nightly_prices,
        ];
    }
}
