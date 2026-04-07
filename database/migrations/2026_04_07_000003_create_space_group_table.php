<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('space_group', function (Blueprint $table) {
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->primary(['space_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_group');
    }
};
