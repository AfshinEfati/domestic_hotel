<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\Rule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelRuleTest extends TestCase
{
    // use RefreshDatabase; // Uncomment if database connection is available

    public function test_can_create_a_rule(): void
    {
        $rule = Rule::create([
            'title' => 'سن کودک',
            'name' => 'child_age',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('rules', [
            'name' => 'child_age',
        ]);
    }

    public function test_can_assign_rule_to_accommodation_with_value(): void
    {
        $rule = Rule::create([
            'title' => 'سن کودک',
            'name' => 'child_age',
        ]);

        $accommodation = Accommodation::factory()->create();

        $accommodation->rules()->attach($rule->id, ['value' => '7']);

        $this->assertDatabaseHas('accommodation_rule', [
            'accommodation_id' => $accommodation->id,
            'rule_id' => $rule->id,
            'value' => '7',
        ]);

        $this->assertEquals('7', $accommodation->rules->first()->pivot->value);
    }
}
