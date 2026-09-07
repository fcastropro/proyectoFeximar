<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_product_presentations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('box_type_id');
            $table->dropColumn([
                'bunches_per_box',
                'stems_per_box',
                'available_boxes',
                'price_per_box',
            ]);

            $table->unsignedInteger('available_stems')->nullable()->after('stems_per_bunch');
        });

        // Si existían filas duplicadas por longitud (antes separadas por caja), conservar una.
        $duplicates = DB::table('farm_product_presentations')
            ->select('farm_product_id', 'stem_length_cm', DB::raw('MIN(id) as keep_id'))
            ->groupBy('farm_product_id', 'stem_length_cm')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('farm_product_presentations')
                ->where('farm_product_id', $duplicate->farm_product_id)
                ->where('stem_length_cm', $duplicate->stem_length_cm)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('farm_product_presentations', function (Blueprint $table) {
            $table->unique(['farm_product_id', 'stem_length_cm']);
        });
    }

    public function down(): void
    {
        Schema::table('farm_product_presentations', function (Blueprint $table) {
            $table->dropUnique(['farm_product_id', 'stem_length_cm']);
            $table->dropColumn('available_stems');

            $table->foreignId('box_type_id')
                ->nullable()
                ->after('farm_product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('bunches_per_box')->nullable()->after('stems_per_bunch');
            $table->unsignedInteger('stems_per_box')->nullable()->after('bunches_per_box');
            $table->unsignedInteger('available_boxes')->default(0)->after('stems_per_box');
            $table->decimal('price_per_box', 12, 2)->nullable()->after('price_per_bunch');
        });
    }
};
