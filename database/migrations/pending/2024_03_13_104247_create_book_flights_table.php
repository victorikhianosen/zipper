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
            $table->string('flight_number');
            $table->foreignUuid('passenger_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('flight_class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('airline_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from');
            $table->string('to');
            $table->unsignedDouble('flight_cost')->default(0);
            $table->string('departure_port');
            $table->string('arrival_port');
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');
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
