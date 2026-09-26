<?php

namespace Tests\Unit;

use App\Models\Accommodation;
use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\ReservationCreateValidator;
use App\Support\Reservation\ReservationGuestType;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class ReservationCreateValidatorChildPolicyTest extends TestCase
{
    private function validator(
        ?RoomCalendarRepositoryInterface $calendars = null
    ): ReservationCreateValidator {
        return new ReservationCreateValidator(
            $calendars ?? $this->createMock(RoomCalendarRepositoryInterface::class),
            $this->createMock(ProviderRepositoryInterface::class),
        );
    }

    private function policy(array $overrides = []): HotelChildPolicy
    {
        return new HotelChildPolicy(array_merge([
            'max_infant_age' => 3,
            'max_child_age' => 7,
            'max_children_covered' => 1,
            'max_infants_covered' => 1,
            'infant_when_disabled' => 'as_child',
            'child_when_disabled' => 'as_adult',
            'infant_service_condition' => 'no_service',
            'child_service_condition' => 'no_service',
            'infant_pricing_type' => 'free',
            'child_pricing_type' => 'half',
            'status' => true,
        ], $overrides));
    }

    /**
     * @return array<int,array{date:string,price:int}>|null
     */
    private function calculate(
        array $guests,
        RoomType $room,
        HotelChildPolicy $policy,
        string $checkIn = '2026-09-26'
    ): ?array {
        $validator = $this->validator();

        $accommodation = new Accommodation();
        $accommodation->setRelation('childPolicy', $policy);

        $selected = new RoomCalendar();
        $selected->setRelation('accommodation', $accommodation);

        $live = collect([
            $checkIn => new RoomCalendar([
                'daily_rate' => 82_500_000,
                'rack_rate' => 82_500_000,
                'grs_rate' => 82_500_000,
                'extend_bed_daily_rate' => 20_000_000,
            ]),
        ]);

        $method = new ReflectionMethod($validator, 'calculateSelectedPrice');

        return $method->invoke(
            $validator,
            $room,
            $selected,
            $guests,
            [$checkIn],
            $live,
            CarbonImmutable::parse($checkIn),
        );
    }

    private function adult(string $birthday = '1994-04-06'): array
    {
        return [
            'type' => 1,
            'age' => 99,
            'birthday' => $birthday,
        ];
    }

    private function child(
        string $birthday = '2020-09-26',
        int $type = 2,
        int $age = 99
    ): array {
        return [
            'type' => $type,
            'age' => $age,
            'birthday' => $birthday,
        ];
    }

    public function test_request_type_and_age_are_ignored_and_birthday_decides_child_treatment(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->adult('1990-01-01'),
                // Declared as adult with a fake age, but birthday makes this guest 6.
                $this->child('2020-09-26', type: 1, age: 55),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy(),
        );

        $this->assertNotNull($result);
        $this->assertSame(103_125_000, $result[0]['price']);
    }

    public function test_nine_year_old_is_adult_by_policy_even_when_request_type_is_child(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->child('2017-09-26', type: 2, age: 1),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy(),
        );

        $this->assertNotNull($result);
        $this->assertSame(82_500_000, $result[0]['price']);
    }

    public function test_child_inside_unused_base_capacity_has_no_extra_child_charge(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->child(),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy(),
        );

        $this->assertNotNull($result);
        $this->assertSame(82_500_000, $result[0]['price']);
    }

    public function test_second_extra_child_outside_policy_allowance_is_full_service_price(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->adult('1990-01-01'),
                $this->child('2020-09-26'),
                $this->child('2021-09-26'),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 1]),
            $this->policy(),
        );

        $this->assertNotNull($result);

        // Base room 82.5m + one covered half-rate child 20.625m
        // + second child outside policy allowance as one full extra service 20m.
        $this->assertSame(123_125_000, $result[0]['price']);
    }

    public function test_second_extra_child_outside_policy_allowance_requires_extra_capacity(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->adult('1990-01-01'),
                $this->child('2020-09-26'),
                $this->child('2021-09-26'),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy(),
        );

        $this->assertNull($result);
    }

    public function test_only_children_beyond_normal_room_capacity_use_child_policy_price(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->child('2020-09-26'),
                $this->child('2021-09-26'),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy(),
        );

        $this->assertNotNull($result);

        // One child uses the already-paid second bed. Only the remaining child
        // is outside base capacity and receives the single half-rate allowance.
        $this->assertSame(103_125_000, $result[0]['price']);
    }

    public function test_age_is_calculated_at_check_in_not_reservation_creation_time(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->adult('1990-01-01'),
                // On 2026-09-26 this guest is 7; at check-in below the guest is 8
                // and therefore outside a max_child_age=7 policy.
                $this->child('2019-09-26', type: 2, age: 1),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 1]),
            $this->policy(),
            '2027-09-27',
        );

        $this->assertNotNull($result);
        $this->assertSame(102_500_000, $result[0]['price']);
    }

    public function test_exact_infant_age_boundary_becomes_child_instead_of_disappearing_between_ranges(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->adult('1990-01-01'),
                $this->child('2023-09-26', type: 3, age: 0),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy(),
        );

        $this->assertNotNull($result);
        $this->assertSame(103_125_000, $result[0]['price']);
    }

    public function test_infant_sublimit_can_fall_back_to_child_policy_for_extra_guests(): void
    {
        $result = $this->calculate(
            [
                $this->adult(),
                $this->adult('1990-01-01'),
                $this->child('2024-09-26', type: 3),
                $this->child('2025-09-26', type: 3),
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy([
                'max_children_covered' => 2,
                'max_infants_covered' => 1,
            ]),
        );

        $this->assertNotNull($result);

        // First infant is free, second infant falls back to half-rate child.
        $this->assertSame(103_125_000, $result[0]['price']);
    }

    public function test_local_validation_normalizes_guest_type_from_birthday_and_policy(): void
    {
        $policy = $this->policy();
        $accommodation = new Accommodation(['id' => 3]);
        $accommodation->id = 3;
        $accommodation->setRelation('childPolicy', $policy);

        $room = new RoomType([
            'id' => 10,
            'accommodation_id' => 3,
            'capacity' => 2,
            'extra_capacity' => 0,
            'out_of_service' => false,
        ]);

        $calendar = new RoomCalendar([
            'id' => 11264,
            'accommodation_id' => 3,
            'room_type_id' => 10,
            'day' => '2026-09-26',
        ]);
        $calendar->setRelation('roomType', $room);
        $calendar->setRelation('accommodation', $accommodation);

        $calendars = $this->createMock(RoomCalendarRepositoryInterface::class);
        $calendars->method('find')->with(11264)->willReturn($calendar);

        $validator = $this->validator($calendars);

        $normalized = $validator->validateGuestSelection([
            'check_in' => '2026-09-26',
            'hotel' => [
                'accommodation_id' => 3,
                'rooms' => [[
                    'calendar' => [[
                        'calendar_id' => 11264,
                        'date' => '2026-09-26',
                    ]],
                    'guests' => [
                        $this->adult(),
                        // Client calls this a child and sends a fake age, but birthday is 9.
                        $this->child('2017-09-26', type: 2, age: 1),
                    ],
                ]],
            ],
        ]);

        $this->assertSame(
            ReservationGuestType::ADULT,
            $normalized['hotel']['rooms'][0]['guests'][1]['type']
        );
    }

    public function test_uncovered_second_child_keeps_child_type_while_using_full_price(): void
    {
        $policy = $this->policy();
        $accommodation = new Accommodation(['id' => 3]);
        $accommodation->id = 3;
        $accommodation->setRelation('childPolicy', $policy);

        $room = new RoomType([
            'id' => 10,
            'accommodation_id' => 3,
            'capacity' => 2,
            'extra_capacity' => 1,
            'out_of_service' => false,
        ]);

        $calendar = new RoomCalendar([
            'id' => 11264,
            'accommodation_id' => 3,
            'room_type_id' => 10,
            'day' => '2026-09-26',
        ]);
        $calendar->setRelation('roomType', $room);
        $calendar->setRelation('accommodation', $accommodation);

        $calendars = $this->createMock(RoomCalendarRepositoryInterface::class);
        $calendars->method('find')->with(11264)->willReturn($calendar);

        $validator = $this->validator($calendars);

        $normalized = $validator->validateGuestSelection([
            'check_in' => '2026-09-26',
            'hotel' => [
                'accommodation_id' => 3,
                'rooms' => [[
                    'calendar' => [[
                        'calendar_id' => 11264,
                        'date' => '2026-09-26',
                    ]],
                    'guests' => [
                        $this->adult(),
                        $this->adult('1990-01-01'),
                        $this->child('2020-09-26'),
                        $this->child('2021-09-26'),
                    ],
                ]],
            ],
        ]);

        $this->assertSame(
            ReservationGuestType::CHILD,
            $normalized['hotel']['rooms'][0]['guests'][2]['type']
        );
        $this->assertSame(
            ReservationGuestType::CHILD,
            $normalized['hotel']['rooms'][0]['guests'][3]['type']
        );
    }

    public function test_local_validation_rejects_incompatible_guest_mix_before_reservation_insert(): void
    {
        $policy = $this->policy();
        $accommodation = new Accommodation(['id' => 3]);
        $accommodation->id = 3;
        $accommodation->setRelation('childPolicy', $policy);

        $room = new RoomType([
            'id' => 10,
            'accommodation_id' => 3,
            'capacity' => 2,
            'extra_capacity' => 0,
            'out_of_service' => false,
        ]);

        $calendar = new RoomCalendar([
            'id' => 11264,
            'accommodation_id' => 3,
            'room_type_id' => 10,
            'day' => '2026-09-26',
        ]);
        $calendar->setRelation('roomType', $room);
        $calendar->setRelation('accommodation', $accommodation);

        $calendars = $this->createMock(RoomCalendarRepositoryInterface::class);
        $calendars->method('find')->with(11264)->willReturn($calendar);

        $validator = $this->validator($calendars);

        $this->expectException(ValidationException::class);

        $validator->validateGuestSelection([
            'check_in' => '2026-09-26',
            'hotel' => [
                'accommodation_id' => 3,
                'rooms' => [[
                    'calendar' => [[
                        'calendar_id' => 11264,
                        'date' => '2026-09-26',
                    ]],
                    'guests' => [
                        $this->adult(),
                        $this->adult('1990-01-01'),
                        $this->child('2020-09-26'),
                        $this->child('2021-09-26'),
                    ],
                ]],
            ],
        ]);
    }
}
