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
        Schema::create('item_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('item_id')->constrained('items', 'item_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('old_status', ['open', 'claimed', 'returned', 'closed']);
            $table->enum('new_status', ['open', 'claimed', 'returned', 'closed']);
            $table->text('note')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_logs');
    }
};
