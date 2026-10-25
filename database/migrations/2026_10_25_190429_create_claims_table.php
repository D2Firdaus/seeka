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
        Schema::create('claims', function (Blueprint $table) {
            $table->id('claim_id');
            $table->foreignId('found_item_id')->constrained('items', 'item_id')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('claimant_id')->constrained('users', 'user_id')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('lost_item_id')->nullable()->constrained('items', 'item_id')->restrictOnDelete()->restrictOnUpdate();
            $table->text('proof_description');
            $table->string('proof_image')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users', 'user_id')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamp('verified_at')->nullable();
            $table->text('admin_note')->nullable();

            $table->unsignedBigInteger('approved_item_id')
                ->nullable()
                ->storedAs("case when status = 'approved' then found_item_id else null end");

            $table->timestamps();

            $table->unique('approved_item_id', 'claims_approved_item_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
