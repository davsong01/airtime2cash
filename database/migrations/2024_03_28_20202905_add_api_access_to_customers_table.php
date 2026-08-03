<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'api_access')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('api_access')->default('inactive');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'api_access')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn("api_access")->nullable();
            }); 
        }
    }
};
