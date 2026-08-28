<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKFILL_MARKER = 'backfill-orphan-airtime2cash-wallet-log-2026-08-28';

    public function up(): void
    {
        if (! Schema::hasTable('wallets') || ! Schema::hasTable('transaction_logs') || ! Schema::hasTable('airtime2_cash_transactions')) {
            return;
        }

        $columns = [
            'charge_breakdown' => Schema::hasColumn('transaction_logs', 'charge_breakdown'),
            'completed_at' => Schema::hasColumn('transaction_logs', 'completed_at'),
            'resolution_date' => Schema::hasColumn('transaction_logs', 'resolution_date'),
        ];

        $orphanWallets = DB::table('wallets as w')
            ->leftJoin('transaction_logs as tl', 'tl.transaction_id', '=', 'w.transaction_id')
            ->leftJoin('airtime2_cash_transactions as a2c', 'a2c.transaction_id', '=', 'w.transaction_id')
            ->leftJoin('customers as c', 'c.id', '=', 'w.customer_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('products as p', 'p.id', '=', 'a2c.product_id')
            ->whereNull('tl.id')
            ->where('w.reason', 'Airtime-to-cash conversion')
            ->where('w.transaction_id', 'like', 'A2C-%')
            ->select([
                'w.id as wallet_id',
                'w.customer_id',
                'w.transaction_id',
                'w.amount as wallet_amount',
                'w.balance_before as wallet_balance_before',
                'w.balance_after as wallet_balance_after',
                'w.created_at as wallet_created_at',
                'w.updated_at as wallet_updated_at',
                'a2c.id as a2c_id',
                'a2c.amount_charged',
                'a2c.amount_paid',
                'a2c.charge_rate',
                'a2c.total_amount as a2c_total_amount',
                'a2c.description as a2c_description',
                'a2c.product_id',
                'a2c.phone_numbers',
                'a2c.payment_method as a2c_payment_method',
                'a2c.status as a2c_status',
                'a2c.provider_id',
                'a2c.bank_name',
                'a2c.bank_code',
                'a2c.account_name',
                'a2c.account_number',
                'a2c.bank_transfer_api_response',
                'a2c.completed_at as a2c_completed_at',
                'u.firstname as customer_firstname',
                'u.middlename as customer_middlename',
                'u.lastname as customer_lastname',
                'u.email as customer_email',
                'u.phone as customer_phone',
                'p.name as product_name',
                'p.category_id as product_category_id',
            ])
            ->orderBy('w.created_at')
            ->get();

        if ($orphanWallets->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($orphanWallets, $columns): void {
            foreach ($orphanWallets as $row) {
                if (DB::table('transaction_logs')->where('transaction_id', $row->transaction_id)->exists()) {
                    continue;
                }

                if (blank($row->customer_email) || blank($row->customer_phone) || blank($row->customer_firstname) || blank($row->customer_lastname)) {
                    continue;
                }

                $amount = (float) ($row->amount_paid ?? $row->wallet_amount ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $status = strtolower((string) ($row->a2c_status ?? 'successful'));
                if (! in_array($status, ['success', 'successful', 'completed', 'approved', 'delivered'], true)) {
                    $status = 'successful';
                }

                $customerName = trim(implode(' ', array_filter([
                    $row->customer_firstname,
                    $row->customer_middlename,
                    $row->customer_lastname,
                ])));

                $payload = [
                    'status' => $status,
                    'reference_id' => $row->transaction_id,
                    'transaction_id' => $row->transaction_id,
                    'payment_method' => 'wallet',
                    'customer_id' => $row->customer_id,
                    'customer_email' => $row->customer_email,
                    'customer_name' => $customerName,
                    'customer_phone' => $row->customer_phone,
                    'unique_element' => 'Airtime2Cash Payment',
                    'discount' => 0,
                    'unit_price' => $amount,
                    'amount' => $amount,
                    'total_amount' => $amount,
                    'balance_before' => $row->wallet_balance_before,
                    'balance_after' => $row->wallet_balance_after,
                    'quantity' => 1,
                    'product_id' => $row->product_id,
                    'product_name' => $row->product_name,
                    'category_id' => $row->product_category_id,
                    'api_id' => $row->provider_id,
                    'reason' => 'Airtime2Cash Payment',
                    'descr' => 'Backfilled missing Airtime-to-cash wallet transaction log.',
                    'provider_charge' => $row->amount_charged,
                    'provider_discount' => 0,
                    'request_data' => json_encode([
                        'source' => self::BACKFILL_MARKER,
                        'wallet_id' => $row->wallet_id,
                        'airtime2cash_transaction_id' => $row->a2c_id,
                        'wallet_transaction_id' => $row->transaction_id,
                        'wallet_amount' => $row->wallet_amount,
                        'a2c_amount_paid' => $row->amount_paid,
                        'a2c_amount_charged' => $row->amount_charged,
                        'a2c_total_amount' => $row->a2c_total_amount,
                    ], JSON_THROW_ON_ERROR),
                    'api_response' => $row->bank_transfer_api_response,
                    'ip_address' => null,
                    'domain_name' => null,
                    'failure_reason' => null,
                    'app_version' => self::BACKFILL_MARKER,
                    'created_at' => $row->a2c_completed_at ?? $row->wallet_created_at ?? now(),
                    'updated_at' => $row->a2c_completed_at ?? $row->wallet_updated_at ?? now(),
                ];

                if ($columns['charge_breakdown']) {
                    $payload['charge_breakdown'] = json_encode([
                        'source' => self::BACKFILL_MARKER,
                        'amount_charged' => $row->amount_charged,
                        'charge_rate' => $row->charge_rate,
                    ], JSON_THROW_ON_ERROR);
                }

                if ($columns['completed_at']) {
                    $payload['completed_at'] = $row->a2c_completed_at ?? $row->wallet_created_at ?? now();
                }

                if ($columns['resolution_date']) {
                    $payload['resolution_date'] = $row->a2c_completed_at ?? $row->wallet_created_at ?? now();
                }

                DB::table('transaction_logs')->insert($payload);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_logs')) {
            return;
        }

        DB::table('transaction_logs')
            ->where('app_version', self::BACKFILL_MARKER)
            ->delete();
    }
};
