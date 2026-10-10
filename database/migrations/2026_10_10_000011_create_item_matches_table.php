<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hasil pencocokan lost <-> found dan status notifikasinya.
        // Aturan yang dijaga aplikasi (bukan database): lost_item_id harus items.type = 'lost',
        // found_item_id harus items.type = 'found', dan keduanya tidak boleh sama.
        Schema::create('item_matches', function (Blueprint $table) {
            $table->string('match_id', 10)->primary();         // MTC001
            $table->string('lost_item_id', 10);
            $table->string('found_item_id', 10);

            $table->decimal('score', 5, 4);                    // skor akhir gabungan (untuk mengurutkan)
            $table->decimal('sbert_cosine', 5, 4);             // kemiripan makna teks (untuk gerbang notifikasi)
            $table->decimal('bm25_norm', 5, 4)->nullable();    // BM25 ternormalisasi (fitur untuk regresi logistik)
            $table->decimal('metadata_score', 5, 4);           // kategori + lokasi + tanggal (untuk gerbang notifikasi)

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
