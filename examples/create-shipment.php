<?php
/**
 * Create Shipment Example
 *
 * This example demonstrates how to create shipments.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\CreateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;
use PigeonExpress\DTO\Request\AddressData;
use PigeonExpress\Exceptions\ValidationException;
use PigeonExpress\Exceptions\PigeonExpressException;

$client = new PigeonExpress('your_api_key', 'your_api_secret');

try {
    // ==================
    // Example 1: Office-to-Office shipment
    // ==================

    echo "=== Office-to-Office Shipment ===\n\n";

    $request = new CreateShipmentRequest(
        'Иван Петров',           // receiver_name
        '+359888123456',         // receiver_phone
        'office',                // pickup_type
        'office',                // delivery_type
        'standard',              // service_type
        'sender'                 // who_pays
    );

    $request->setReceiverEmail('ivan@example.com')
            ->setPickupOfficeId(1)
            ->setDeliveryOfficeId(2)
            ->addPackage(
                (new PackageData(2.5, 30, 20, 15))->setDescription('Книги')
            )
            ->setCodAmount(50.00)
            ->enableSmsNotification()
            ->setNote('Моля, обадете се преди доставка');

    $shipment = $client->shipments()->create($request);

    echo "Shipment created successfully!\n";
    echo "Reference Number: " . $shipment->getReferenceNumber() . "\n";
    echo "Status: " . $shipment->getStatus()->getName() . "\n";
    echo "Price: " . $shipment->getDeliveryPrice() . " EUR\n";
    echo "Expected Delivery: " . $shipment->getExpectedDeliveryDate() . "\n";

    if ($shipment->getFirstPackage() !== null) {
        echo "Tracking Number: " . $shipment->getFirstPackage()->getTrackingNumber() . "\n";
    }
    echo "\n";

    // ==================
    // Example 2: Office-to-Address shipment
    // ==================

    echo "=== Office-to-Address Shipment ===\n\n";

    // Create delivery address
    $deliveryAddress = new AddressData(759); // Varna city ID
    $deliveryAddress->setStreetId(100)
                    ->setStreetNumber('15')
                    ->setBuildingNumber('А')
                    ->setEntrance('1')
                    ->setFloor('3')
                    ->setApartment('12')
                    ->setPostalCode('9000')
                    ->setNeighborhood('Лозенец')
                    ->setContactName('Мария Иванова')
                    ->setContactPhone('+359887654321')
                    ->setAdditionalInfo('До магазина');

    $request2 = new CreateShipmentRequest(
        'Мария Иванова',
        '+359887654321',
        'office',
        'address',
        'standard',
        'receiver'
    );

    $request2->setReceiverEmail('maria@example.com')
             ->setPickupOfficeId(1)
             ->setDeliveryAddress($deliveryAddress)
             ->addPackage(
                 (new PackageData(1.2, 25, 15, 10))->setDescription('Дрехи')
             )
             ->enableSmsNotification();

    $shipment2 = $client->shipments()->create($request2);

    echo "Shipment created: " . $shipment2->getReferenceNumber() . "\n";
    echo "Price: " . $shipment2->getDeliveryPrice() . " EUR (receiver pays)\n\n";

    // ==================
    // Example 3: Office-to-Locker shipment
    // ==================

    echo "=== Office-to-Locker Shipment ===\n\n";

    $request3 = new CreateShipmentRequest(
        'Анна Георгиева',
        '+359888777666',
        'office',
        'locker',
        'standard',
        'sender'
    );

    $request3->setReceiverEmail('anna@example.com')
             ->setPickupOfficeId(1)
             ->setDeliveryOfficeId(5) // Locker ID
             ->addPackage(
                 (new PackageData(0.8, 20, 15, 8))->setDescription('Аксесоари')
             )
             ->enableSmsNotification() // Required for locker delivery!
             ->setNote('Доставка до АПС локър');

    $shipment3 = $client->shipments()->create($request3);

    echo "Locker shipment created: " . $shipment3->getReferenceNumber() . "\n\n";

    // ==================
    // Example 4: Address-to-Address with all services
    // ==================

    echo "=== Address-to-Address with Services ===\n\n";

    $senderAddress = new AddressData(1); // Sofia city ID
    $senderAddress->setStreetId(50)
                  ->setStreetNumber('5')
                  ->setFloor('2')
                  ->setPostalCode('1000')
                  ->setContactName('Петър Петров')
                  ->setContactPhone('+359888999777');

    $receiverAddress = new AddressData(759); // Varna city ID
    $receiverAddress->setStreetId(100)
                    ->setStreetNumber('20')
                    ->setEntrance('Б')
                    ->setFloor('5')
                    ->setApartment('22')
                    ->setPostalCode('9000')
                    ->setContactName('Георги Димитров')
                    ->setContactPhone('+359899111222');

    $request4 = new CreateShipmentRequest(
        'Георги Димитров',
        '+359899111222',
        'address',
        'address',
        'express',
        'sender'
    );

    $request4->setSenderAddress($senderAddress)
             ->setDeliveryAddress($receiverAddress)
             ->addPackage(
                 (new PackageData(5.0, 40, 30, 25))
                     ->setDescription('Електроника')
                     ->setFragile(true)
             )
             ->setCodAmount(150.00)
             ->enableSmsNotification()
             ->enableRequiresSignature()
             ->enableSpecialPackaging();

    $shipment4 = $client->shipments()->create($request4);

    echo "Express shipment created: " . $shipment4->getReferenceNumber() . "\n";
    echo "Price: " . $shipment4->getDeliveryPrice() . " EUR\n";

} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";

    if ($e->hasErrors()) {
        echo "\nField errors:\n";
        foreach ($e->getErrors() as $field => $messages) {
            foreach ((array) $messages as $message) {
                echo "  - $field: $message\n";
            }
        }
    }

} catch (PigeonExpressException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "HTTP Status: " . $e->getHttpStatusCode() . "\n";
}
