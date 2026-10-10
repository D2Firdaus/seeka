<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handovers', function (Blueprint $table) {
            $table->string('handover_id', 10)->primary();      // HND001
            $table->string('claim_id', 10)->unique();          // 1 klaim = 1 serah terima
            $table->string('handed_by', 10);                   // petugas penyerah -> users.user_id
            $table->string('received_by', 10);                 // penerima -> users.user_id
            $table->dateTime('handover_date');
            $table->string('photo')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('claim_id')->references('claim_id')->on('claims')->cascadeOnDelete();
            $table->foreign('handed_by')->references('user_id')->on('users')->restrictOnDelete();
            $table->foreign('received_by')->references('user_id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handovers');
    }
};
