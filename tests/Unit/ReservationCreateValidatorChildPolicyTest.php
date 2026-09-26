<?php

namespace Tests\Unit;

use App\Models\Accommodation;
use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\ReservationCreateValidator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReservationCreateValidatorChildPolicyTest extends TestCase
{
    private function validator(): ReservationCreateValidator
    {
        return new ReservationCreateValidator(
            $this->createMock(RoomCalendarRepositoryInterface::class),
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

    private function calculate(array $guests, RoomType $room, HotelChildPolicy $policy): ?array
    {
        $validator = $this->validator();

        $accommodation = new Accommodation();
        $accommodation->setRelation('childPolicy', $policy);

        $selected = new RoomCalendar();
        $selected->setRelation('accommodation', $accommodation);

        $live = collect([
            '2026-09-26' => new RoomCalendar([
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
            ['2026-09-26'],
            $live
        );
    }

    public function test_nine_year_old_is_recalculated_from_birthday_and_becomes_adult_when_policy_ends_at_seven(): void
    {
        CarbonImmutable::setTestNow('2026-09-24 12:00:00');

        $validator = $this->validator();
        $method = new ReflectionMethod($validator, 'normalizeGuestCounts');

        $result = $method->invoke(
            $validator,
            [
                [
                    'type' => 2,
                    'age' => 1,
                    'birthday' => '2017-09-26',
                ],
                [
                    'type' => 1,
                    'age' => 99,
                    'birthday' => '1994-04-06',
                ],
            ],
            $this->policy(),
            CarbonImmutable::parse('2026-09-24')
        );

        CarbonImmutable::setTestNow();

        $this->assertSame([2, 0, 0], $result);
    }

    public function test_invalid_child_age_fails_when_room_has_no_extra_capacity(): void
    {
        $result = $this->calculate(
            [
                ['type' => 1, 'age' => 32, 'birthday' => '1994-04-06'],
                ['type' => 2, 'age' => 1, 'birthday' => '2017-09-26'],
            ],
            new RoomType(['capacity' => 1, 'extra_capacity' => 0]),
            $this->policy()
        );

        $this->assertNull($result);
    }

    public function test_eligible_child_uses_existing_room_capacity_without_extra_child_charge(): void
    {
        $result = $this->calculate(
            [
                ['type' => 1, 'age' => 32, 'birthday' => '1994-04-06'],
                ['type' => 2, 'age' => 6, 'birthday' => '2020-09-26'],
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy()
        );

        $this->assertNotNull($result);
        $this->assertSame(82_500_000, $result[0]['price']);
    }

    public function test_second_covered_child_becomes_adult_and_requires_capacity(): void
    {
        $result = $this->calculate(
            [
                ['type' => 1, 'age' => 32, 'birthday' => '1994-04-06'],
                ['type' => 2, 'age' => 6, 'birthday' => '2020-09-26'],
                ['type' => 3, 'age' => 2, 'birthday' => '2024-09-26'],
            ],
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $this->policy()
        );

        $this->assertNull($result);
    }
}
