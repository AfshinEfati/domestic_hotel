<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\SystemLog;
use App\Services\Contracts\ProviderServiceInterface;
use App\Services\Contracts\RoomTypeProviderMapServiceInterface;
use App\Services\Contracts\RoomTypeServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncRoomsForHotelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $providerId;
    public int $accommodationId;
    public string $providerHotelId;

    public function __construct(int $providerId, int $accommodationId, string $providerHotelId)
    {
        $this->providerId = $providerId;
        $this->accommodationId = $accommodationId;
        $this->providerHotelId = $providerHotelId;
    }

    public function handle(
        ProviderServiceInterface $providerService,
        RoomTypeServiceInterface $roomTypeService,
        RoomTypeProviderMapServiceInterface $mapService
    ): void
    {
        try {
            $provider = $providerService->show($this->providerId);
            if (!$provider) {
                return;
            }

            /** @var ProviderAdapterInterface $adapter */
            $adapter = app()->makeWith(ProviderAdapterInterface::class, [
                'provider' => $provider,
            ]);

            $rooms = $adapter->fetchRoomTypes($this->providerHotelId);
            if ($rooms === null) {
                return;
            }

            foreach ($rooms as $roomData) {
                $this->syncRoom($roomData, $roomTypeService, $mapService);
            }

        } catch (Exception $e) {
             SystemLog::create([
                'level' => 'error',
                'method' => 'SyncRoomsForHotelJob',
                'message' => $e->getMessage(),
                'provider_id' => $this->providerId,
                'context' => ['accommodation_id' => $this->accommodationId, 'provider_hotel_id' => $this->providerHotelId],
            ]);
        }
    }

    protected function syncRoom(
        array $roomData,
        RoomTypeServiceInterface $roomTypeService,
        RoomTypeProviderMapServiceInterface $mapService
    ): void
    {
        $providerRoomTypeId = $roomData['room_type_id'];
        $name = $roomData['fa_name'];

        // 1. Check if map exists
        $map = $mapService->findByProviderAndRemoteId($this->providerId, $providerRoomTypeId);

        if ($map) {
            // Update existing map or room type if needed
            // For now, we assume if mapped, it's fine.
            return;
        }

        // 2. Check if RoomType exists in this Accommodation by name
        // We try to find a room type with the same name in the same accommodation
        $roomType = $roomTypeService->findByAccommodationAndName($this->accommodationId, $name);

        if (!$roomType) {
            // Create new RoomType
            $roomType = $roomTypeService->store([
                'accommodation_id' => $this->accommodationId,
                'fa_name' => $name,
                'en_name' => $roomData['en_name'] ?? null,
                'capacity' => $roomData['capacity'] ?? 0,
                'extra_capacity' => $roomData['extra'] ?? 0,
                'single_bed_count' => 0, // Default or parse if available
                'double_bed_count' => 0,
                'sofa_bed_count' => 0,
                'out_of_service' => false,
            ]);
        }

        // 3. Create Map
        $mapService->store([
            'room_type_id' => $roomType->id,
            'provider_id' => $this->providerId,
            'provider_room_type_id' => $providerRoomTypeId,
            'fa_name' => $name,
            'en_name' => $roomData['en_name'] ?? null,
        ]);
    }
}
