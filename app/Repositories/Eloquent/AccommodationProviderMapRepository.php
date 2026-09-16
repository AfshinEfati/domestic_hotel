<?php

namespace App\Repositories\Eloquent;

use App\Models\AccommodationProviderMap;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;

class AccommodationProviderMapRepository extends BaseRepository implements AccommodationProviderMapRepositoryInterface
{
    public function __construct(AccommodationProviderMap $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<AccommodationProviderMap>
     */
    public function getAll(): iterable
    {
        /** @var iterable<AccommodationProviderMap> */
        return parent::getAll();
    }

    public function find(int|string $id): ?AccommodationProviderMap
    {
        /** @var AccommodationProviderMap|null */
        return parent::find($id);
    }

    public function store(array $data): AccommodationProviderMap
    {
        /** @var AccommodationProviderMap */
        return parent::store($data);
    }

    public function countMappedPropertiesByProvider(int $providerId): int
    {
        return (int) $this->model
            ->where('provider_id', $providerId)
            ->whereNotNull('provider_property_id')
            ->count();
    }

    public function existsForAccommodationAndProvider(int $accommodationId, int $providerId): bool
    {
        return $this->model->newQuery()
            ->where('accommodation_id', $accommodationId)
            ->where('provider_id', $providerId)
            ->whereNotNull('provider_property_id')
            ->exists();
    }

    public function chunkMappedPropertiesByProvider(int $providerId, int $chunkSize, callable $callback): void
    {
        $this->model
            ->where('provider_id', $providerId)
            ->whereNotNull('provider_property_id')
            ->select('id', 'provider_property_id')
            ->orderBy('id')
            ->chunkById(max(1, $chunkSize), $callback, 'id');
    }

    public function chunkByProvider(int $providerId, callable $callback): void
    {
        $this->model->where('provider_id', $providerId)->chunk(100, $callback);
    }

    public function chunkActive(callable $callback): void
    {
        // برای تست فعلا ۱۰ دونه میگیریم
//        $maps = $this->model
//            ->whereHas('provider', function ($query) {
//                $query->where('is_active', true);
//            })
//            ->limit(20)
//            ->get();
//        $callback($maps);
        // بعد از تست اون بالا رو پاک کن و این پایین رو فعال کن .
        $this->model->whereHas('provider', function ($query) {
            $query->where('is_active', true);
        })->chunk(100, $callback);
    }
}
