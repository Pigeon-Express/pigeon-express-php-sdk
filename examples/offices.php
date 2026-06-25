<?php
/**
 * Offices Example
 *
 * This example demonstrates various ways to work with offices.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PigeonExpress\PigeonExpress;
use PigeonExpress\DTO\Request\SearchOfficesRequest;
use PigeonExpress\Exceptions\PigeonExpressException;

$client = new PigeonExpress('your_api_key', 'your_api_secret');

try {
    // Example 1: Get all offices
    echo "=== All Offices ===\n";
    $offices = $client->offices()->all();
    echo "Total offices: " . count($offices) . "\n\n";

    // Example 2: Search offices using array
    echo "=== Search Offices in Sofia ===\n";
    $sofiaOffices = $client->offices()->search(array(
        'city' => 'София',
        'type' => 'office',
        'limit' => 5,
    ));

    foreach ($sofiaOffices as $office) {
        echo "- " . $office->getName() . " (" . $office->getAddress() . ")\n";
    }
    echo "\n";

    // Example 3: Search using DTO
    echo "=== Search Using DTO ===\n";
    $searchRequest = new SearchOfficesRequest();
    $searchRequest->setCity('Варна')
                  ->setType('locker')
                  ->setLimit(10);

    $varnaLockers = $client->offices()->search($searchRequest);
    echo "Found " . count($varnaLockers) . " lockers in Varna\n\n";

    // Example 4: Get lockers only
    echo "=== All Lockers ===\n";
    $lockers = $client->offices()->lockers();
    echo "Total lockers: " . count($lockers) . "\n";

    foreach (array_slice($lockers, 0, 3) as $locker) {
        echo "- " . $locker->getName();
        if ($locker->getWorkingHours() !== null && $locker->getWorkingHours()->is24Hours()) {
            echo " (24/7)";
        }
        echo "\n";
    }
    echo "\n";

    // Example 5: Get offices by city ID
    echo "=== Offices by City ID ===\n";
    $cityOffices = $client->offices()->byCity(759); // Varna
    echo "Offices in city ID 759: " . count($cityOffices) . "\n\n";

    // Example 6: Search by name
    echo "=== Search by Name ===\n";
    $results = $client->offices()->searchByName('Център', 5);

    foreach ($results as $office) {
        echo "- " . $office->getName() . " (" . $office->getCity()->getName() . ")\n";
    }

} catch (PigeonExpressException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
