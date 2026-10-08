<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('auto_share_product_providers')) {
            Schema::create('auto_share_product_providers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('api_id');
                $table->timestamps();

                $table->unique(['product_id', 'api_id']);
                $table->index('api_id');
            });
        }

        $autosyncId = DB::table('apis')->where('slug', 'autosync')->value('id');
        $products = DB::table('products')
            ->where('type', 'airtime2cash')
            ->get(['id', 'auto_share_provider_id']);

        foreach ($products as $product) {
            $providerIds = collect([$autosyncId, $product->auto_share_provider_id])
                ->filter()
                ->unique()
                ->values();

            foreach ($providerIds as $providerId) {
                DB::table('auto_share_product_providers')->updateOrInsert(
                    [
                        'product_id' => $product->id,
                        'api_id' => $providerId,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        if (Schema::hasColumn('products', 'auto_share_provider_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('auto_share_provider_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('auto_share_product_providers')) {
            return;
        }

        Schema::drop('auto_share_product_providers');
    }
};
