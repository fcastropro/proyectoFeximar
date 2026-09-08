<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_farm_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('stems_reserved')->default(false);
            $table->timestamps();

            $table->unique(['order_id', 'farm_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_farm_fulfillments');
    }
};
