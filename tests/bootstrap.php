<?php
// Portable bootstrap for running tests either inside the package repo
// or from a consuming project's vendor/bin/phpunit.

$candidates = [
    __DIR__ . '/../vendor/autoload.php',      // running inside the package with its own vendor
    __DIR__ . '/../../../autoload.php',       // running from a consumer project using root vendor autoload
];

$autoloaded = false;
foreach ($candidates as $file) {
    if (file_exists($file)) {
        require $file;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    fwrite(STDERR, "Autoloader not found. Checked: " . implode(', ', $candidates) . "\n");
    exit(1);
}

// If specific test groups were explicitly requested but required env is missing,
// provide a friendly, actionable message and exit early so the user sees it even
// without PHPUnit's verbose skip output.
if (PHP_SAPI === 'cli') {
    $argv = isset($_SERVER['argv']) ? implode(' ', $_SERVER['argv']) : '';
    $requestedIntegrationGroup = (bool) preg_match('/--group(?:=|\s).*\brequires-integrationnet\b/', $argv);
    $requestedHwGroup = (bool) preg_match('/--group(?:=|\s).*\brequires-hardwarewallet\b/', $argv);

    if ($requestedIntegrationGroup && !getenv('STELLAR_HORIZON_BASE_URL')) {
        fwrite(STDERR, "\n[stellar-api] Integration tests require environment variables.\n\n");
        fwrite(STDERR, "Set one of the following before re-running:\n\n");
        fwrite(STDERR, "- Testnet:\n");
        fwrite(STDERR, "  export STELLAR_HORIZON_BASE_URL=https://horizon-testnet.stellar.org/\n");
        fwrite(STDERR, "  export STELLAR_NETWORK_PASSPHRASE='Test SDF Network ; September 2015'\n\n");
        fwrite(STDERR, "- Local integration network (Docker):\n");
        fwrite(STDERR, "  export STELLAR_HORIZON_BASE_URL=http://localhost:8000/\n");
        fwrite(STDERR, "  export STELLAR_NETWORK_PASSPHRASE='Integration Test Network ; zulucrypto'\n\n");
        fwrite(STDERR, "Quick one-liner (Testnet):\n");
        fwrite(STDERR, "  STELLAR_HORIZON_BASE_URL=https://horizon-testnet.stellar.org/ \
STELLAR_NETWORK_PASSPHRASE='Test SDF Network ; September 2015' \
vendor/bin/phpunit -c vendor/zulucrypto/stellar-api/tests --group requires-integrationnet\n\n");
        exit(2);
    }

    if ($requestedHwGroup) {
        $missing = [];
        if (!getenv('STELLAR_HORIZON_BASE_URL')) $missing[] = 'STELLAR_HORIZON_BASE_URL';
        if (!getenv('STELLAR_NETWORK_PASSPHRASE')) $missing[] = 'STELLAR_NETWORK_PASSPHRASE';
        if (!getenv('STELLAR_SIGNING_PROVIDER')) $missing[] = 'STELLAR_SIGNING_PROVIDER';

        if (!empty($missing)) {
            fwrite(STDERR, "\n[stellar-api] Hardware wallet tests require environment variables.\n\n");
            fwrite(STDERR, "Missing: " . implode(', ', $missing) . "\n\n");
            fwrite(STDERR, "Testnet example:\n");
            fwrite(STDERR, "  export STELLAR_HORIZON_BASE_URL=https://horizon-testnet.stellar.org/\n");
            fwrite(STDERR, "  export STELLAR_NETWORK_PASSPHRASE='Test SDF Network ; September 2015'\n\n");
            fwrite(STDERR, "Hardware wallet provider example:\n");
            fwrite(STDERR, "  export STELLAR_SIGNING_PROVIDER=trezor\n");
            fwrite(STDERR, "  # optional if trezorctl not in PATH:\n");
            fwrite(STDERR, "  export TREZOR_BIN_PATH=/usr/local/bin/trezorctl\n\n");
            fwrite(STDERR, "Then re-run:\n");
            fwrite(STDERR, "  vendor/bin/phpunit -c vendor/zulucrypto/stellar-api/tests --group requires-hardwarewallet\n\n");
            exit(2);
        }
    }
}
