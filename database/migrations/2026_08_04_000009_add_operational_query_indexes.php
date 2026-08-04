<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'transaction_logs' => [
            'transaction_logs_status_created_at_index' => ['status', 'created_at'],
            'transaction_logs_transaction_id_index' => ['transaction_id'],
            'transaction_logs_customer_id_created_at_index' => ['customer_id', 'created_at'],
            'transaction_logs_account_number_created_at_index' => ['account_number', 'created_at'],
            'transaction_logs_wallet_provider_created_at_index' => ['wallet_funding_provider', 'created_at'],
        ],
        'wallets' => [
            'wallets_customer_id_created_at_index' => ['customer_id', 'created_at'],
            'wallets_transaction_id_type_index' => ['transaction_id', 'type'],
        ],
        'airtime2_cash_transactions' => [
            'a2c_type_status_created_at_index' => ['type', 'status', 'created_at'],
            'a2c_transaction_id_index' => ['transaction_id'],
            'a2c_customer_id_created_at_index' => ['customer_id', 'created_at'],
        ],
        'email_logs' => [
            'email_logs_status_created_at_index' => ['status', 'created_at'],
        ],
        'reserved_account_numbers' => [
            'reserved_accounts_customer_id_created_at_index' => ['customer_id', 'created_at'],
            'reserved_accounts_account_number_index' => ['account_number'],
        ],
        'reserved_account_callbacks' => [
            'reserved_callbacks_status_created_at_index' => ['status', 'created_at'],
            'reserved_callbacks_transaction_reference_index' => ['transaction_reference'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (!Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes, true) as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach (array_reverse($indexes, true) as $name => $columns) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
