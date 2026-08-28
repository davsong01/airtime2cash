#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
EMAIL="${EMAIL:?Set EMAIL to a test customer email}"
PASSWORD="${PASSWORD:?Set PASSWORD to the test password}"
W2B_PRODUCT_ID="${W2B_PRODUCT_ID:-37}"
BANK_CODE="${BANK_CODE:?Set BANK_CODE to an active bank CBN code}"
ACCOUNT_NUMBER="${ACCOUNT_NUMBER:?Set ACCOUNT_NUMBER to a test account number}"
ACCOUNT_NAME="${ACCOUNT_NAME:?Set ACCOUNT_NAME to a test account name}"
AMOUNT_A="${AMOUNT_A:-60}"
AMOUNT_B="${AMOUNT_B:-30}"
TRANSFER_MODE="${TRANSFER_MODE:-manual}"

COOKIE_JAR="$(mktemp)"
RESPONSE_A="$(mktemp)"
RESPONSE_B="$(mktemp)"

cleanup() {
    rm -f "$COOKIE_JAR" "$RESPONSE_A" "$RESPONSE_B"
}

trap cleanup EXIT

log() {
    printf '%s\n' "$*" >&2
}

extract_csrf_token() {
    curl -fsS -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE_URL/login" \
        | perl -ne 'if (/name="_token" value="([^"]+)"/) { print $1; exit }'
}

login() {
    local csrf_token="$1"

    curl -fsS -L -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
        -H 'Content-Type: application/x-www-form-urlencoded' \
        --data-urlencode "_token=$csrf_token" \
        --data-urlencode "email=$EMAIL" \
        --data-urlencode "password=$PASSWORD" \
        --data-urlencode "remember=1" \
        "$BASE_URL/login" >/dev/null
}

post_wallet_to_bank() {
    local amount="$1"
    local outfile="$2"

    curl -sS -L -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
        -H 'Content-Type: application/x-www-form-urlencoded' \
        -X POST "$BASE_URL/customer-initialize-wallet2banktransaction/$W2B_PRODUCT_ID" \
        --data-urlencode "_token=$CSRF_TOKEN" \
        --data-urlencode "amount=$amount" \
        --data-urlencode "bank=$BANK_CODE" \
        --data-urlencode "account_number=$ACCOUNT_NUMBER" \
        --data-urlencode "account_name=$ACCOUNT_NAME" \
        --data-urlencode "transfer_mode=$TRANSFER_MODE" \
        -w '\nHTTP_STATUS:%{http_code}\n' \
        >"$outfile"
}

CSRF_TOKEN="$(extract_csrf_token)"
login "$CSRF_TOKEN"

log "Session authenticated against $BASE_URL"
log "Firing parallel wallet-to-bank requests: $AMOUNT_A and $AMOUNT_B"

post_wallet_to_bank "$AMOUNT_A" "$RESPONSE_A" &
PID_A=$!

post_wallet_to_bank "$AMOUNT_B" "$RESPONSE_B" &
PID_B=$!

wait "$PID_A"
wait "$PID_B"

log ""
log "Response A ($AMOUNT_A):"
cat "$RESPONSE_A"

log ""
log "Response B ($AMOUNT_B):"
cat "$RESPONSE_B"
