<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_images', function (Blueprint $table) {
            $table->string('image_id', 10)->primary();         // IMG001
            $table->string('item_id', 10);
            $table->string('path');
            $table->timestamps();

            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_images');
    }
};
