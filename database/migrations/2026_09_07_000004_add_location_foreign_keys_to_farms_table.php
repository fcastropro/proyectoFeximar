<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('province_id')->constrained()->nullOnDelete();

            $table->dropColumn(['city', 'province']);
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('country_id');

            $table->string('city')->nullable()->after('phone');
            $table->string('province')->nullable()->after('city');
        });
    }
};
