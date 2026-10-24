<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code', 3)->unique();
                $table->boolean('bank_verification_enabled')->default(false);
                $table->unsignedBigInteger('bank_verification_provider_id')->nullable();
                $table->timestamps();
            });
        }

        $nigeriaId = DB::table('countries')->where('code', 'NG')->value('id');

        if (! $nigeriaId) {
            $nigeriaId = DB::table('countries')->insertGetId([
                'name' => 'Nigeria',
                'code' => 'NG',
                'bank_verification_enabled' => true,
                'bank_verification_provider_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('countries')->where('id', $nigeriaId)->update([
                'bank_verification_enabled' => true,
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasColumn('users', 'country_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('country_id')->nullable()->after('id');
            });
        }

        DB::table('users')->whereNull('country_id')->update(['country_id' => $nigeriaId]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'country_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('country_id');
            });
        }

        Schema::dropIfExists('countries');
    }
};
