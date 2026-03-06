<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->string('title');
            $table->json('content')->nullable();
            $table->text('content_text')->nullable();
            $table->string('change_summary')->nullable();
            $table->unsignedSmallInteger('version_number');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_versions');
    }
};
