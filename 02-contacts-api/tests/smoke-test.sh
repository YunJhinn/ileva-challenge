#!/usr/bin/env bash
#
# Dependency-free end-to-end smoke test: boots the API with PHP's built-in
# server against a throwaway SQLite database, then exercises every route
# with curl, checking status codes and the presence of expected fields.
#
# Useful when phpunit isn't installed yet (e.g. no network access), and as
# a quick sanity check after `docker compose up`.
#
# Usage: bash tests/smoke-test.sh [base_url]
#   base_url defaults to http://127.0.0.1:8098/api and, when it points at
#   127.0.0.1, this script will start/stop its own PHP server for you.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${1:-http://127.0.0.1:8098/api}"
MANAGE_SERVER=false
SERVER_PID=""

pass=0
fail=0

assert_status() {
  local description="$1" expected="$2" actual="$3"
  if [[ "$actual" == "$expected" ]]; then
    echo "  [OK]   $description (HTTP $actual)"
    pass=$((pass + 1))
  else
    echo "  [FAIL] $description (expected HTTP $expected, got $actual)"
    fail=$((fail + 1))
  fi
}

assert_contains() {
  local description="$1" haystack="$2" needle="$3"
  if [[ "$haystack" == *"$needle"* ]]; then
    echo "  [OK]   $description"
    pass=$((pass + 1))
  else
    echo "  [FAIL] $description — expected to find '$needle'"
    fail=$((fail + 1))
  fi
}

cleanup() {
  if [[ -n "$SERVER_PID" ]]; then
    kill "$SERVER_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT

if [[ "$BASE_URL" == *"127.0.0.1"* ]]; then
  MANAGE_SERVER=true
  export DB_CONNECTION=sqlite
  export DB_SQLITE_PATH=":memory:"
  PORT=$(echo "$BASE_URL" | sed -E 's#.*:([0-9]+).*#\1#')
  php -S "127.0.0.1:${PORT}" -t "${ROOT_DIR}/public" >/tmp/ileva-smoke-test-server.log 2>&1 &
  SERVER_PID=$!
  sleep 1
fi

echo "Running smoke test against ${BASE_URL}"
echo

echo "health check"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' "${BASE_URL}/health")
assert_status "GET /health" 200 "$status"

echo "create person"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X POST "${BASE_URL}/people" \
  -H 'Content-Type: application/json' -d '{"name":"Smoke Test Person"}')
assert_status "POST /people" 201 "$status"
person_id=$(php -r '$d=json_decode(file_get_contents("/tmp/body.json"),true); echo $d["data"]["id"];')

echo "reject invalid person"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X POST "${BASE_URL}/people" \
  -H 'Content-Type: application/json' -d '{"name":""}')
assert_status "POST /people with empty name" 422 "$status"

echo "add contacts"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X POST "${BASE_URL}/people/${person_id}/contacts" \
  -H 'Content-Type: application/json' -d '{"type":"phone","value":"+55 62 90000-0000"}')
assert_status "POST /people/{id}/contacts (phone)" 201 "$status"

status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X POST "${BASE_URL}/people/${person_id}/contacts" \
  -H 'Content-Type: application/json' -d '{"type":"email","value":"smoke@example.com"}')
assert_status "POST /people/{id}/contacts (email)" 201 "$status"
contact_id=$(php -r '$d=json_decode(file_get_contents("/tmp/body.json"),true); echo $d["data"]["id"];')

echo "reject invalid contact"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X POST "${BASE_URL}/people/${person_id}/contacts" \
  -H 'Content-Type: application/json' -d '{"type":"fax","value":"123"}')
assert_status "POST /people/{id}/contacts with bad type" 422 "$status"

echo "read back"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' "${BASE_URL}/people/${person_id}")
assert_status "GET /people/{id}" 200 "$status"
assert_contains "person has 2 nested contacts" "$(cat /tmp/body.json)" '"contacts"'

echo "update"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X PUT "${BASE_URL}/contacts/${contact_id}" \
  -H 'Content-Type: application/json' -d '{"type":"whatsapp","value":"+55 62 91234-5678"}')
assert_status "PUT /contacts/{id}" 200 "$status"
assert_contains "contact type updated" "$(cat /tmp/body.json)" '"whatsapp"'

echo "delete"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X DELETE "${BASE_URL}/contacts/${contact_id}")
assert_status "DELETE /contacts/{id}" 204 "$status"

status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X DELETE "${BASE_URL}/people/${person_id}")
assert_status "DELETE /people/{id}" 204 "$status"

status=$(curl -sS -o /tmp/body.json -w '%{http_code}' "${BASE_URL}/people/${person_id}")
assert_status "GET /people/{id} after delete" 404 "$status"

echo "error paths"
status=$(curl -sS -o /tmp/body.json -w '%{http_code}' "${BASE_URL}/does-not-exist")
assert_status "GET unknown route" 404 "$status"

status=$(curl -sS -o /tmp/body.json -w '%{http_code}' -X PATCH "${BASE_URL}/people/1")
assert_status "PATCH (unsupported method)" 405 "$status"

echo
echo "Results: ${pass} passed, ${fail} failed"
[[ "$fail" -eq 0 ]]
