<?php

namespace Tests\Support;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Providers\BaseAdapter;
use Illuminate\Support\Collection;

class TestProviderAdapter extends BaseAdapter implements ProviderAdapterInterface
{
    public function code(): string
    {
        return 'test';
    }

    public function authenticate(): void
    {
        // No-op
    }

    public function fetchCities(): Collection
    {
        return collect();
    }

    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 100): Collection
    {
        return collect();
    }

    public function fetchRoomTypes(string $providerPropertyId): ?Collection
    {
        // Return sample data
        return collect([
            [
                'id' => 'RT-101',
                'name' => '  Single   Room  ', // Test normalization
                'name_en' => 'Single Room',
                'capacity' => 1,
                'extra_capacity' => 0,
                'rate_plans' => [
                    [
                        'id' => 'RP-101',
                        'name' => 'Breakfast Included',
                        'name_en' => 'Breakfast Included',
                        'meal_type_included' => 'breakfast',
                        'cancelable' => true,
                    ]
                ]
            ],
            [
                'id' => 'RT-102',
                'name' => 'Double Room',
                'name_en' => 'Double Room',
                'capacity' => 2,
                'extra_capacity' => 1,
                'rate_plans' => []
            ],
        ]);
    }

    public function fetchRatePlans(string $providerPropertyId): Collection
    {
        return collect();
    }

    public function fetchAvailability(string $providerPropertyId, \DateTimeInterface $from, \DateTimeInterface $to): Collection
    {
        return collect();
    }

    public function reserve(array $payload): array
    {
        return [];
    }

    public function extendExpire(string $reserveId): array
    {
        return [];
    }

    public function book(array $payload): array
    {
        return [];
    }

    public function modify(array $payload): array
    {
        return [];
    }

    public function cancel(array $payload): array
    {
        return [];
    }

    public function reservesList(array $filters = []): Collection
    {
        return collect();
    }

    public function reserveDetails(string $reserveId): array
    {
        return [];
    }

    public function supportedWebhooks(): array
    {
        return [];
    }

    public function fetchFacilities(): Collection
    {
        return collect();
    }

    public function fetchProperties(): Collection
    {
        return collect();
    }
}
