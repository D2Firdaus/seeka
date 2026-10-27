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
        Schema::create('handovers', function (Blueprint $table) {
            $table->id('handover_id');
            $table->foreignId('claim_id')->constrained('claims', 'claim_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('handed_by')->constrained('users', 'user_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('received_by')->constrained('users', 'user_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('handover_date');
            $table->string('photo')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique('claim_id', 'handovers_claim_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handovers');
    }
};
