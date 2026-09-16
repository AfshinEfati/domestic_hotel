<?php

namespace Tests\Unit;

use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Services\AvailabilityFilterService;
use App\Services\HotelChildPolicyTextParser;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ChildPolicyCoverageTest extends TestCase
{
    private function normalize(array $passengers, array $attributes): array
    {
        $service = new AvailabilityFilterService(
            $this->createMock(RoomCalendarRepositoryInterface::class)
        );

        return (new ReflectionMethod($service, 'normalizePassengers'))->invoke(
            $service,
            $passengers,
            new HotelChildPolicy(array_merge([
                'max_infant_age' => 5,
                'max_child_age' => 10,
                'max_children_covered' => 1,
                'max_infants_covered' => null,
                'infant_when_disabled' => 'as_child',
                'child_when_disabled' => 'as_adult',
                'infant_pricing_type' => 'free',
                'child_pricing_type' => 'half',
                'status' => true,
            ], $attributes))
        );
    }

    public function test_korosh_two_four_year_olds_share_a_single_allowance(): void
    {
        $result = $this->normalize([
            ['type' => 'adl'],
            ['type' => 'adl'],
            ['type' => 'chd', 'age' => 4],
            ['type' => 'chd', 'age' => 4],
        ], []);

        $this->assertSame(['adult', 'adult', 'infant', 'adult'], array_column($result, 'type'));
    }

    public function test_free_infant_has_priority_over_half_rate_child_regardless_of_request_order(): void
    {
        $first = $this->normalize([
            ['type' => 'child', 'age' => 8],
            ['type' => 'child', 'age' => 4],
        ], []);

        $second = $this->normalize([
            ['type' => 'child', 'age' => 4],
            ['type' => 'child', 'age' => 8],
        ], []);

        $this->assertSame(['adult', 'infant'], array_column($first, 'type'));
        $this->assertSame(['infant', 'adult'], array_column($second, 'type'));
    }

    public function test_infant_sublimit_falls_back_to_child_only_when_shared_limit_has_room(): void
    {
        $result = $this->normalize([
            ['type' => 'chd', 'age' => 4],
            ['type' => 'chd', 'age' => 4],
        ], [
            'max_children_covered' => 2,
            'max_infants_covered' => 1,
        ]);

        $this->assertSame(['infant', 'child'], array_column($result, 'type'));
    }

    public function test_zero_shared_limit_makes_all_children_adults(): void
    {
        $result = $this->normalize([
            ['type' => 'child', 'age' => 4],
        ], [
            'max_children_covered' => 0,
        ]);

        $this->assertSame(['adult'], array_column($result, 'type'));
    }

    public function test_null_shared_limit_does_not_disable_infant_sublimit(): void
    {
        $result = $this->normalize([
            ['type' => 'child', 'age' => 4],
            ['type' => 'child', 'age' => 4],
        ], [
            'max_children_covered' => null,
            'max_infants_covered' => 1,
        ]);

        $this->assertSame(['infant', 'child'], array_column($result, 'type'));
    }

    public function test_spinas_infant_only_rule_converts_second_infant_to_adult(): void
    {
        $result = $this->normalize([
            ['type' => 'child', 'age' => 3],
            ['type' => 'child', 'age' => 3],
        ], [
            'max_infant_age' => 4,
            'max_child_age' => 0,
            'max_children_covered' => null,
            'max_infants_covered' => 1,
            'child_pricing_type' => 'adult',
        ]);

        $this->assertSame(['infant', 'adult'], array_column($result, 'type'));
    }

    public function test_an_expired_discount_affects_capacity_and_room_price(): void
    {
        $passengers = $this->normalize([
            ['type' => 'adult'],
            ['type' => 'adult'],
            ['type' => 'child', 'age' => 4],
            ['type' => 'child', 'age' => 4],
        ], []);

        $counts = array_count_values(array_column($passengers, 'type'));
        $counts += ['adult' => 0, 'child' => 0, 'infant' => 0];

        $service = new AvailabilityFilterService(
            $this->createMock(RoomCalendarRepositoryInterface::class)
        );
        $method = new ReflectionMethod($service, 'priceOption');

        $policy = new HotelChildPolicy([
            'max_infant_age' => 5,
            'max_child_age' => 10,
            'max_children_covered' => 1,
            'infant_pricing_type' => 'free',
            'child_pricing_type' => 'half',
            'infant_service_condition' => 'no_service',
            'child_service_condition' => 'no_service',
        ]);
        $calendar = new RoomCalendar([
            'daily_rate' => 45000000,
            'rack_rate' => 50000000,
            'grs_rate' => 40000000,
            'extend_bed_daily_rate' => 15000000,
        ]);
        $dates = ['2027-03-04'];
        $calendars = collect(['2027-03-04' => $calendar]);

        $unavailable = $method->invoke(
            $service,
            new RoomType(['capacity' => 2, 'extra_capacity' => 0]),
            $counts,
            $policy,
            $dates,
            $calendars
        );
        $this->assertNull($unavailable);

        $available = $method->invoke(
            $service,
            new RoomType(['capacity' => 2, 'extra_capacity' => 1]),
            $counts,
            $policy,
            $dates,
            $calendars
        );
        $this->assertSame(1, $available['extra_bed_count']);
        $this->assertSame(15000000, $available['pricing']['extra_total']);
        $this->assertSame(60000000, $available['total_price']);
    }

    public function test_parser_recognizes_shared_free_and_half_rate_allowance(): void
    {
        $parser = new HotelChildPolicyTextParser();

        $korosh = $parser->parse(
            'اقامت کودک زیر 5 سال رایگان می باشد و کودک بین 5 تا 10 سال نیم بها محاسبه می گردد. اقامت رایگان و نیم بها تنها برای یک کودک محاسبه می گردد.',
            ['max_infant_age' => 5, 'max_child_age' => 10]
        );

        $spinas = $parser->parse(
            'اقامت کودک زیر 4 سال رایگان می باشد. اقامت رایگان و نیم بها تنها برای یک کودک محاسبه می گردد.',
            ['max_infant_age' => 4, 'max_child_age' => 0]
        );

        $this->assertSame(1, $korosh['max_children_covered']);
        $this->assertNull($korosh['max_infants_covered']);
        $this->assertSame(1, $spinas['max_children_covered']);
        $this->assertNull($spinas['max_infants_covered']);
    }

    public function test_parser_keeps_an_explicit_infant_sublimit(): void
    {
        $parser = new HotelChildPolicyTextParser();

        $result = $parser->parse(
            'اقامت رایگان و نیم بها فقط برای دو کودک است. حداکثر یک نوزاد رایگان است.',
            ['max_infant_age' => 5, 'max_child_age' => 10]
        );

        $this->assertSame(2, $result['max_children_covered']);
        $this->assertSame(1, $result['max_infants_covered']);
    }
}
