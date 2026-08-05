#!/usr/bin/env bash
# Aborts a release when the version carriers disagree, before anything is published.
set -euo pipefail

VERSION="${VERSION:-}"
if [[ -z "$VERSION" ]]; then
  VERSION="$(head -n1 version.txt | tr -d '[:space:]')"
fi
if [[ -z "$VERSION" ]]; then
  echo "ERROR: VERSION ist nicht gesetzt und version.txt ist leer" >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

# 1) version.txt - what release-please bumps
TXT_VERSION="$(head -n1 "$ROOT_DIR/version.txt" | tr -d '[:space:]')"

# 2) readme.txt Stable tag
README_VERSION="$(grep -E '^Stable tag:' "$ROOT_DIR/plugin/readme.txt" | head -n1 | sed -E 's/^Stable tag:[[:space:]]*//')"
if [[ -z "$README_VERSION" ]]; then
  echo "ERROR: Konnte 'Stable tag:' nicht in plugin/readme.txt finden" >&2
  exit 1
fi

# 3) the plugin header
PLUGIN_VERSION="$(grep -E '^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*[0-9]+\.[0-9]+\.[0-9]+' "$ROOT_DIR/plugin/ph-content-relations.php" \
  | head -n1 \
  | sed -E 's/.*Version:[[:space:]]*([0-9]+\.[0-9]+\.[0-9]+).*/\1/')"
if [[ -z "$PLUGIN_VERSION" ]]; then
  echo "ERROR: Konnte 'Version:' nicht in plugin/ph-content-relations.php finden" >&2
  exit 1
fi

# The development wrapper in the root is deliberately not a version carrier - it never
# ships, so its header version is decoration. (Learned from postqueue-feeds, where
# demanding it match once blocked a release.)

fail=0
check_eq () {
  local label="$1"; local got="$2"
  if [[ "$got" != "$VERSION" ]]; then
    echo "ERROR: ${label} ist $got, erwartet $VERSION" >&2; fail=1
  else
    echo "OK: ${label} == $VERSION"
  fi
}

check_eq "version.txt" "$TXT_VERSION"
check_eq "readme.txt Stable tag" "$README_VERSION"
check_eq "Plugin-Header Version" "$PLUGIN_VERSION"

if [[ "$fail" -ne 0 ]]; then
  echo "Release-Version-Check fehlgeschlagen." >&2
  exit 1
fi
echo "Alle Versionen passen ✅"
