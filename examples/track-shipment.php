<?php
/**
 * Track Shipment Example
 *
 * This example demonstrates how to track shipments.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PigeonExpress\PigeonExpress;
use PigeonExpress\Exceptions\NotFoundException;
use PigeonExpress\Exceptions\PigeonExpressException;

$client = new PigeonExpress('your_api_key', 'your_api_secret');

// Reference number to track
$referenceNumber = 'PGN-2026-00012345';

try {
    echo "=== Tracking Shipment: $referenceNumber ===\n\n";

    $tracking = $client->shipments()->track($referenceNumber);

    // Basic info
    echo "Reference: " . $tracking->getReferenceNumber() . "\n";
    echo "Status: " . $tracking->getStatus()->getName() . " (" . $tracking->getStatus()->getCode() . ")\n";
    echo "Receiver: " . $tracking->getReceiverName() . "\n";
    echo "Phone: " . $tracking->getReceiverPhone() . "\n";

    if ($tracking->getExpectedDeliveryDate() !== null) {
        echo "Expected Delivery: " . $tracking->getExpectedDeliveryDate() . "\n";
    }

    // Current location
    echo "\n--- Current Location ---\n";
    $location = $tracking->getCurrentLocation();
    if ($location !== null) {
        echo "Office: " . $tracking->getCurrentLocationName() . "\n";
        echo "City: " . $tracking->getCurrentLocationCity() . "\n";
    } else {
        echo "Location not available\n";
    }

    // Status checks
    echo "\n--- Status Checks ---\n";
    echo "Is Delivered: " . ($tracking->isDelivered() ? 'Yes' : 'No') . "\n";
    echo "Is In Transit: " . ($tracking->isInTransit() ? 'Yes' : 'No') . "\n";
    echo "Has Problem: " . ($tracking->hasProblem() ? 'Yes' : 'No') . "\n";

    // Tracking history
    echo "\n--- Tracking History (" . $tracking->getEventCount() . " events) ---\n";

    foreach ($tracking->getHistory() as $event) {
        echo sprintf(
            "[%s] %s\n",
            $event->getTimestamp(),
            $event->getStatusName()
        );

        if ($event->getLocation() !== null) {
            echo "   Location: " . $event->getLocation() . "\n";
        }

        if ($event->getNote() !== null) {
            echo "   Note: " . $event->getNote() . "\n";
        }

        // Show if this is a delivery or problem event
        if ($event->isDeliveryEvent()) {
            echo "   [DELIVERED]\n";
        } elseif ($event->isProblemEvent()) {
            echo "   [PROBLEM]\n";
        }

        echo "\n";
    }

    // Get latest event
    echo "--- Latest Event ---\n";
    $latest = $tracking->getLatestEvent();
    if ($latest !== null) {
        echo "Status: " . $latest->getStatusName() . "\n";
        echo "Time: " . $latest->getTimestamp() . "\n";
    }

} catch (NotFoundException $e) {
    echo "Shipment not found: $referenceNumber\n";
    echo "Please check the reference number and try again.\n";

} catch (PigeonExpressException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
