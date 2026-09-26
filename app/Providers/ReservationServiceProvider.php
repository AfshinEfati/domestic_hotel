<?php

namespace App\Providers;

use App\Repositories\Contracts\ProviderCreditBalanceRepositoryInterface;
use App\Repositories\Contracts\PurchaseManualRuleRepositoryInterface;
use App\Repositories\Contracts\ReservationGuestRepositoryInterface;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationManualReasonRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomNightRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;
use App\Repositories\Eloquent\ProviderCreditBalanceRepository;
use App\Repositories\Eloquent\PurchaseManualRuleRepository;
use App\Repositories\Eloquent\ReservationGuestRepository;
use App\Repositories\Eloquent\ReservationHotelRepository;
use App\Repositories\Eloquent\ReservationManualPurchaseRepository;
use App\Repositories\Eloquent\ReservationManualReasonRepository;
use App\Repositories\Eloquent\ReservationPurchaseRepository;
use App\Repositories\Eloquent\ReservationPurchaseSegmentRepository;
use App\Repositories\Eloquent\ReservationRepository;
use App\Repositories\Eloquent\ReservationRoomNightRepository;
use App\Repositories\Eloquent\ReservationRoomRepository;
use App\Services\Contracts\PurchaseResolverInterface;
use App\Services\Contracts\ReservationPurchaseRequestServiceInterface;
use App\Services\Contracts\ReservationReferenceGeneratorInterface;
use App\Services\Contracts\ReservationServiceInterface;
use App\Services\PurchaseResolver;
use App\Services\ReservationPurchaseRequestService;
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
        $this->app->bind(ReservationRoomNightRepositoryInterface::class, ReservationRoomNightRepository::class);
        $this->app->bind(ReservationGuestRepositoryInterface::class, ReservationGuestRepository::class);
        $this->app->bind(ReservationPurchaseRepositoryInterface::class, ReservationPurchaseRepository::class);
        $this->app->bind(ReservationManualPurchaseRepositoryInterface::class, ReservationManualPurchaseRepository::class);
        $this->app->bind(ReservationPurchaseSegmentRepositoryInterface::class, ReservationPurchaseSegmentRepository::class);
        $this->app->bind(PurchaseManualRuleRepositoryInterface::class, PurchaseManualRuleRepository::class);
        $this->app->bind(ProviderCreditBalanceRepositoryInterface::class, ProviderCreditBalanceRepository::class);
        $this->app->bind(ReservationManualReasonRepositoryInterface::class, ReservationManualReasonRepository::class);
        $this->app->bind(ReservationReferenceGeneratorInterface::class, ReservationReferenceGenerator::class);
        $this->app->bind(ReservationServiceInterface::class, ReservationService::class);
        $this->app->bind(PurchaseResolverInterface::class, PurchaseResolver::class);
        $this->app->bind(ReservationPurchaseRequestServiceInterface::class, ReservationPurchaseRequestService::class);
    }
}
