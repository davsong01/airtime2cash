<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        foreach ($this->indexes() as $name => $column) {
            if (Schema::hasColumn('users', $column) && !Schema::hasIndex('users', $name)) {
                Schema::table('users', fn (Blueprint $table) => $table->index($column, $name));
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        foreach (array_keys($this->indexes()) as $name) {
            if (Schema::hasIndex('users', $name)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropIndex($name));
            }
        }
    }

    private function indexes(): array
    {
        return [
            'users_firstname_index' => 'firstname',
            'users_lastname_index' => 'lastname',
            'users_username_index' => 'username',
            'users_phone_index' => 'phone',
        ];
    }
};
