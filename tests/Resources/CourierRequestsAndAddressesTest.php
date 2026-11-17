<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\Resources;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Response\CourierRequest;
use PigeonExpress\Exceptions\ConflictException;
use PigeonExpress\PigeonExpress;
use PigeonExpress\Resources\AbstractResource;
use PigeonExpress\Resources\Addresses;
use PigeonExpress\Resources\CourierRequests;
use PigeonExpress\Resources\ShipmentStatuses;

/**
 * Courier requests, saved addresses and the shipment status list, called the
 * way the WooCommerce integration calls them.
 */
class CourierRequestsAndAddressesTest extends TestCase
{
    private function courierRequest(string $code = 'courier_request_pending'): array
    {
        return [
            'request_number'             => 'CR-458106744669',
            'request_type'               => 'groupage',
            'status'                     => ['code' => $code, 'name' => 'Нова заявка'],
            'contact'                    => ['name' => 'Иван', 'phone' => '0888123456', 'email' => null],
            'pickup'                     => ['city' => 'София', 'address' => 'улица Витоша 15'],
            'requested_pickup_date'      => '2026-07-13',
            'requested_pickup_time_type' => 'interval',
            'requested_pickup_time_from' => '09:00',
            'requested_pickup_time_to'   => '13:00',
            'created_shipment_reference' => null,
            'created_at'                 => '2026-07-10 17:17:57',
        ];
    }

    public function testCreatePostsTheBodyAsGiven(): void
    {
        $body = [
            'pickup_city_id'             => 759,
            'pickup_street_id'           => 433950,
            'pickup_street_number'       => '15',
            'requested_pickup_date'      => '2026-07-13',
            'requested_pickup_time_type' => 'interval',
            'requested_pickup_time_from' => '09:00',
            'requested_pickup_time_to'   => '13:00',
            'shipment_references'        => ['457911010807', '457911010603'],
        ];
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => $this->courierRequest()], 201)]);
        $resource = new CourierRequests($client);

        $request = $resource->create($body);

        $this->assertSame('POST', $client->calls[0]['method']);
        $this->assertSame('courier-requests', $client->calls[0]['path']);
        $this->assertSame($body, $client->calls[0]['body']);
        $this->assertSame('CR-458106744669', $request->getRequestNumber());
        $this->assertSame(CourierRequest::TYPE_GROUPAGE, $request->getRequestType());
        $this->assertSame(CourierRequest::STATUS_PENDING, $request->getStatusCode());
        $this->assertSame('13:00', $request->getRequestedPickupTimeTo());
        $this->assertFalse($request->isFinal());
        $this->assertSame(AbstractResource::REPLAY_NONE, $resource->getLastReplay());
    }

    public function testCreateReportsAReplayedAnswer(): void
    {
        $client = new ScriptedHttpClient([
            ScriptedHttpClient::json(['success' => true, 'data' => $this->courierRequest()], 201, ['X-Duplicate-Request' => 'true']),
        ]);
        $resource = new CourierRequests($client);

        $resource->create(['pickup_city_id' => 759]);

        $this->assertSame(AbstractResource::REPLAY_EARLIER, $resource->getLastReplay());
    }

    public function testCreateRepeatsAConflict(): void
    {
        $client = new ScriptedHttpClient([
            new ConflictException('in progress', 1),
            ScriptedHttpClient::json(['success' => true, 'data' => $this->courierRequest()], 201),
        ]);

        (new SleeplessCourierRequests($client))->create(['pickup_city_id' => 759]);

        $this->assertCount(2, $client->calls);
    }

    public function testGetAndCancelUseTheEncodedNumber(): void
    {
        $client = new ScriptedHttpClient([
            ScriptedHttpClient::json(['success' => true, 'data' => $this->courierRequest()]),
            ScriptedHttpClient::json(['success' => true, 'message' => 'Заявката е отказана успешно', 'data' => [
                'request_number' => 'CR-458106744669',
                'status'         => ['code' => 'courier_request_cancelled', 'name' => 'Отменена заявка'],
            ]]),
        ]);
        $resource = new CourierRequests($client);

        $resource->get('CR 1/2');
        $cancelled = $resource->cancel('CR 1/2');

        $this->assertSame('GET', $client->calls[0]['method']);
        $this->assertSame('courier-requests/CR%201%2F2', $client->calls[0]['path']);
        $this->assertSame('POST', $client->calls[1]['method']);
        $this->assertSame('courier-requests/CR%201%2F2/cancel', $client->calls[1]['path']);
        $this->assertTrue($cancelled->isCancelled());
        $this->assertTrue($cancelled->isFinal());
    }

    public function testThereIsNoListOfCourierRequests(): void
    {
        // GET /courier-requests answers 404 on the sandbox and is not in the
        // docs: a request is read back by its number only.
        $this->assertFalse(method_exists(CourierRequests::class, 'all'));
        $this->assertFalse(method_exists(CourierRequests::class, 'list'));
    }

    public function testSavedAddressesAreListed(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => [
            [
                'id'            => 512,
                'is_default'    => true,
                'label'         => 'гр. София, улица Витоша, № 12',
                'contact_name'  => 'Иван Петров',
                'contact_phone' => '0888123456',
                'address'       => [
                    'city_id' => 759, 'city_name' => 'София', 'street_id' => 10234, 'street_name' => 'улица Витоша',
                    'street_number' => '12', 'building_number' => null, 'entrance' => 'А', 'floor' => '3',
                    'apartment' => '7', 'neighborhood' => null, 'postal_code' => '1000', 'additional_info' => null,
                ],
            ],
            [
                'id'         => 513,
                'is_default' => false,
                'label'      => 'с. Лозен',
                'address'    => ['city_id' => 1001, 'street_id' => null, 'additional_info' => 'до магазина'],
            ],
        ]])]);

        $addresses = (new Addresses($client))->all();

        $this->assertSame('addresses', $client->calls[0]['path']);
        $this->assertSame([], $client->calls[0]['query'], 'the endpoint takes no parameters');
        $this->assertCount(2, $addresses);

        $default = $addresses[0];
        $this->assertSame(512, $default->getId());
        $this->assertTrue($default->isDefault());
        $this->assertSame(759, $default->getCityId());
        $this->assertSame(10234, $default->getStreetId());
        $this->assertSame('София', $default->getCityName());

        $this->assertSame([
            'city_id'         => 759,
            'street_id'       => 10234,
            'street_number'   => '12',
            'entrance'        => 'А',
            'floor'           => '3',
            'apartment'       => '7',
            'postal_code'     => '1000',
            'contact_name'    => 'Иван Петров',
            'contact_phone'   => '0888123456',
        ], $default->toAddressData()->toArray(), 'display names stay out of an address request');

        $this->assertNull($addresses[1]->getStreetId());
    }

    public function testFindAndDefaultAddress(): void
    {
        $page = ScriptedHttpClient::json(['success' => true, 'data' => [
            ['id' => 1, 'is_default' => false, 'address' => ['city_id' => 1]],
            ['id' => 2, 'is_default' => true, 'address' => ['city_id' => 2]],
        ]]);
        $resource = new Addresses(new ScriptedHttpClient([$page, $page, $page]));

        $this->assertSame(2, $resource->getDefault()->getId());
        $this->assertSame(1, $resource->find(1)->getId());
        $this->assertNull($resource->find(99));
    }

    public function testShipmentStatusesAreListed(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => [
            ['code' => 'shipment_registered', 'name' => 'Създадена от подател'],
            ['code' => 'shipment_courier_assigned', 'name' => 'Взета от куриер'],
        ]])]);

        $statuses = (new ShipmentStatuses($client))->all();

        $this->assertSame('shipment-statuses', $client->calls[0]['path']);
        $this->assertSame('shipment_registered', $statuses[0]->getCode());
        $this->assertSame('Взета от куриер', $statuses[1]->getName());
    }

    public function testShipmentStatusNamesByCode(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => [
            ['code' => 'shipment_registered', 'name' => 'Създадена от подател'],
        ]])]);

        $this->assertSame(
            ['shipment_registered' => 'Създадена от подател'],
            (new ShipmentStatuses($client))->namesByCode()
        );
    }

    public function testTheClientExposesTheNewResources(): void
    {
        $sdk = (new PigeonExpress('key', 'secret'))->setHttpClient(new ScriptedHttpClient());

        $this->assertInstanceOf(ShipmentStatuses::class, $sdk->shipmentStatuses());
        $this->assertInstanceOf(CourierRequests::class, $sdk->courierRequests);
        $this->assertInstanceOf(Addresses::class, $sdk->addresses);
    }
}

/**
 * Courier requests with the waits removed.
 */
class SleeplessCourierRequests extends CourierRequests
{
    protected function pause($microseconds)
    {
        // Deliberately does nothing.
    }
}
