<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// GANTI file bawaan Laravel dengan nama yang sama (0001_01_01_000000_create_users_table.php).
// PK users berubah dari id (angka) menjadi user_id (kode, mis. USR001),
// jadi sessions.user_id juga harus string.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id', 10)->primary();          // USR001
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('nim_nip', 30)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->boolean('notify_email')->default(true);        // preferensi kanal notifikasi
            $table->boolean('notify_whatsapp')->default(false);
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 10)->nullable()->index();   // sebelumnya foreignId (angka)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
