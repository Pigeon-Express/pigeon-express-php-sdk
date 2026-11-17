<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\DTO\Request;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Request\AddressData;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;

/**
 * The create body the Magento module needs to send, and the guarantee that no
 * field handed to the request object is silently dropped on the way out.
 */
class CreateShipmentRequestTest extends TestCase
{
    private function request(string $pickup = 'office', string $delivery = 'office'): CreateShipmentRequest
    {
        return (new CreateShipmentRequest('Иван Петров', '0888123456', $pickup, $delivery, 'standard', 'sender'))
            ->setPickupOfficeId(149)
            ->setDeliveryOfficeId(1002)
            ->addPackage(new PackageData(1, 30, 20, 15));
    }

    public function testNewFieldsAreSent(): void
    {
        $body = $this->request()
            ->setExternalReference('ORDER-12345')
            ->enableHideContentOnLabel()
            ->enableReturnAtMyExpense()
            ->setReturnOfficeId(1001)
            ->setReturnDocumentsAddressId(512)
            ->setLabelFormat('zpl300')
            ->enablePayWithPos()
            ->setServiceCodes(['sms_notification_receiver' => true])
            ->setServiceCode('cod_amount', 64.06)
            ->addInventoryItem('Обувки', 2, 50.0, 0.8)
            ->toArray();

        $this->assertSame('ORDER-12345', $body['external_reference']);
        $this->assertTrue($body['hide_content_on_label']);
        $this->assertTrue($body['return_at_my_expense']);
        $this->assertSame(1001, $body['return_office_id']);
        $this->assertSame(512, $body['return_documents_address_id']);
        $this->assertSame('zpl300', $body['label_format']);
        $this->assertTrue($body['pay_with_pos']);
        $this->assertSame(['sms_notification_receiver' => true, 'cod_amount' => 64.06], $body['service_codes']);
        $this->assertSame(
            [['description' => 'Обувки', 'quantity' => 2, 'unit_price' => 50.0, 'unit_weight' => 0.8]],
            $body['inventory_items']
        );
        $this->assertSame('{"sms_notification_receiver":true,"cod_amount":64.06}', json_encode($body['service_codes']), 'an object on the wire');
    }

    public function testUnsetOptionalFieldsAreNotSent(): void
    {
        $body = $this->request()->toArray();

        foreach (['external_reference', 'hide_content_on_label', 'is_reverse', 'sender_name', 'service_codes',
            'inventory_items', 'return_office_id', 'sender_address_id', 'pay_with_pos'] as $key) {
            $this->assertArrayNotHasKey($key, $body);
        }
    }

    public function testPerPackageDescriptionAndFragileAreSent(): void
    {
        $body = $this->request()
            ->setPackages([['weight' => 2, 'length' => 10, 'width' => 10, 'height' => 10, 'description' => 'Ваза', 'is_fragile' => true]])
            ->toArray();

        $this->assertSame('Ваза', $body['packages'][0]['description']);
        $this->assertTrue($body['packages'][0]['is_fragile']);
    }

    public function testAReverseShipmentNamesTheSender(): void
    {
        $request = (new CreateShipmentRequest(null, null, 'office', 'address', 'standard', 'receiver'))
            ->enableReverse()
            ->setSender('Петър Георгиев', '0888999888', 'petar@example.com')
            ->setSenderCompany('Акме ООД', 'BG123456789', 'Петър Георгиев', 'гр. София, ул. Витоша 1')
            ->setPickupOfficeId(1001)
            ->setDeliveryAddressId(512)
            ->addPackage(new PackageData(1, 30, 20, 15));

        $this->assertTrue($request->validate());

        $body = $request->toArray();
        $this->assertTrue($body['is_reverse']);
        $this->assertSame('Петър Георгиев', $body['sender_name']);
        $this->assertSame('0888999888', $body['sender_phone']);
        $this->assertSame('petar@example.com', $body['sender_email']);
        $this->assertSame('BG123456789', $body['sender_company_vat']);
        $this->assertArrayNotHasKey('sender_company_dds_number', $body);
        $this->assertSame(512, $body['delivery_address_id']);
        $this->assertArrayNotHasKey('receiver_name', $body, 'an empty receiver defaults to the profile');
        $this->assertArrayNotHasKey('receiver_phone', $body);
    }

    public function testAReverseShipmentWithoutASenderIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Sender name/');

        (new CreateShipmentRequest(null, null, 'office', 'office', 'standard', 'receiver'))
            ->enableReverse()
            ->setPickupOfficeId(1)
            ->setDeliveryOfficeId(2)
            ->addPackage(new PackageData(1, 1, 1, 1))
            ->validate();
    }

    public function testASavedSenderAddressSatisfiesAnAddressPickup(): void
    {
        $request = (new CreateShipmentRequest('Иван Петров', '0888123456', 'address', 'office', 'standard', 'sender'))
            ->setSenderAddressId(512)
            ->setDeliveryOfficeId(1002)
            ->addPackage(new PackageData(1, 30, 20, 15));

        $this->assertTrue($request->validate());
        $this->assertSame(512, $request->toArray()['sender_address_id']);
        $this->assertArrayNotHasKey('sender_address', $request->toArray());
    }

    public function testALockerHandInUsesThePickupOfficeId(): void
    {
        $body = $this->request('locker', 'office')->setPickupOfficeId(3456)->toArray();

        $this->assertSame('locker', $body['pickup_type']);
        $this->assertSame(3456, $body['pickup_office_id']);
    }

    /** @return array<string, array{0: callable, 1: string}> */
    public function invalidRequests(): array
    {
        return [
            'external reference over 100' => [static function (CreateShipmentRequest $r): void {
                $r->setExternalReference(str_repeat('я', 101));
            }, '/at most 100/'],
            'two return destinations' => [static function (CreateShipmentRequest $r): void {
                $r->setReturnOfficeId(1)->setReturnAddressId(2);
            }, '/return office or a return address/'],
            'two return documents destinations' => [static function (CreateShipmentRequest $r): void {
                $r->setReturnDocumentsOfficeId(1)->setReturnDocumentsAddressId(2);
            }, '/return documents office/'],
            'saved delivery address on a normal shipment' => [static function (CreateShipmentRequest $r): void {
                $r->setDeliveryAddressId(512);
            }, '/only be used on a reverse/'],
            'address and saved address' => [static function (CreateShipmentRequest $r): void {
                $r->setSenderAddress(new AddressData(759))->setSenderAddressId(512);
            }, '/not both/'],
        ];
    }

    /** @dataProvider invalidRequests */
    public function testInvalidCombinationsAreRefused(callable $change, string $message): void
    {
        $request = $this->request();
        $change($request);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($message);
        $request->validate();
    }

    public function testExactlyOneHundredCharactersIsAccepted(): void
    {
        $this->assertTrue($this->request()->setExternalReference(str_repeat('я', 100))->validate());
    }

    public function testTheDocumentedSmsServiceSatisfiesLockerDelivery(): void
    {
        $request = $this->request('office', 'locker')->setServiceCode('sms_notification_receiver');

        $this->assertTrue($request->validate());
    }

    public function testFieldsWithoutASetterAreSentAsGiven(): void
    {
        $body = $this->request()
            ->set('allow_partial_acceptance', true)
            ->set('receiver_company_name', 'Акме ООД')
            ->toArray();

        $this->assertTrue($body['allow_partial_acceptance']);
        $this->assertSame('Акме ООД', $body['receiver_company_name']);
    }

    public function testFromArrayKeepsEveryField(): void
    {
        $payload = [
            'receiver_name'         => 'Иван Петров',
            'receiver_phone'        => '0888123456',
            'pickup_type'           => 'address',
            'delivery_type'         => 'address',
            'service_type'          => 'standard',
            'who_pays'              => 'receiver',
            'sender_address_id'     => 512,
            'delivery_address'      => ['city_id' => 759, 'street_id' => 1, 'street_number' => '5', 'unknown_address_key' => 'kept'],
            'packages'              => [['weight' => 1, 'length' => 1, 'width' => 1, 'height' => 1, 'unknown_package_key' => 'kept']],
            'inventory_items'       => [['description' => 'Книга', 'quantity' => 1]],
            'service_codes'         => ['sms_notification_receiver' => true, 'cod_amount' => 10.5],
            'external_reference'    => '100000123',
            'hide_content_on_label' => true,
            'return_at_my_expense'  => true,
            'return_office_id'      => 1001,
            'pay_with_pos'          => true,
            'note'                  => 'Обадете се',
            'allow_partial_acceptance' => false,
            'receiver_company_name' => 'Акме ООД',
            'a_field_from_the_future' => ['nested' => 1],
        ];

        $request = CreateShipmentRequest::fromArray($payload);
        $this->assertTrue($request->validate(), 'typed fields went through the setters');

        $body = $request->toArray();
        foreach ($payload as $key => $value) {
            $this->assertArrayHasKey($key, $body, $key . ' was dropped');
        }
        $this->assertSame('kept', $body['delivery_address']['unknown_address_key']);
        $this->assertSame('kept', $body['packages'][0]['unknown_package_key']);
        $this->assertFalse($body['allow_partial_acceptance']);
        $this->assertSame(['nested' => 1], $body['a_field_from_the_future']);
        $this->assertSame(1001, $body['return_office_id']);
        $this->assertSame(512, $body['sender_address_id']);
    }

    public function testFromArrayReadsTheReverseFields(): void
    {
        $request = CreateShipmentRequest::fromArray([
            'is_reverse'       => true,
            'sender_name'      => 'Петър Георгиев',
            'sender_phone'     => '0888999888',
            'pickup_type'      => 'office',
            'pickup_office_id' => 1001,
            'delivery_type'    => 'office',
            'delivery_office_id' => 1002,
            'service_type'     => 'standard',
            'who_pays'         => 'receiver',
            'packages'         => [['weight' => 1, 'length' => 1, 'width' => 1, 'height' => 1]],
        ]);

        $this->assertTrue($request->isReverse());
        $this->assertTrue($request->validate());
        $this->assertArrayNotHasKey('receiver_name', $request->toArray());
    }

    public function testFromArrayAcceptsPickupAddressAsTheSenderAddress(): void
    {
        $request = CreateShipmentRequest::fromArray([
            'receiver_name' => 'Иван Петров', 'receiver_phone' => '0888123456',
            'pickup_type' => 'address', 'delivery_type' => 'office', 'delivery_office_id' => 2,
            'service_type' => 'standard', 'who_pays' => 'sender',
            'pickup_address' => ['city_id' => 759, 'additional_info' => 'склад'],
            'packages' => [['weight' => 1, 'length' => 1, 'width' => 1, 'height' => 1]],
        ]);

        $this->assertTrue($request->validate());
        $this->assertSame(['city_id' => 759, 'additional_info' => 'склад'], $request->toArray()['sender_address']);
    }
}
