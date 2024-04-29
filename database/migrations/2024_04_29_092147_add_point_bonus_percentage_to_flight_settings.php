<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flight_settings', function (Blueprint $table) {
            $table->unsignedDouble('point_bonus_percentage')->default(0)->after('delay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flight_settings', function (Blueprint $table) {
            $table->dropColumn(['point_bonus_percentage']);
        });
    }
};
