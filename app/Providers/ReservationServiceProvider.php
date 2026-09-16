<?php

namespace App\Providers;

use App\Repositories\Contracts\PurchaseManualRuleRepositoryInterface;
use App\Repositories\Contracts\ReservationGuestRepositoryInterface;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;
use App\Repositories\Eloquent\PurchaseManualRuleRepository;
use App\Repositories\Eloquent\ReservationGuestRepository;
use App\Repositories\Eloquent\ReservationHotelRepository;
use App\Repositories\Eloquent\ReservationPurchaseSegmentRepository;
use App\Repositories\Eloquent\ReservationRepository;
use App\Repositories\Eloquent\ReservationRoomRepository;
use App\Services\Contracts\PurchaseResolverInterface;
use App\Services\Contracts\ReservationReferenceGeneratorInterface;
use App\Services\Contracts\ReservationServiceInterface;
use App\Services\PurchaseResolver;
use App\Services\ReservationReferenceGenerator;
use App\Services\ReservationService;
use Illuminate\Support\ServiceProvider;

class ReservationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);
        $this->app->bind(ReservationHotelRepositoryInterface::class, ReservationHotelRepository::class);
        $this->app->bind(ReservationRoomRepositoryInterface::class, ReservationRoomRepository::class);
        $this->app->bind(ReservationGuestRepositoryInterface::class, ReservationGuestRepository::class);
        $this->app->bind(ReservationPurchaseSegmentRepositoryInterface::class, ReservationPurchaseSegmentRepository::class);
        $this->app->bind(PurchaseManualRuleRepositoryInterface::class, PurchaseManualRuleRepository::class);
        $this->app->bind(ReservationReferenceGeneratorInterface::class, ReservationReferenceGenerator::class);
        $this->app->bind(ReservationServiceInterface::class, ReservationService::class);
        $this->app->bind(PurchaseResolverInterface::class, PurchaseResolver::class);
    }
}
