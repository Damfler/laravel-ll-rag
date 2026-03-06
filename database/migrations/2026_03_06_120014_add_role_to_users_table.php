<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Добавляем поле default_role для быстрых проверок (без join).
     * Полноценная система ролей — через таблицу role_user.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_role')->default('viewer')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_role');
        });
    }
};
