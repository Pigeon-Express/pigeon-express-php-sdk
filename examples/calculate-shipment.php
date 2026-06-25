<?php
/**
 * Calculate Shipment Example
 *
 * This example demonstrates how to calculate shipment prices.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\CalculateShipmentRequest;
use PigeonExpress\DTO\Request\PackageData;
use PigeonExpress\Exceptions\ValidationException;
use PigeonExpress\Exceptions\PigeonExpressException;

$client = new PigeonExpress('your_api_key', 'your_api_secret');

try {
    // ==================
    // Example 1: Using DTO (recommended)
    // ==================

    echo "=== Calculate using DTO ===\n\n";

    $request = new CalculateShipmentRequest('office', 'address', 'standard');
    $request->setPickupOfficeId(1)
            ->addPackage(new PackageData(2.5, 30, 20, 15))
            ->setCodAmount(50.00)
            ->addServiceCode('sms_notification');

    $calculation = $client->shipments()->calculate($request);

    echo "Delivery Fee: " . $calculation->getDeliveryFee() . " EUR\n";
    echo "COD Fee: " . ($calculation->getCodFee() ?: 0) . " EUR\n";
    echo "Total: " . $calculation->getTotal() . " EUR\n";
    echo "Expected Delivery: " . $calculation->getExpectedDeliveryDays() . " days\n\n";

    // Show fee breakdown
    echo "Fee Breakdown:\n";
    foreach ($calculation->getFeeBreakdown() as $fee) {
        echo sprintf("  - %s: %.2f EUR\n", $fee->getName(), $fee->getAmount());
    }
    echo "\n";

    // ==================
    // Example 2: Using array
    // ==================

    echo "=== Calculate using array ===\n\n";

    $calculation = $client->shipments()->calculate(array(
        'packages' => array(
            array(
                'weight' => 2.5,
                'length' => 30,
                'width' => 20,
                'height' => 15,
            )
        ),
        'pickup_type' => 'office',
        'pickup_office_id' => 1,
        'delivery_type' => 'office',
        'delivery_office_id' => 2,
        'service_type' => 'standard',
    ));

    echo "Office-to-Office (standard): " . $calculation->getTotal() . " EUR\n";
    echo "Expected: " . $calculation->getExpectedDeliveryDays() . " days\n\n";

    // ==================
    // Example 3: Express service
    // ==================

    echo "=== Calculate express delivery ===\n\n";

    $expressRequest = new CalculateShipmentRequest('office', 'address', 'express');
    $expressRequest->setPickupOfficeId(1)
                   ->addPackage(new PackageData(1.0, 20, 15, 10));

    $expressCalc = $client->shipments()->calculate($expressRequest);

    echo "Express delivery: " . $expressCalc->getTotal() . " EUR\n";
    echo "Expected: " . $expressCalc->getExpectedDeliveryDays() . " day(s)\n\n";

    // ==================
    // Example 4: Multiple packages
    // ==================

    echo "=== Calculate with multiple packages ===\n\n";

    $multiRequest = new CalculateShipmentRequest('office', 'address', 'standard');
    $multiRequest->setPickupOfficeId(1)
                 ->addPackage(new PackageData(2.0, 30, 20, 15))
                 ->addPackage(new PackageData(1.5, 25, 20, 10))
                 ->addPackage(new PackageData(0.5, 15, 10, 5));

    $multiCalc = $client->shipments()->calculate($multiRequest);

    echo "3 packages total: " . $multiCalc->getTotal() . " EUR\n";

} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";

    if ($e->hasErrors()) {
        echo "Field errors:\n";
        foreach ($e->getErrors() as $field => $messages) {
            echo "  - $field: " . implode(', ', $messages) . "\n";
        }
    }

} catch (PigeonExpressException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
