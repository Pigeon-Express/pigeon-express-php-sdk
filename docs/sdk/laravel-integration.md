# Laravel Integration Guide

Complete guide for integrating Pigeon Express SDK into a Laravel application.

## Installation

```bash
composer require pigeonexpress/php-sdk
```

## Configuration

### 1. Create Config File

Create `config/pigeon.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pigeon Express API Credentials
    |--------------------------------------------------------------------------
    */
    'enabled' => env('PIGEON_ENABLED', true),
    'api_key' => env('PIGEON_API_KEY'),
    'api_secret' => env('PIGEON_API_SECRET'),
    'base_url' => env('PIGEON_API_URL', 'https://api.pigeonexpress.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => env('PIGEON_TIMEOUT', 30),
    'default_pickup_office_id' => env('PIGEON_PICKUP_OFFICE_ID'),
    'default_service_type' => env('PIGEON_SERVICE_TYPE', 'standard'),
    'default_who_pays' => env('PIGEON_WHO_PAYS', 'receiver'),
];
```

### 2. Add Environment Variables

Add to your `.env` file:

```env
# Pigeon Express
PIGEON_ENABLED=true
PIGEON_API_KEY=pk_live_your_api_key
PIGEON_API_SECRET=sk_live_your_api_secret
PIGEON_API_URL=https://api.pigeonexpress.com/v1
PIGEON_TIMEOUT=30
PIGEON_PICKUP_OFFICE_ID=101
PIGEON_SERVICE_TYPE=standard
PIGEON_WHO_PAYS=receiver
```

### 3. Register Service Provider

In `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PigeonExpress\PigeonExpress;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PigeonExpress::class, function ($app) {
            $config = config('pigeon');

            return new PigeonExpress(
                $config['api_key'] ?? '',
                $config['api_secret'] ?? '',
                [
                    'base_url' => $config['base_url'] ?? null,
                    'timeout' => $config['timeout'] ?? 30,
                ]
            );
        });
    }
}
```

## Service Class

Create a service class to wrap SDK functionality:

### Create Service

`app/Services/PigeonExpressService.php`:

```php
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;
use PigeonExpress\DTO\Request\AddressData;
use PigeonExpress\DTO\Response\ShipmentCalculation;
use PigeonExpress\DTO\Response\Shipment;
use PigeonExpress\DTO\Response\TrackingInfo;
use PigeonExpress\Exceptions\PigeonExpressException;

class PigeonExpressService
{
    protected PigeonExpress $client;
    protected array $config;
    protected ?string $lastError = null;

    public function __construct()
    {
        $this->config = config('pigeon');

        $this->client = new PigeonExpress(
            $this->config['api_key'] ?? '',
            $this->config['api_secret'] ?? '',
            [
                'base_url' => $this->config['base_url'] ?? null,
                'timeout' => $this->config['timeout'] ?? 30,
            ]
        );
    }

    /**
     * Check if service is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return ($this->config['enabled'] ?? false)
            && !empty($this->config['api_key'])
            && !empty($this->config['api_secret']);
    }

    /**
     * Get the last error message.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get SDK client.
     */
    public function getClient(): PigeonExpress
    {
        return $this->client;
    }

    /**
     * Search cities.
     */
    public function searchCities(string $query, int $limit = 10): array
    {
        try {
            return $this->client->cities()->search($query, $limit);
        } catch (PigeonExpressException $e) {
            Log::error('Pigeon cities search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search streets in a city.
     */
    public function searchStreets(int $cityId, string $query, int $limit = 10): array
    {
        try {
            return $this->client->streets()->search($cityId, $query, $limit);
        } catch (PigeonExpressException $e) {
            Log::error('Pigeon streets search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search offices.
     */
    public function searchOffices(array $filters = []): array
    {
        try {
            return $this->client->offices()->search($filters);
        } catch (PigeonExpressException $e) {
            Log::error('Pigeon offices search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate shipping price.
     */
    public function calculateShipping(array $params): ?ShipmentCalculation
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $request = new CalculateShipmentRequest(
                $params['pickup_type'] ?? 'office',
                $params['delivery_type'] ?? 'address',
                $params['service_type'] ?? $this->config['default_service_type']
            );

            if (!empty($params['pickup_office_id'])) {
                $request->setPickupOfficeId($params['pickup_office_id']);
            } else {
                $request->setPickupOfficeId($this->config['default_pickup_office_id']);
            }

            if (!empty($params['delivery_office_id'])) {
                $request->setDeliveryOfficeId($params['delivery_office_id']);
            }

            // Add package
            $request->addPackage(new PackageData(
                $params['weight'] ?? 1.0,
                $params['length'] ?? 20,
                $params['width'] ?? 15,
                $params['height'] ?? 10
            ));

            if (!empty($params['cod_amount'])) {
                $request->setCodAmount($params['cod_amount']);
            }

            return $this->client->shipments()->calculate($request);

        } catch (PigeonExpressException $e) {
            Log::error('Pigeon calculate error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create shipment for an order.
     */
    public function createShipment(Order $order, array $options = []): ?Shipment
    {
        $this->lastError = null;

        if (!$this->isEnabled()) {
            $this->lastError = 'Pigeon Express is not enabled.';
            return null;
        }

        try {
            $request = new CreateShipmentRequest(
                $order->delivery_name,
                $order->delivery_phone,
                'office',
                $order->delivery_type,
                $options['service_type'] ?? $this->config['default_service_type'],
                $options['who_pays'] ?? $this->config['default_who_pays']
            );

            if ($order->delivery_email) {
                $request->setReceiverEmail($order->delivery_email);
            }

            $request->setPickupOfficeId($this->config['default_pickup_office_id']);

            // Set delivery location
            if ($order->delivery_type === 'address') {
                $address = new AddressData($order->pigeon_city_id);

                if ($order->pigeon_street_id) {
                    $address->setStreetId($order->pigeon_street_id);
                }
                if ($order->street_number) {
                    $address->setStreetNumber($order->street_number);
                }
                // ... add other address fields

                $request->setDeliveryAddress($address);
            } else {
                $request->setDeliveryOfficeId($order->pigeon_office_id);
            }

            // Package
            $request->addPackage(new PackageData(
                $options['weight'] ?? 1.0,
                $options['length'] ?? 20,
                $options['width'] ?? 15,
                $options['height'] ?? 10
            ));

            // COD
            $codAmount = $options['cod_amount'] ?? $order->subtotal;
            if ($codAmount > 0) {
                $request->setCodAmount($codAmount);
            }

            // SMS for locker
            if ($order->delivery_type === 'locker' || ($options['sms_notification'] ?? false)) {
                $request->enableSmsNotification();
            }

            // Note
            if (!empty($options['note'])) {
                $request->setNote($options['note']);
            }

            $shipment = $this->client->shipments()->create($request);

            // Update order
            $order->update([
                'pigeon_reference_number' => $shipment->getReferenceNumber(),
                'pigeon_tracking_number' => $shipment->getFirstPackage()?->getTrackingNumber(),
            ]);

            Log::info('Pigeon shipment created', [
                'order_id' => $order->id,
                'reference' => $shipment->getReferenceNumber(),
            ]);

            return $shipment;

        } catch (PigeonExpressException $e) {
            $this->lastError = $e->getMessage();
            Log::error('Pigeon create shipment error: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            return null;
        }
    }

    /**
     * Track a shipment.
     */
    public function trackShipment(string $referenceNumber): ?TrackingInfo
    {
        try {
            return $this->client->shipments()->track($referenceNumber);
        } catch (PigeonExpressException $e) {
            Log::error('Pigeon tracking error: ' . $e->getMessage());
            return null;
        }
    }
}
```

### Register Service

```php
// In AppServiceProvider
$this->app->singleton(PigeonExpressService::class);
```

## Database Migrations

### Cities Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pigeon_cities', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('type')->default('град');
            $table->string('municipality')->nullable();
            $table->string('district')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('has_streets')->default(false);
            $table->timestamps();

            $table->index('name');
            $table->index('municipality');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pigeon_cities');
    }
};
```

### Offices Table

```php
Schema::create('pigeon_offices', function (Blueprint $table) {
    $table->unsignedBigInteger('id')->primary();
    $table->unsignedBigInteger('city_id')->nullable();
    $table->string('name');
    $table->string('code')->nullable();
    $table->string('type')->default('office');
    $table->string('address')->nullable();
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    $table->json('working_hours')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index('city_id');
    $table->index('type');
});
```

### Orders Table (Add Pigeon Fields)

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('delivery_type')->default('address');
    $table->unsignedBigInteger('pigeon_city_id')->nullable();
    $table->unsignedBigInteger('pigeon_street_id')->nullable();
    $table->unsignedBigInteger('pigeon_office_id')->nullable();
    $table->string('pigeon_reference_number')->nullable();
    $table->string('pigeon_tracking_number')->nullable();

    $table->index('pigeon_reference_number');
});
```

## Artisan Command for Syncing

Create `app/Console/Commands/SyncPigeonLocations.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\PigeonCity;
use App\Models\PigeonOffice;
use App\Services\PigeonExpressService;
use Illuminate\Console\Command;

class SyncPigeonLocations extends Command
{
    protected $signature = 'pigeon:sync {--cities} {--offices} {--all}';
    protected $description = 'Sync Pigeon Express locations';

    public function handle(PigeonExpressService $service): int
    {
        if (!$service->isEnabled()) {
            $this->error('Pigeon Express is not configured.');
            return 1;
        }

        $syncAll = $this->option('all');

        if ($syncAll || $this->option('cities')) {
            $this->syncCities($service);
        }

        if ($syncAll || $this->option('offices')) {
            $this->syncOffices($service);
        }

        $this->info('Sync completed!');
        return 0;
    }

    protected function syncCities(PigeonExpressService $service): void
    {
        $this->info('Syncing cities...');

        $cities = $service->getClient()->cities()->all();
        $bar = $this->output->createProgressBar(count($cities));

        foreach ($cities as $city) {
            PigeonCity::updateOrCreate(
                ['id' => $city->getId()],
                [
                    'name' => $city->getName(),
                    'type' => $city->getType(),
                    'municipality' => $city->getMunicipality(),
                    'district' => $city->getDistrict(),
                    'postal_code' => $city->getPostalCode(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced " . count($cities) . " cities.");
    }

    protected function syncOffices(PigeonExpressService $service): void
    {
        $this->info('Syncing offices...');

        $offices = $service->getClient()->offices()->all();
        $bar = $this->output->createProgressBar(count($offices));

        foreach ($offices as $office) {
            PigeonOffice::updateOrCreate(
                ['id' => $office->getId()],
                [
                    'city_id' => $office->getCityId(),
                    'name' => $office->getName(),
                    'code' => $office->getCode(),
                    'type' => $office->getType(),
                    'address' => $office->getAddress(),
                    'latitude' => $office->getLatitude(),
                    'longitude' => $office->getLongitude(),
                    'working_hours' => $office->getWorkingHours()?->toArray(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced " . count($offices) . " offices.");
    }
}
```

Register in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Sync locations daily
    $schedule->command('pigeon:sync --all')->daily();
}
```

## API Controller

Create `app/Http/Controllers/Api/ShippingController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PigeonExpressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(
        protected PigeonExpressService $pigeon
    ) {}

    public function searchCities(Request $request): JsonResponse
    {
        $cities = $this->pigeon->searchCities(
            $request->input('query', ''),
            $request->input('limit', 10)
        );

        return response()->json([
            'success' => true,
            'data' => array_map(fn($city) => [
                'id' => $city->getId(),
                'name' => $city->getName(),
                'full_name' => $this->formatCityName($city),
                'type' => $city->getType(),
                'municipality' => $city->getMunicipality(),
            ], $cities),
        ]);
    }

    public function searchOffices(Request $request): JsonResponse
    {
        $offices = $this->pigeon->searchOffices([
            'city_id' => $request->input('city_id'),
            'type' => $request->input('type'),
        ]);

        return response()->json([
            'success' => true,
            'data' => array_map(fn($office) => [
                'id' => $office->getId(),
                'name' => $office->getName(),
                'address' => $office->getAddress(),
                'type' => $office->getType(),
            ], $offices),
        ]);
    }

    public function calculateShipping(Request $request): JsonResponse
    {
        $calculation = $this->pigeon->calculateShipping([
            'delivery_type' => $request->input('delivery_type'),
            'delivery_office_id' => $request->input('office_id'),
            'cod_amount' => $request->input('cod_amount', 0),
        ]);

        if (!$calculation) {
            return response()->json([
                'success' => false,
                'error' => 'Unable to calculate shipping.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shipping_price' => $calculation->getDeliveryFee(),
                'total' => $calculation->getTotal(),
                'expected_days' => $calculation->getExpectedDeliveryDays(),
            ],
        ]);
    }

    protected function formatCityName($city): string
    {
        $prefix = match ($city->getType()) {
            'село' => 'с.',
            'град' => 'гр.',
            default => 'гр.',
        };

        $name = $prefix . ' ' . $city->getName();

        if ($city->getMunicipality()) {
            $name .= ', общ. ' . $city->getMunicipality();
        }

        return $name;
    }
}
```

## Routes

Add to `routes/api.php`:

```php
Route::prefix('shipping')->group(function () {
    Route::get('cities', [ShippingController::class, 'searchCities']);
    Route::get('streets', [ShippingController::class, 'searchStreets']);
    Route::get('offices', [ShippingController::class, 'searchOffices']);
    Route::post('calculate', [ShippingController::class, 'calculateShipping']);
});
```

## Usage in Controllers

```php
<?php

use App\Services\PigeonExpressService;

class CheckoutController extends Controller
{
    public function __construct(
        protected PigeonExpressService $pigeon
    ) {}

    public function complete(Request $request)
    {
        // Create order...

        // Create shipment
        $shipment = $this->pigeon->createShipment($order, [
            'cod_amount' => $order->subtotal,
            'who_pays' => 'receiver',
        ]);

        if (!$shipment) {
            // Handle error
            $error = $this->pigeon->getLastError();
        }

        // Continue...
    }
}
```

## Testing

```php
<?php

namespace Tests\Feature;

use App\Services\PigeonExpressService;
use Tests\TestCase;

class PigeonExpressTest extends TestCase
{
    public function test_can_search_cities(): void
    {
        $service = app(PigeonExpressService::class);

        $cities = $service->searchCities('София');

        $this->assertNotEmpty($cities);
        $this->assertEquals('София', $cities[0]->getName());
    }

    public function test_can_calculate_shipping(): void
    {
        $service = app(PigeonExpressService::class);

        $calculation = $service->calculateShipping([
            'delivery_type' => 'office',
            'delivery_office_id' => 101,
        ]);

        $this->assertNotNull($calculation);
        $this->assertGreaterThan(0, $calculation->getTotal());
    }
}
```
