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
        Schema::create('farm_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('price', 10, 4)->nullable();
            $table->integer('available_quantity')->default(0);
            $table->string('stem_length')->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(['farm_id', 'product_id', 'stem_length']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_products');
    }
};
