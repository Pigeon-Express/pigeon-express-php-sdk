#!/usr/bin/env bash
#
# Build a distributable zip of the Pigeon Express PHP SDK.
#
# Same whitelist approach as the Magento module: only paths named in SHIP_PATHS
# are copied, so test suites, tooling configs and internal notes cannot leak into
# a client archive even if nobody remembers to exclude them.
#
# Usage:
#   bin/build-zip.sh [version]
#
# Version defaults to the value in composer.json.

set -euo pipefail

SDK_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$SDK_ROOT"

# ---------------------------------------------------------------------------
# The whitelist. Only these paths ship.
# ---------------------------------------------------------------------------
SHIP_PATHS=(
  composer.json
  README.md
  CHANGELOG.md
  LICENSE
  SECURITY.md
  src
  examples
)

PRUNE_NAMES=(
  ".DS_Store"
  "*.orig"
  "*.rej"
  "*.bak"
  "*~"
)

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  VERSION="$(php -r '$c = json_decode(file_get_contents("composer.json"), true); echo $c["version"] ?? "0.0.0";' 2>/dev/null || echo "0.0.0")"
fi

PACKAGE_NAME="pigeon-express-php-sdk"
STAGING="$(mktemp -d)"
TARGET_DIR="$STAGING/$PACKAGE_NAME"
OUT_DIR="$SDK_ROOT/build"
OUT_FILE="$OUT_DIR/${PACKAGE_NAME}.${VERSION}.zip"

cleanup() { rm -rf "$STAGING"; }
trap cleanup EXIT

echo "Building $PACKAGE_NAME $VERSION"
echo

mkdir -p "$TARGET_DIR" "$OUT_DIR"

for path in "${SHIP_PATHS[@]}"; do
  if [[ ! -e "$path" ]]; then
    echo "  WARNING: whitelisted path not found, skipping: $path"
    continue
  fi
  cp -R "$path" "$TARGET_DIR/"
  echo "  added   $path"
done

for pattern in "${PRUNE_NAMES[@]}"; do
  find "$TARGET_DIR" -name "$pattern" -delete 2>/dev/null || true
done

find "$TARGET_DIR" -name ".git*" -prune -exec rm -rf {} + 2>/dev/null || true

rm -f "$OUT_FILE"
(cd "$STAGING" && zip -rq "$OUT_FILE" "$PACKAGE_NAME")

echo
echo "Wrote $OUT_FILE"
echo

# ---------------------------------------------------------------------------
# Verification.
# ---------------------------------------------------------------------------
FORBIDDEN_PATTERN='(^|/)(tests|docs|bin|build|\.github|node_modules)/|(^|/)\.git|phpstan|phpunit|\.zip$'
LEAKED="$(unzip -Z1 "$OUT_FILE" | sed "s|^$PACKAGE_NAME/||" | grep -nE "$FORBIDDEN_PATTERN" || true)"

if [[ -n "$LEAKED" ]]; then
  echo "REFUSING TO SHIP: internal files found in the archive:"
  echo "$LEAKED"
  rm -f "$OUT_FILE"
  exit 1
fi

echo "Verified clean. Contents:"
unzip -Z1 "$OUT_FILE" | sed "s|^$PACKAGE_NAME/||" | awk -F/ 'NF>1 {print $1"/"} NF==1 && $1!="" {print $1}' | sort -u | sed 's/^/  /'
echo
echo "Files: $(unzip -Z1 "$OUT_FILE" | grep -cv '/$')"
exit 0
