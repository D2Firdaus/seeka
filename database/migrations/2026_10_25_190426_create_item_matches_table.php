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
        Schema::create('item_matches', function (Blueprint $table) {
            $table->id('match_id');
            $table->foreignId('lost_item_id')->constrained('items', 'item_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('found_item_id')->constrained('items', 'item_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('jw_score', 6, 4)->nullable();
            $table->decimal('metadata_score', 6, 4)->nullable();
            $table->decimal('bgem3_sparse', 6, 4)->nullable();
            $table->decimal('bgem3_dense', 6, 4)->nullable();
            $table->decimal('bge_reranker', 8, 4)->nullable();
            $table->decimal('final_score', 6, 4)->nullable();
            $table->string('pipeline_version', 50);
            $table->enum('status', ['candidate', 'new', 'notified', 'viewed', 'dismissed'])->default('candidate');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['lost_item_id', 'found_item_id'], 'item_matches_lost_found_unique');
            $table->index(['lost_item_id', 'status', 'final_score'], 'item_matches_lost_status_score_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_matches');
    }
};
