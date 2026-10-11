<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pasangan lost <-> found yang LOLOS threshold (keluaran skenario 5) dan status notifikasinya.
        // Skor skenario 1-4 tidak disimpan di sini; skenario itu dibandingkan di skrip evaluasi offline.
        // Aturan yang dijaga aplikasi (bukan database): lost_item_id harus items.type = 'lost',
        // found_item_id harus items.type = 'found', dan keduanya tidak boleh sama.
        Schema::create('item_matches', function (Blueprint $table) {
            $table->string('match_id', 10)->primary();         // MTC001
            $table->string('lost_item_id', 10);
            $table->string('found_item_id', 10);

            // Komponen tahap 1
            $table->decimal('jw_score', 5, 4);                 // Jaro-Winkler pada judul (skenario 1)
            $table->decimal('metadata_score', 5, 4);           // kategori + lokasi + tanggal (skenario 1)
            $table->decimal('bgem3_dense', 5, 4);              // cosine dense BGE-M3 (skenario 3)
            $table->decimal('bgem3_sparse', 5, 4)->nullable(); // skor sparse BGE-M3 (skenario 2); NULL sampai sparse
                                                               // diimplementasikan di website. Skalanya BUKAN cosine.
            // Skor akhir
            $table->decimal('bge_reranker', 5, 4);             // keluaran reranker (sigmoid 0-1): dasar urutan dan threshold
            $table->string('pipeline_version', 50);            // skenario, model, bobot, dan threshold yang dipakai

            $table->enum('status', ['new', 'notified', 'viewed', 'dismissed'])->default('new');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->foreign('lost_item_id')->references('item_id')->on('items')->cascadeOnDelete();
            $table->foreign('found_item_id')->references('item_id')->on('items')->cascadeOnDelete();

            $table->unique(['lost_item_id', 'found_item_id']); // satu pasangan hanya sekali
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_matches');
    }
};
