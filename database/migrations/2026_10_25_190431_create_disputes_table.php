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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id('dispute_id');
            $table->foreignId('claim_id')->constrained('claims', 'claim_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('reported_by')->constrained('users', 'user_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->text('reason');
            $table->enum('status', ['open', 'resolved', 'dismissed'])->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users', 'user_id')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
