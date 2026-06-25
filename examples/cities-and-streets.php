<?php
/**
 * Cities and Streets Example
 *
 * This example demonstrates working with cities and streets.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PigeonExpress\PigeonExpress;
use PigeonExpress\Exceptions\PigeonExpressException;

$client = new PigeonExpress('your_api_key', 'your_api_secret');

try {
    // ==================
    // CITIES
    // ==================

    echo "=== Cities ===\n\n";

    // Get all cities
    echo "Fetching all cities...\n";
    $cities = $client->cities()->all();
    echo "Total cities: " . count($cities) . "\n\n";

    // Show first 5 cities
    echo "First 5 cities:\n";
    foreach (array_slice($cities, 0, 5) as $city) {
        echo sprintf(
            "- %s (ID: %d, Postal: %s, District: %s)\n",
            $city->getFullName(),
            $city->getId(),
            $city->getPostalCode() ?: 'N/A',
            $city->getDistrict() ?: 'N/A'
        );
    }
    echo "\n";

    // Find city by ID
    echo "Finding city by ID 759...\n";
    $varna = $client->cities()->find(759);
    if ($varna !== null) {
        echo "Found: " . $varna->getFullName() . "\n";
        echo "District: " . $varna->getDistrict() . "\n";
        echo "Municipality: " . $varna->getMunicipality() . "\n";
    }
    echo "\n";

    // Search cities
    echo "Searching for cities containing 'Плов'...\n";
    $searchResults = $client->cities()->search('Плов');
    foreach ($searchResults as $city) {
        echo "- " . $city->getFullName() . "\n";
    }
    echo "\n";

    // Find by postal code
    echo "Finding city by postal code '1000'...\n";
    $sofia = $client->cities()->findByPostalCode('1000');
    if ($sofia !== null) {
        echo "Found: " . $sofia->getFullName() . "\n";
    }
    echo "\n";

    // ==================
    // STREETS
    // ==================

    echo "=== Streets ===\n\n";

    // Get streets by city
    echo "Fetching streets in Varna (ID: 759)...\n";
    $streets = $client->streets()->byCity(759);
    echo "Total streets: " . count($streets) . "\n\n";

    // Show first 10 streets
    echo "First 10 streets:\n";
    foreach (array_slice($streets, 0, 10) as $street) {
        echo sprintf(
            "- %s (ID: %d, Type: %s)\n",
            $street->getFullName(),
            $street->getId(),
            $street->getType()
        );
    }
    echo "\n";

    // Search streets
    echo "Searching for streets containing 'Левски' in Varna...\n";
    $searchResults = $client->streets()->searchInCity(759, 'Левски', 10);
    foreach ($searchResults as $street) {
        echo "- " . $street->getFullName() . "\n";
    }
    echo "\n";

    // Search streets across all cities
    echo "Searching for 'Христо Ботев' across all cities...\n";
    $searchResults = $client->streets()->searchByName('Христо Ботев', 5);
    foreach ($searchResults as $street) {
        echo sprintf("- %s (City ID: %d)\n", $street->getFullName(), $street->getCityId());
    }

} catch (PigeonExpressException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
