<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit trail: hanya di-insert, tidak diedit (maka tanpa updated_at)
        Schema::create('item_logs', function (Blueprint $table) {
            $table->string('log_id', 10)->primary();           // LOG001
            $table->string('item_id', 10);
            $table->string('user_id', 10);
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('note')->nullable();
            $table->string('ip_address', 45)->nullable();      // muat IPv4 maupun IPv6
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->restrictOnDelete();

            $table->index(['item_id', 'created_at']);          // riwayat satu barang, urut waktu
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_logs');
    }
};
