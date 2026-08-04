<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('firstname', 'users_firstname_index');
            $table->index('lastname', 'users_lastname_index');
            $table->index('username', 'users_username_index');
            $table->index('phone', 'users_phone_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_firstname_index');
            $table->dropIndex('users_lastname_index');
            $table->dropIndex('users_username_index');
            $table->dropIndex('users_phone_index');
        });
    }
};
