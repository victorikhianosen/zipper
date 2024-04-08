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
        Schema::create('book_flights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->nullable()->constrained()->nullOnDelete();
            $table->string('flight_order_ref');
            $table->unsignedDouble('flight_cost')->default(0);

            $table->string('departure_port')->nullable();
            $table->string('arrival_port')->nullable();
            $table->json('locations')->nullable();
            $table->json('flight_offers')->nullable();
            $table->json('passengers')->nullable();
            $table->json('remarks')->nullable();
            $table->json('ticketing_agreement')->nullable();
            $table->json('dictionaries')->nullable();
            $table->json('search_meta')->nullable();

            $table->dateTime('departure_time')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_flights');
    }
};
