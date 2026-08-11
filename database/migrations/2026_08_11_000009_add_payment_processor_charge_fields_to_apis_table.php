<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (! Schema::hasColumn('apis', 'charge')) {
                $table->double('charge')->nullable()->after('is_payment_gateway');
            }

            if (! Schema::hasColumn('apis', 'reserved_account_payment_charge')) {
                $table->double('reserved_account_payment_charge')->nullable()->after('charge');
            }

            if (! Schema::hasColumn('apis', 'reserved_account_payment_charge_type')) {
                $table->string('reserved_account_payment_charge_type')->nullable()->after('reserved_account_payment_charge');
            }
        });

        $monnifyApiExists = DB::table('apis')
            ->where('id', 1)
            ->orWhere('slug', 'monnify')
            ->exists();

        if (! $monnifyApiExists) {
            return;
        }

        $legacyGateway = null;

        if (Schema::hasTable('payment_gateways')) {
            $legacyGateway = DB::table('payment_gateways')
                ->where('id', 1)
                ->first();
        }

        if (! $legacyGateway) {
            return;
        }

        DB::table('apis')
            ->where('id', 1)
            ->orWhere('slug', 'monnify')
            ->update([
                'charge' => $legacyGateway->charge ?? null,
                'reserved_account_payment_charge' => $legacyGateway->reserved_account_payment_charge ?? null,
                'reserved_account_payment_charge_type' => $legacyGateway->reserved_account_payment_charge_type ?? null,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('apis')) {
            return;
        }

        Schema::table('apis', function (Blueprint $table) {
            if (Schema::hasColumn('apis', 'reserved_account_payment_charge_type')) {
                $table->dropColumn('reserved_account_payment_charge_type');
            }

            if (Schema::hasColumn('apis', 'reserved_account_payment_charge')) {
                $table->dropColumn('reserved_account_payment_charge');
            }

            if (Schema::hasColumn('apis', 'charge')) {
                $table->dropColumn('charge');
            }
        });
    }
};
