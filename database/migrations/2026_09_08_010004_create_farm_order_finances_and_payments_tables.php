<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_order_finances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_condition')->default('cash'); // cash|credit
            $table->unsignedSmallInteger('credit_days')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending'); // pending|partial|paid|overdue
            $table->timestamps();

            $table->unique(['order_id', 'farm_id']);
        });

        Schema::create('farm_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_order_finance_id')
                ->constrained('farm_order_finances')
                ->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_payments');
        Schema::dropIfExists('farm_order_finances');
    }
};
