<?php
/**
 * Basic Usage Example
 *
 * This example demonstrates basic SDK usage including initialization
 * and fetching offices.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PigeonExpress\PigeonExpress;
use PigeonExpress\Exceptions\PigeonExpressException;

// Initialize client with your API credentials
$client = new PigeonExpress(
    'your_api_key',
    'your_api_secret',
    array(
        'base_url' => 'https://api.pigeonexpress.com', // Optional, this is default
        'timeout' => 30, // Optional, default is 30 seconds
    )
);

try {
    // Get all offices
    echo "Fetching all offices...\n\n";

    $offices = $client->offices()->all();

    echo "Found " . count($offices) . " offices:\n";
    echo str_repeat('-', 60) . "\n";

    foreach ($offices as $office) {
        echo sprintf(
            "ID: %d | %s (%s)\n",
            $office->getId(),
            $office->getName(),
            $office->getType()
        );

        if ($office->getCity() !== null) {
            echo "   City: " . $office->getCity()->getFullName() . "\n";
        }

        echo "   Address: " . $office->getAddress() . "\n";

        if ($office->getPhone() !== null) {
            echo "   Phone: " . $office->getPhone() . "\n";
        }

        echo "\n";
    }

} catch (PigeonExpressException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "HTTP Status: " . $e->getHttpStatusCode() . "\n";
}
