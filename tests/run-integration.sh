#!/usr/bin/env bash
#
# Usage Examples:
#
#  ./run-integration.sh --filter testCreateAccount
#

set -euo pipefail

# Directory where this script is located
DIR="$( cd "$( dirname "$0" )" && pwd )"

# Required environment variables
MISSING=()
[[ -z "${STELLAR_HORIZON_BASE_URL:-}" ]] && MISSING+=("STELLAR_HORIZON_BASE_URL")
[[ -z "${STELLAR_NETWORK_PASSPHRASE:-}" ]] && MISSING+=("STELLAR_NETWORK_PASSPHRASE")

if (( ${#MISSING[@]} > 0 )); then
  echo "Integration tests require the following environment variables:" >&2
  for var in "${MISSING[@]}"; do
    echo "  - $var (not set)" >&2
  done
  echo >&2
  echo "Testnet example:" >&2
  echo "  export STELLAR_HORIZON_BASE_URL=https://horizon-testnet.stellar.org/" >&2
  echo "  export STELLAR_NETWORK_PASSPHRASE='Test SDF Network ; September 2015'" >&2
  echo >&2
  echo "Then re-run: ./tests/run-integration.sh" >&2
  exit 1
fi

# Run relative to the tests/ directory
cd "$DIR"

# Build group flags: include PHP Unit debug group based on env var
PHPUNIT_GROUP_FLAGS=(--group requires-integrationnet)

# Toggle include-phpunit-debug group when INCLUDE_PHPUNIT_DEBUG=1
if [[ "${INCLUDE_PHPUNIT_DEBUG:-}" == "1" ]]; then
  PHPUNIT_GROUP_FLAGS+=(--group include-phpunit-debug)
else
  PHPUNIT_GROUP_FLAGS+=(--exclude-group include-phpunit-debug)
fi

../vendor/bin/phpunit -c "$DIR" "${PHPUNIT_GROUP_FLAGS[@]}" "$@"
