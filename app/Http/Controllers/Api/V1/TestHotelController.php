<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Providers\PartoAdapter;
use App\Domain\Hotel\Providers\SnappTripAdapter;
use App\Http\Controllers\Controller;
use App\Jobs\Hotel\SyncProviderAvailabilityForPropertyJob;
use App\Support\Logging\SystemLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use App\Models\Provider;

// فرض بر این است که مدل پروایدر دارید
use App\Domain\Hotel\Providers\GRSAdapter;

// آدرس کلاس آداپتور شما
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TestHotelController extends Controller
{
    protected Provider $provider;
    protected string $baseUrl;
    protected array $headers = [];
    protected $client;
    public function __construct()
    {
    }



    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function test()
    {
        $this->provider = Provider::find(4);
        $this->authenticate();
        $this->baseUrl = "https://b2bapiv2.snapptrip.com";
        $this->client = Http::withHeaders($this->headers)
            ->baseUrl($this->baseUrl)
            ->timeout(30);
        $from = Carbon::parse("2026-02-20");
        $to = Carbon::parse("2026-02-25");
        $providerPropertyId = 1134;
        return $this->client->get("/availability/hotels/$providerPropertyId", [
            'checkin' => '2026-02-20',
            'checkout' => '2026-02-25',
        ])->object();
        $hotelData = data_get($res, 'items.0', []);
        $rooms = data_get($hotelData, 'rooms', []);
        $availability = collect();
        foreach ($rooms as $room) {
            $roomId = (string)$room['id'];
            $dailyData = $room['daily'] ?? [];
            foreach ($dailyData as $dateStr => $data) {
                $availability->push([
                    'day' => $dateStr,
                    'room_type_id' => $roomId,
                    'inventory' => $data['availability'] ?? 0,
                    'daily_rate' => $data['price'] ?? 0,
                    'rack_rate' => $data['original_sell_price'] ?? 0,
                    'min_stay' => $data['min_stay'] ?? 1,
                    'max_stay' => null,
                    'cta' => false,
                    'ctd' => false,
                    'closed' => ($data['availability'] ?? 0) <= 0,
                    // Rate plan info is implicit in the room/price
                    'rate_plan_id' => null,
                ]);
            }
        }

        return $availability;
    }


    public function authenticate(): void
    {
        $token = $this->provider->config['token'] ?? null;
        if (!$token) {
            throw new RuntimeException("SnappTrip provider missing token (api-key) in config");
        }
        $this->setHeader('api-key', $token);
    }
    protected function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }


}
