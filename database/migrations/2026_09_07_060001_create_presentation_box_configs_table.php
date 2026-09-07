<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentation_box_configs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_product_presentation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('box_type_id')
                ->constrained('box_types')
                ->restrictOnDelete();

            $table->unsignedInteger('stems_per_box');
            $table->unsignedInteger('bunches_per_box')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(
                ['farm_product_presentation_id', 'box_type_id'],
                'presentation_box_configs_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentation_box_configs');
    }
};
