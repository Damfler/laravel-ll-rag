<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            // public     — виден всем авторизованным
            // restricted — только участникам назначенных групп + owner + admin
            // private    — только owner + admin
            $table->string('visibility')->default('public')->after('color');
        });

        // Переносим данные из is_public → visibility
        DB::table('spaces')->where('is_public', false)->update(['visibility' => 'private']);

        Schema::table('spaces', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('color');
        });

        DB::table('spaces')->where('visibility', 'public')->update(['is_public' => true]);
        DB::table('spaces')->where('visibility', '!=', 'public')->update(['is_public' => false]);

        Schema::table('spaces', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
