<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cargo_agency_id')->nullable()->after('credit_days')
                ->constrained('cargo_agencies')->nullOnDelete();
            $table->string('shipping_method', 20)->nullable()->after('cargo_agency_id');
            $table->foreignId('destination_country_id')->nullable()->after('shipping_method')
                ->constrained('countries')->nullOnDelete();
            $table->string('destination_city')->nullable()->after('destination_country_id');
            $table->string('destination_airport')->nullable()->after('destination_city');
            $table->string('destination_port')->nullable()->after('destination_airport');
            $table->text('marking')->nullable()->after('destination_port');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_agency_id');
            $table->dropConstrainedForeignId('destination_country_id');
            $table->dropColumn([
                'shipping_method',
                'destination_city',
                'destination_airport',
                'destination_port',
                'marking',
            ]);
        });
    }
};
