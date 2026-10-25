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
        Schema::create('item_embeddings', function (Blueprint $table) {
            $table->id('embedding_id');
            $table->foreignId('item_id')->constrained('items', 'item_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('field', ['title', 'description']);
            $table->string('model_name', 100);
            $table->char('text_hash', 64);
            $table->binary('embedding');
            $table->timestamps();

            $table->unique(['item_id', 'field', 'model_name'], 'item_embeddings_item_field_model_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_embeddings');
    }
};
