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
        Schema::table('flight_offers', function (Blueprint $table) {
            $table->foreignUuid('saved_by')->index()->nullable()->after('raw_offer_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flight_offers', function (Blueprint $table) {
            $table->dropColumn(['saved_by']);
        });
    }
};
