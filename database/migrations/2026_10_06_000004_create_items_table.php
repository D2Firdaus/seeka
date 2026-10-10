<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->string('item_id', 10)->primary();          // ITM001
            $table->string('user_id', 10);                     // pelapor
            $table->string('category_id', 10);
            $table->string('location_id', 10);

            $table->enum('type', ['lost', 'found']);
            $table->string('title');
            $table->text('description');
            $table->text('hidden_detail')->nullable();         // ciri rahasia untuk verifikasi, tidak ditampilkan publik
            $table->date('date_event');                        // tanggal hilang / ditemukan
            $table->enum('status', ['open', 'claimed', 'returned', 'closed'])->default('open');
            $table->string('storage_location')->nullable();    // tempat barang found disimpan

            $table->timestamps();
            $table->softDeletes();

            // Foreign key (kolom FK otomatis ter-index di MySQL/InnoDB)
            $table->foreign('user_id')->references('user_id')->on('users')->restrictOnDelete();
            $table->foreign('category_id')->references('category_id')->on('categories')->restrictOnDelete();
            $table->foreign('location_id')->references('location_id')->on('locations')->restrictOnDelete();

            // Index tambahan
            $table->index(['type', 'status', 'date_event']);   // query kandidat pencarian/pencocokan: jenis + status + rentang tanggal
            $table->index('date_event');                       // urut terbaru
            $table->fullText(['title', 'description']);        // pencarian kata kunci
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
