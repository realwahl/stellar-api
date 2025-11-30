<?php
// Portable bootstrap for running tests either inside the package repo
// or from a consuming project's vendor/bin/phpunit.

$candidates = [
    __DIR__ . '/../vendor/autoload.php',      // running inside the package with its own vendor
    __DIR__ . '/../../../autoload.php',       // running from a consumer project using root vendor autoload
];

foreach ($candidates as $file) {
    if (file_exists($file)) {
        require $file;
        return;
    }
}

fwrite(STDERR, "Autoloader not found. Checked: " . implode(', ', $candidates) . "\n");
exit(1);

