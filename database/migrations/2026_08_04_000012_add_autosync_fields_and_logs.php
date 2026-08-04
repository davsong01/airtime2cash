<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'auto_share_provider_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedBigInteger('auto_share_provider_id')->nullable();
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'auto_share_product_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('auto_share_product_code')->nullable();
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumns('products', ['id', 'name', 'type', 'auto_share_product_code'])) {
            DB::table('products')
                ->where('type', 'airtime2cash')
                ->whereNull('auto_share_product_code')
                ->select(['id', 'name'])
                ->orderBy('id')
                ->each(function (object $product) {
                    $name = Str::lower($product->name);
                    $code = match (true) {
                        Str::contains($name, 'mtn') => 'mtn',
                        Str::contains($name, 'airtel') => 'airtel',
                        Str::contains($name, '9mobile'), Str::contains($name, 'etisalat') => '9mobile',
                        Str::contains($name, 'glo') => 'glo',
                        default => Str::slug($product->name),
                    };

                    DB::table('products')->where('id', $product->id)->update([
                        'auto_share_product_code' => $code,
                    ]);
                });
        }

        if (Schema::hasTable('airtime2_cash_transactions')) {
            $columns = [
                'provider_reference' => fn (Blueprint $table) => $table->string('provider_reference')->nullable(),
                'provider_request_ref' => fn (Blueprint $table) => $table->string('provider_request_ref')->nullable(),
                'provider_status' => fn (Blueprint $table) => $table->string('provider_status')->nullable(),
                'provider_response' => fn (Blueprint $table) => $table->longText('provider_response')->nullable(),
                'completed_at' => fn (Blueprint $table) => $table->timestamp('completed_at')->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (!Schema::hasColumn('airtime2_cash_transactions', $column)) {
                    Schema::table('airtime2_cash_transactions', $definition);
                }
            }
        }

        if (!Schema::hasTable('autosync_api_logs')) {
            Schema::create('autosync_api_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('transaction_id')->nullable()->index();
                $table->string('operation')->index();
                $table->string('method', 10)->default('POST');
                $table->text('endpoint');
                $table->longText('request_headers')->nullable();
                $table->longText('request_payload')->nullable();
                $table->unsignedSmallInteger('response_status')->nullable()->index();
                $table->longText('response_headers')->nullable();
                $table->longText('response_body')->nullable();
                $table->text('error')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('autosync_webhooks')) {
            Schema::create('autosync_webhooks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('transaction_id')->nullable()->index();
                $table->string('provider_reference')->nullable()->index();
                $table->string('request_ref')->nullable()->index();
                $table->string('provider_status')->nullable()->index();
                $table->string('processing_status')->default('pending')->index();
                $table->boolean('signature_valid')->default(false)->index();
                $table->longText('headers')->nullable();
                $table->longText('payload');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('a_p_is') && Schema::hasColumns('a_p_is', ['name', 'slug', 'status'])) {
            $local = app()->environment('local');
            DB::table('a_p_is')->updateOrInsert(
                ['slug' => 'autosync'],
                [
                    'name' => 'AutoSync',
                    'description' => 'AutoSyncNG Airtime to Cash integration',
                    'status' => $local ? 'active' : 'inactive',
                    'api_key' => $local ? 'autosync_dummy_api_key' : null,
                    'secret_key' => $local ? 'autosync_dummy_access_token' : null,
                    'public_key' => $local ? '1234' : null,
                    'live_base_url' => 'https://autosyncng.com/api/v1',
                    'sandbox_base_url' => null,
                    'file_name' => 'AutoSyncService',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'auto_share_provider_id')) {
                $providerId = DB::table('a_p_is')->where('slug', 'autosync')->value('id');
                DB::table('settings')->whereNull('auto_share_provider_id')->update(['auto_share_provider_id' => $providerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('autosync_webhooks');
        Schema::dropIfExists('autosync_api_logs');

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'auto_share_provider_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('auto_share_provider_id');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'auto_share_product_code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('auto_share_product_code');
            });
        }

        if (!Schema::hasTable('airtime2_cash_transactions')) {
            return;
        }

        $columns = collect([
            'provider_reference',
            'provider_request_ref',
            'provider_status',
            'provider_response',
            'completed_at',
        ])->filter(fn (string $column) => Schema::hasColumn('airtime2_cash_transactions', $column))->all();

        if ($columns) {
            Schema::table('airtime2_cash_transactions', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
