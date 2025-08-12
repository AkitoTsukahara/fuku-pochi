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
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->onDelete('cascade');
            $table->foreignId('clothing_category_id')->constrained('clothing_categories')->onDelete('cascade');
            $table->integer('current_count')->default(0)->unsigned();
            $table->timestamps();
            
            $table->unique(['child_id', 'clothing_category_id']);
            $table->index('child_id');
            $table->index('clothing_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
