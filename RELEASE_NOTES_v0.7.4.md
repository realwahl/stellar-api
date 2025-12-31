Version 0.7.4 — Maintenance and API improvements

Highlights
- Account API: `Account::getTransactions()` and `Account::getPayments()` accept `order` and `includeFailed` and forward them to Horizon.
- Transaction model: map the `successful` field, use `fee_charged` when present, and throw on unhandled payment types.
- Test tooling: more resilient runner scripts and integration fixture checks.
- XDR encoding: `signedBigInteger64` now handles zero values safely.

Breaking Changes
- None.

Migration Notes
- Use `order`/`includeFailed` in account queries when you need non-default Horizon sorting or failed records.
- `Transaction::isSuccessful()` provides direct access to Horizon’s `successful` flag.
- Integration runner will prompt to run `tests/setup-integration-network.php` when fixtures are missing.

Changelog Summary
- Account payments/transactions can be ordered and include failed results.
- Transaction parsing includes `successful` and `fee_charged`; payment parsing throws on unknown types.
- Test scripts now resolve PHPUnit and can bootstrap missing fixtures.
- XDR signed 64-bit encoding now handles zero values.
