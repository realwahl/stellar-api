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

# Check whether integration fixtures are present; prompt to set up if not.
HORIZON_BASE_URL="${STELLAR_HORIZON_BASE_URL%/}"
USD_ISSUER_PUBLIC="GDJ7OPOMTHEUFEBT6VUR7ANXR6BOHKR754CZ3KMSIMQC43HHBEDVDWVG"

horizon_account_exists() {
  local account_id="$1"
  local url="${HORIZON_BASE_URL}/accounts/${account_id}"
  local code=""

  if command -v curl >/dev/null 2>&1; then
    code="$(curl -s -o /dev/null -w "%{http_code}" "$url")"
  else
    code="$(php -r '$url=$argv[1]; $ctx=stream_context_create(["http"=>["ignore_errors"=>true]]); @file_get_contents($url, false, $ctx); $code=0; if (isset($http_response_header[0]) && preg_match("#HTTP/\\S+\\s+(\\d+)#", $http_response_header[0], $m)) { $code=(int)$m[1]; } echo $code;' "$url")"
  fi

  [[ "$code" == "200" ]]
}

if ! horizon_account_exists "$USD_ISSUER_PUBLIC"; then
  echo "Integration fixtures not detected (issuer account missing)." >&2
  if [[ ! -t 0 ]]; then
    echo "Run: php tests/setup-integration-network.php" >&2
    exit 1
  fi

  read -r -p "Run setup now? [y/N] " reply
  case "$reply" in
    y|Y)
      if ! command -v php >/dev/null 2>&1; then
        echo "php not found in PATH; run tests/setup-integration-network.php manually." >&2
        exit 1
      fi
      php "$DIR/setup-integration-network.php"
      ;;
    *)
      echo "Setup required. Exiting." >&2
      exit 1
      ;;
  esac
fi

# Run relative to the tests/ directory
cd "$DIR"

# Resolve phpunit binary for both in-repo and consumer installs.
PHPUNIT_BIN="$DIR/../vendor/bin/phpunit"
if [[ ! -x "$PHPUNIT_BIN" ]]; then
  PHPUNIT_BIN="$DIR/../../../bin/phpunit"
fi
if [[ ! -x "$PHPUNIT_BIN" ]]; then
  if command -v phpunit >/dev/null 2>&1; then
    PHPUNIT_BIN="phpunit"
  else
    echo "phpunit not found. Checked:" >&2
    echo "  - $DIR/../vendor/bin/phpunit" >&2
    echo "  - $DIR/../../../bin/phpunit" >&2
    echo "  - phpunit in PATH" >&2
    exit 1
  fi
fi

# Build group flags: include PHP Unit debug group based on env var
PHPUNIT_GROUP_FLAGS=(--group requires-integrationnet)

# Toggle include-phpunit-debug group when INCLUDE_PHPUNIT_DEBUG=1
if [[ "${INCLUDE_PHPUNIT_DEBUG:-}" == "1" ]]; then
  PHPUNIT_GROUP_FLAGS+=(--group include-phpunit-debug)
else
  PHPUNIT_GROUP_FLAGS+=(--exclude-group include-phpunit-debug)
fi

"$PHPUNIT_BIN" -c "$DIR" "${PHPUNIT_GROUP_FLAGS[@]}" "$@"
