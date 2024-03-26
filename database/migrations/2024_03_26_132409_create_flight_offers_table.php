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
        Schema::create('flight_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('search_id')->nullable();
            $table->json('itineraries')->nullable();
            $table->json('price')->nullable();
            $table->json('pricing_option')->nullable();
            $table->json('traveler_pricing')->nullable();
            $table->json('locations')->nullable();
            $table->json('raw_offer_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_offers');
    }
};
