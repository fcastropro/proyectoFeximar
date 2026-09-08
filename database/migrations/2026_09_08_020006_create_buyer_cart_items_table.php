<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_cart_id')->constrained('buyer_carts')->cascadeOnDelete();
            $table->foreignId('farm_product_availability_id')->constrained('farm_product_availabilities')->restrictOnDelete();
            $table->foreignId('box_type_id')->constrained('box_types')->restrictOnDelete();
            $table->unsignedInteger('boxes');
            $table->decimal('unit_price_snapshot', 12, 4);
            $table->unsignedInteger('stems_per_box_snapshot');
            $table->unsignedInteger('total_stems');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->unique(
                ['buyer_cart_id', 'farm_product_availability_id', 'box_type_id'],
                'buyer_cart_items_unique_line'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_cart_items');
    }
};
