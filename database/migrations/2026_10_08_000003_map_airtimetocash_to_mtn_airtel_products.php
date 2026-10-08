<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('auto_share_product_providers')) {
            return;
        }

        $providerId = DB::table('apis')->where('slug', 'airtimetocash')->value('id');

        if (! $providerId) {
            return;
        }

        $products = DB::table('products')
            ->where('type', 'airtime2cash')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%mtn%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%airtel%']);
            })
            ->pluck('id');

        foreach ($products as $productId) {
            DB::table('auto_share_product_providers')->updateOrInsert(
                ['product_id' => $productId, 'api_id' => $providerId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        $providerId = DB::table('apis')->where('slug', 'airtimetocash')->value('id');

        if (! $providerId || ! Schema::hasTable('auto_share_product_providers')) {
            return;
        }

        $products = DB::table('products')
            ->where('type', 'airtime2cash')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%mtn%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%airtel%']);
            })
            ->pluck('id');

        DB::table('auto_share_product_providers')
            ->where('api_id', $providerId)
            ->whereIn('product_id', $products)
            ->delete();
    }
};
