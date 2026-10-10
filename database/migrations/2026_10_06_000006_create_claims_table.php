<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->string('claim_id', 10)->primary();         // CLM001
            $table->string('item_id', 10);
            $table->string('claimant_id', 10);                 // pengaju klaim -> users.user_id
            $table->string('lost_item_id', 10)->nullable();    // laporan hilang milik pengaju (jika ada) -> items.item_id

            $table->text('proof_description');
            $table->string('proof_image')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->string('verified_by', 10)->nullable();     // admin verifikator -> users.user_id
            $table->timestamp('verified_at')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
            $table->foreign('claimant_id')->references('user_id')->on('users')->restrictOnDelete();
            $table->foreign('lost_item_id')->references('item_id')->on('items')->nullOnDelete();
            $table->foreign('verified_by')->references('user_id')->on('users')->nullOnDelete();

            $table->index(['item_id', 'status']);              // cek klaim pending per barang
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
