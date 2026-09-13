<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions')
            || ! Schema::hasColumns('airtime2_cash_transactions', ['transaction_id', 'provider_request_ref', 'provider_reference'])) {
            return;
        }

        // Before the fields were separated, provider_request_ref contained
        // AutoSync's external reference. Preserve it as provider_reference
        // and move the local request reference into provider_request_ref.
        DB::table('airtime2_cash_transactions')
            ->whereNotNull('provider_request_ref')
            ->orderBy('id')
            ->chunkById(200, function ($transactions): void {
                foreach ($transactions as $transaction) {
                    $updates = [];
                    $requestReference = (string) $transaction->provider_request_ref;
                    $looksLikeLocalReference = str_starts_with($requestReference, 'A2C-')
                        || $requestReference === (string) $transaction->transaction_id;

                    if (blank($transaction->provider_reference)) {
                        if ($looksLikeLocalReference) {
                            $response = is_array($transaction->provider_response)
                                ? $transaction->provider_response
                                : json_decode((string) $transaction->provider_response, true);
                            $providerReference = is_array($response)
                                ? ($response['data']['transaction']['reference']
                                    ?? $response['transaction']['reference']
                                    ?? null)
                                : null;

                            if (filled($providerReference)) {
                                $updates['provider_reference'] = $providerReference;
                            }
                        } else {
                            $updates['provider_reference'] = $requestReference;
                        }
                    }

                    if (! $looksLikeLocalReference) {
                        $updates['provider_request_ref'] = $transaction->transaction_id;
                    }

                    if ($updates !== []) {
                        DB::table('airtime2_cash_transactions')
                            ->where('id', $transaction->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // The previous field meanings cannot be restored safely after the
        // external and local references have been separated.
    }
};
