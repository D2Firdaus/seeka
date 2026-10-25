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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('match_id')->nullable()->constrained('item_matches', 'match_id')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('channel', ['email', 'whatsapp']);
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['match_id', 'user_id', 'channel'], 'notifications_match_user_channel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
