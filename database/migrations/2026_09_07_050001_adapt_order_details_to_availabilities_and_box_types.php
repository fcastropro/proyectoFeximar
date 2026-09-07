<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adapta order_details al modelo de pedidos basado en disponibilidad semanal.
     *
     * No elimina datos. Si existen filas, la migración se detiene para forzar
     * una migración/limpieza manual explícita.
     */
    public function up(): void
    {
        if (Schema::hasTable('order_details') && DB::table('order_details')->exists()) {
            throw new \RuntimeException(
                'No se puede adaptar order_details porque existen detalles de pedidos. Migre o elimine esos datos explícitamente antes de continuar.'
            );
        }

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('farm_product_presentation_id');
            $table->dropColumn('boxes');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('farm_product_availability_id')
                ->after('order_id')
                ->constrained('farm_product_availabilities')
                ->restrictOnDelete();

            $table->foreignId('box_type_id')
                ->after('farm_product_availability_id')
                ->constrained('box_types')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity')
                ->after('box_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('order_details') && DB::table('order_details')->exists()) {
            throw new \RuntimeException(
                'No se puede revertir order_details porque existen detalles de pedidos. Migre o elimine esos datos explícitamente antes de continuar.'
            );
        }

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('farm_product_availability_id');
            $table->dropConstrainedForeignId('box_type_id');
            $table->dropColumn('quantity');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('farm_product_presentation_id')
                ->after('order_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('boxes')->after('farm_product_presentation_id');
        });
    }
};
