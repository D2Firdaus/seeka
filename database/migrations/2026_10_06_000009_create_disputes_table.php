<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->string('dispute_id', 10)->primary();       // DSP001
            $table->string('claim_id', 10);
            $table->string('reported_by', 10);
            $table->text('reason');
            $table->enum('status', ['open', 'resolved', 'dismissed'])->default('open');
            $table->string('resolved_by', 10)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('claim_id')->references('claim_id')->on('claims')->cascadeOnDelete();
            $table->foreign('reported_by')->references('user_id')->on('users')->restrictOnDelete();
            $table->foreign('resolved_by')->references('user_id')->on('users')->nullOnDelete();

            $table->index(['claim_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
