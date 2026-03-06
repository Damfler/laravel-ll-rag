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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->json('content')->nullable();         // TipTap JSON
            $table->text('content_text')->nullable();    // Plain text для FTS и RAG
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('depth')->default(0);   // уровень вложенности (0 = корень)
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['space_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
