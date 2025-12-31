## Description

PHP Library for interacting with the Stellar network.

* Communicate with Horizon server
* Build and sign transactions

## :warning: Danger Zone :warning:

**Development Status**

This library is under active development and should be considered beta quality.
Please ensure that you've tested extensively on a test network and have added
sanity checks in other places in your code.

:warning: [See the release notes for breaking changes](CHANGELOG.md) 

**Large Integer Support**

The largest PHP integer is 64-bits when on a 64-bit platform. This is especially
important to pay attention to when working with large balance transfers. The native
representation of a single XLM (1 XLM) is 10000000 stroops.

Therefore, if you try to use a `MAX_INT` number of XLM (or a custom asset) it is
possible to overflow PHP's integer type when the value is converted to stroops and
sent to the network.

This library attempts to add checks for this scenario and also uses a `BigInteger`
class to work around this problem.

If your application uses large amounts of XLM or a custom asset please do extensive
testing with large values and use the `StellarAmount` helper class or the `BigInteger` 
class if possible.

**Floating point issues**

Although not specific to Stellar or PHP, it's important to be aware of problems
when doing comparisons between floating point numbers.

For example:

```php
$oldBalance = 1.605;
$newBalance = 1.61;

var_dump($oldBalance + 0.005);
var_dump($newBalance);
if ($oldBalance + 0.005 === $newBalance) {
    print "Equal\n";
}
else {
    print "Not Equal\n";
}
```

The above code considers the two values not to be equal even though the same value
is printed out:

Output:
```
float(1.61)
float(1.61)
Not Equal
```

To work around this issue, always work with and store amounts as an integer representing stroops. Only convert
back to a decimal number when you need to display a balance to the user.

The static `StellarAmount::STROOP_SCALE` property can be used to help with this conversion.

## Installation

To install the latest release for usage in your project:

	cd your_project/
	composer require zulucrypto/stellar-api

If you want to work with the most recent development version you can use this repository:

	git clone https://github.com/zulucrypto/stellar-api.git
	cd stellar-api/
	composer install

## Getting Started

See the [getting-started](getting-started/) directory for examples of how to use this library.

These examples are modeled after the ones in Stellar's getting started guide:

https://www.stellar.org/developers/guides/get-started/create-account.html

Additional examples are available in the [examples](examples/) directory 

## Payments Feed vs. Payment Operations

Horizon’s `/accounts/{id}/payments` endpoint returns a mixed feed of records that are payment-related but include more than just payment operations. This library exposes two accessors on `Account`:

- `Account::getPayments($sinceCursor = null, $limit = 50)`
  - Returns the heterogeneous feed as-is, mapping each record to a typed model.
  - Includes: `create_account`, `payment`, `account_merge`, and `path_payment`.

- `Account::getPaymentOperations($sinceCursor = null, $limit = 50)`
  - Returns only payment-like operations.
  - Includes: `payment`, `path_payment`.
  - Excludes: `create_account`, `account_merge`.

Use `getPaymentOperations()` when you only care about actual transfer operations and want to ignore account creations or merges that may also appear in the payments feed.

When running integration tests, set the following environment variables:

- `STELLAR_HORIZON_BASE_URL` (e.g., `http://localhost:8000/`)
- `STELLAR_NETWORK_PASSPHRASE` (e.g., `Integration Test Network ; zulucrypto`)

Note: `STELLAR_NETWORK_PASSWORD` is no longer used.

Examples (choose one set):

- Local integration network (Docker):
  - `export STELLAR_HORIZON_BASE_URL=http://localhost:8000/`
  - `export STELLAR_NETWORK_PASSPHRASE='Integration Test Network ; zulucrypto'`

- Stellar Testnet:
  - `export STELLAR_HORIZON_BASE_URL=https://horizon-testnet.stellar.org/`
  - `export STELLAR_NETWORK_PASSPHRASE='Test SDF Network ; September 2015'`

- Stellar Public Network:
  - `export STELLAR_HORIZON_BASE_URL=https://horizon.stellar.org/`
  - `export STELLAR_NETWORK_PASSPHRASE='Public Global Stellar Network ; September 2015'`

## Amount Formatting and Precision

Stellar amounts are precise to 7 decimal places (1 XLM = 10,000,000 stroops). This library follows that convention:

- Display values (scaled):
  - `StellarAmount::getScaledValue()` → returns a string with 7 decimal places (e.g., `"12.3400000"`).
  - `AssetAmount::getBalance()` → same 7-decimal string.
  - `Account::getNativeBalance()` → 7-decimal string; returns `"0.0000000"` when empty.

- Storage/comparison values (stroops):
  - `StellarAmount::getUnscaledString()` → integer string of stroops (e.g., `"123400000"`).
  - `AssetAmount::getUnscaledBalance()` → stroops as an integer string.
  - `Account::getNativeBalanceStroops()` → stroops as an integer string.

Guidance:
- Do arithmetic and comparisons using stroops whenever possible.
- Format for display using the 7-decimal methods above.

## Timestamps and Nullability

Horizon returns RFC3339 timestamps (often with a trailing `Z`). This library parses these using PHP's `DateTime` and exposes them as nullable values:

- `Transaction::getCreatedAt()` → `?DateTime`
- `Operation::getCreatedAt()` → `?DateTime`
- `Ledger::getClosedAt()` → `?DateTime`

If Horizon returns an unexpected format that cannot be parsed, these accessors will return `null`. Guard accordingly before calling methods like `format()`.

## Hardware Wallet Tests

This project includes integration tests that exercise signing via a hardware wallet (currently Trezor is supported). These tests compare signatures produced by the device with signatures produced by this library, and prompt you to confirm details on the device screen.

Prerequisites

- A Trezor device (Model T recommended) or the Trezor emulator.
- `trezorctl` available in your `PATH` (or set `TREZOR_BIN_PATH`).
  - Docs: https://wiki.trezor.io/Trezor_Suite_command-line_interface
  - Install example (Python/pip): `pip install trezor` (provides `trezorctl`).
- A Horizon endpoint and network passphrase (Public, Testnet, or local integration network).

Environment variables

- Required:
  - `STELLAR_HORIZON_BASE_URL` — e.g. `https://horizon-testnet.stellar.org/`
  - `STELLAR_NETWORK_PASSPHRASE` — e.g. `Test SDF Network ; September 2015`
  - `STELLAR_SIGNING_PROVIDER` — set to `trezor`
- Optional:
  - `TREZOR_BIN_PATH` — full path to `trezorctl` if not in your `PATH`

Testnet quick start

```
export STELLAR_HORIZON_BASE_URL=https://horizon-testnet.stellar.org/
export STELLAR_NETWORK_PASSPHRASE='Test SDF Network ; September 2015'
export STELLAR_SIGNING_PROVIDER=trezor
# optional if trezorctl is not in PATH:
# export TREZOR_BIN_PATH=/usr/local/bin/trezorctl

./tests/run-hardware-wallet.sh --filter testCreateAccount
```

Test flow and expected prompts

- Tests will print human‑readable prompts before each signing action. The device should display the same transaction details (source, destination, amounts, memo) for you to confirm.
- After confirmation, the test asserts that the device’s signature matches the library’s signature for the same payload.

Derivation path and mnemonic used by tests

- The tests use the standard Stellar BIP‑44 path: `m/44'/148'/0'`.
- A fixed test mnemonic is embedded for reproducibility and to avoid prompting for secrets during CI:
  - `alcohol woman abuse must during monitor noble actual mixed trade anger aisle`
- Do not use this mnemonic for real funds.

Running specific tests

```
./tests/run-hardware-wallet.sh --filter testCustomAsset12Payment
```

Note: the wrapper resolves PHPUnit from this package, a consumer project's vendor bin, or your PATH.

Troubleshooting

- Device not detected: ensure `trezorctl list` finds your device; on Linux, install udev rules per Trezor docs and replug the device.
- Permission errors: try running `trezorctl` once manually; on macOS, grant USB permissions to your terminal if prompted.
- Wrong network: ensure `STELLAR_NETWORK_PASSPHRASE` matches your Horizon endpoint.
- Emulator: start the Trezor emulator before running tests and ensure `trezorctl` can communicate with it.

## Integration Tests

These tests exercise the library against a live Horizon endpoint (either Stellar Testnet, Public network, or a local integration network). A small wrapper script validates required environment variables and provides quick-start instructions when missing.

Run

```
./tests/run-integration.sh
```

Note: the wrapper resolves PHPUnit from this package, a consumer project's vendor bin, or your PATH.
If the integration fixtures are missing, the script will prompt to run `tests/setup-integration-network.php` before continuing.

Environment variables

- Required:
  - `STELLAR_HORIZON_BASE_URL` — e.g. `https://horizon-testnet.stellar.org/`
  - `STELLAR_NETWORK_PASSPHRASE` — e.g. `Test SDF Network ; September 2015`

Testnet quick start

```
export STELLAR_HORIZON_BASE_URL=https://horizon-testnet.stellar.org/
export STELLAR_NETWORK_PASSPHRASE='Test SDF Network ; September 2015'

./tests/run-integration.sh
```

Filtering tests

```
./tests/run-integration.sh --filter PaymentOpTest
```

Debug helper: XDR extraction

- A debug helper (`TransactionBuilderTest::testGetXdr`) is disabled by default and won’t appear as skipped.
- To enable it explicitly, set the environment variable and run (filter optional):

```
export INCLUDE_PHPUNIT_DEBUG=1
./tests/run-integration.sh --filter testGetXdr
```

## Donations

Stellar: GCUVDZRQ6CX347AMUUWZDYSNDFAWDN6FUYM5DVYYVO574NHTAUCQAK53
