<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_product_availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_product_presentation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('week_number');
            $table->unsignedInteger('available_stems')->default(0);
            $table->decimal('price_per_stem', 10, 4)->nullable();
            $table->decimal('price_per_bunch', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(
                ['farm_product_presentation_id', 'year', 'week_number'],
                'farm_product_availabilities_unique_week'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_product_availabilities');
    }
};
