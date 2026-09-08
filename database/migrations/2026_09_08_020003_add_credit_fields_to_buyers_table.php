<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->boolean('credit_allowed')->default(false)->after('active');
            $table->unsignedSmallInteger('credit_days_default')->nullable()->after('credit_allowed');
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn(['credit_allowed', 'credit_days_default']);
        });
    }
};
