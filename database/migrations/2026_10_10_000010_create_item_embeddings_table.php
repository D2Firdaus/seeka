<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cache embedding SBERT per laporan per model, supaya tidak dihitung ulang tiap pencarian.
        Schema::create('item_embeddings', function (Blueprint $table) {
            $table->string('embedding_id', 10)->primary();     // EMB001
            $table->string('item_id', 10);
            $table->string('model_name', 100);                 // nama model SBERT yang dipakai
            $table->char('text_hash', 64);                     // hash teks sumber; berubah -> embed ulang
            $table->binary('embedding');                       // vektor float32 (BLOB)
            $table->timestamps();

            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();

            $table->unique(['item_id', 'model_name']);         // satu embedding per laporan per model
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_embeddings');
    }
};
