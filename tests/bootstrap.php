<?php

/**
 * Minimal PSR-4 autoloader for the SDK test suite.
 *
 * The DTO and resource tests exercise pure mapping logic and need no HTTP
 * client, so the suite runs without a composer install.
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'PigeonExpress\\Tests\\' => __DIR__ . '/',
        'PigeonExpress\\'        => __DIR__ . '/../src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
