<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_product_presentations', function (Blueprint $table) {
            $table->dropColumn('available_stems');
        });
    }

    public function down(): void
    {
        Schema::table('farm_product_presentations', function (Blueprint $table) {
            $table->unsignedInteger('available_stems')->nullable()->after('stems_per_bunch');
        });
    }
};
