<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_product_availabilities', function (Blueprint $table) {
            $table->unsignedInteger('reserved_stems')->default(0)->after('available_stems');
        });
    }

    public function down(): void
    {
        Schema::table('farm_product_availabilities', function (Blueprint $table) {
            $table->dropColumn('reserved_stems');
        });
    }
};
