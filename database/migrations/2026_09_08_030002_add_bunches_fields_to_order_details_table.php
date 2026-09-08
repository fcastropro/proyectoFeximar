<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedInteger('bunches')->nullable()->after('box_type_id');
            $table->unsignedInteger('stems_per_bunch')->nullable()->after('bunches');
            $table->decimal('price_per_stem', 12, 4)->nullable()->after('total_stems');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['bunches', 'stems_per_bunch', 'price_per_stem']);
        });
    }
};
