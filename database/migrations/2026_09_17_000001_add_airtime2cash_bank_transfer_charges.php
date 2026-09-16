<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions')) {
            return;
        }

        Schema::table('airtime2_cash_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('airtime2_cash_transactions', 'bank_transfer_fee')) {
                $table->double('bank_transfer_fee', 11, 2)->default(0)->after('amount_paid');
            }
            if (! Schema::hasColumn('airtime2_cash_transactions', 'bank_transfer_amount')) {
                $table->double('bank_transfer_amount', 11, 2)->nullable()->after('bank_transfer_fee');
            }
            if (! Schema::hasColumn('airtime2_cash_transactions', 'bank_transfer_charge_breakdown')) {
                $table->json('bank_transfer_charge_breakdown')->nullable()->after('bank_transfer_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('airtime2_cash_transactions')) {
            return;
        }

        Schema::table('airtime2_cash_transactions', function (Blueprint $table): void {
            foreach (['bank_transfer_charge_breakdown', 'bank_transfer_amount', 'bank_transfer_fee'] as $column) {
                if (Schema::hasColumn('airtime2_cash_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
