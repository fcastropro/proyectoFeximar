<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renombra quantity → boxes y agrega snapshot de tallos por línea.
     */
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->renameColumn('quantity', 'boxes');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedInteger('stems_per_box')
                ->default(0)
                ->after('boxes');

            $table->unsignedInteger('total_stems')
                ->default(0)
                ->after('stems_per_box');
        });

        $details = DB::table('order_details')->get(['id', 'farm_product_availability_id', 'box_type_id', 'boxes']);

        foreach ($details as $detail) {
            $availability = DB::table('farm_product_availabilities')
                ->where('id', $detail->farm_product_availability_id)
                ->first(['farm_product_presentation_id']);

            if (! $availability) {
                continue;
            }

            $config = DB::table('presentation_box_configs')
                ->where('farm_product_presentation_id', $availability->farm_product_presentation_id)
                ->where('box_type_id', $detail->box_type_id)
                ->first(['stems_per_box']);

            $stemsPerBox = (int) ($config->stems_per_box ?? 0);

            DB::table('order_details')->where('id', $detail->id)->update([
                'stems_per_box' => $stemsPerBox,
                'total_stems' => (int) $detail->boxes * $stemsPerBox,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['stems_per_box', 'total_stems']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->renameColumn('boxes', 'quantity');
        });
    }
};
