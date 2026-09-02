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
        Schema::create('farm_product_presentations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('box_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedSmallInteger('stem_length_cm');

            $table->unsignedInteger('stems_per_bunch')->nullable();
            $table->unsignedInteger('bunches_per_box')->nullable();
            $table->unsignedInteger('stems_per_box')->nullable();

            $table->unsignedInteger('available_boxes')->default(0);

            $table->decimal('price_per_stem', 10, 4)->nullable();
            $table->decimal('price_per_bunch', 10, 2)->nullable();
            $table->decimal('price_per_box', 12, 2)->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_product_presentations');
    }
};
