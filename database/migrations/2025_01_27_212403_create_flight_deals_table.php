<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flight_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('search_id', 255)->nullable();
            $table->longText('itineraries')->nullable();
            $table->json('price')->nullable();  // Change to 'json' instead of 'longText'
            $table->longText('pricing_option')->nullable();
            $table->longText('traveler_pricing')->nullable();
            $table->longText('locations')->nullable();
            $table->longText('dictionaries')->nullable();
            $table->longText('search_meta');
            $table->longText('raw_offer_response')->nullable();
            $table->char('saved_by')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('image_link')->nullable();
            $table->timestamps();


            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_deals');
    }
};
