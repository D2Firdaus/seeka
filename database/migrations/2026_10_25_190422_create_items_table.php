<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('category_id')->constrained('categories', 'category_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained('locations', 'location_id')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('type', ['lost', 'found']);
            $table->string('title');
            $table->text('description');
            $table->text('hidden_detail');
            $table->date('date_event');
            $table->enum('status', ['open', 'claimed', 'returned', 'closed'])->default('open');
            $table->string('storage_location')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'date_event'], 'items_type_status_date_event_index');

            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText(['title', 'description'], 'items_title_description_fulltext');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
