Version 0.7.1 — Enhancements and compatibility fixes

Highlights
- New API: `Account::getPaymentOperations()` returns only payment-like operations (payment, path_payment). Use when you want to ignore create_account and account_merge entries in Horizon’s mixed payments feed. `Account::getPayments()` remains the heterogeneous feed.
- Amount formatting standardized at Stellar precision (7 decimals) across display getters:
  - `StellarAmount::getScaledValue()` and `AssetAmount::getBalance()` return 7-decimal strings
  - `Account::getNativeBalance()` returns a 7-decimal string (e.g., "0.0000000" when empty)
  - Stroop getters return integer strings for storage/comparison
- PHP 8.3 / PHPUnit 9 compatibility and deprecation fixes (Countable, dynamic properties, Memo validation, test annotations).
- Horizon error-handling resilience: JSON 404 handling for getAccount(); `postTransaction()` throws on non-2xx even with Guzzle exceptions disabled.
- Integration test UX: auto-fund core fixtures, graceful skipping; unified env var (`STELLAR_NETWORK_PASSPHRASE`).

Breaking Changes
- None. `Account::getPayments()` remains unchanged; the new method provides a filtered alternative.

Migration Notes
- Prefer `Account::getPaymentOperations()` when you only want payment/path_payment.
- Keep doing arithmetic in stroops (integer strings); use the 7-decimal display methods for presentation.
- Integration tests now expect:
  - `STELLAR_HORIZON_BASE_URL` (e.g., http://localhost:8000/)
  - `STELLAR_NETWORK_PASSPHRASE` (e.g., Integration Test Network ; zulucrypto)
  - Note: `STELLAR_NETWORK_PASSWORD` is no longer used.

Changelog Summary
- Add `Account::getPaymentOperations()` and unit tests
- Standardize amount formatting to 7 decimals; clarify PHPDocs
- Update PHPUnit 9 compatibility and fix PHP 8.3 deprecations
- Improve Horizon error handling for 404 JSON; throw on 4xx postTransaction
- Integration tests: auto-fund fixtures, skip without Horizon; use `STELLAR_NETWORK_PASSPHRASE`
- Normalize shell script line endings; docs on payments feed vs operations and amount precision

