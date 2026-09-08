<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('operator'); // manager|operator
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['farm_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_users');
    }
};
