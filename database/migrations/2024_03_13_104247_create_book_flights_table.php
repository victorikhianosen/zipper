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
        Schema::create('flights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('flight_order_ref');
            $table->string('from');
            $table->string('to');
            $table->unsignedDouble('flight_cost')->default(0);
            $table->json('flight_offers')->nullable();

            $table->string('departure_port')->nullable();
            $table->string('remarks')->nullable();
            $table->string('ticketing_agreement')->nullable();
            $table->string('dictionaries')->nullable();
            $table->string('arrival_port')->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->dateTime('arrival_time')->nullable();
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
