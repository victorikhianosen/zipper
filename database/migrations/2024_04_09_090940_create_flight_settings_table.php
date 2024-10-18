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
        Schema::create('flight_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name')->default('Zippa');
            $table->string('company_phone')->default('08012345678');
            $table->string('addressee_first_name')->default('John');
            $table->string('addressee_last_name')->default('Raheem');
            $table->string('country_calling_code')->default('234');
            $table->string('email_address_for_amadeus')->default('coy@email.com');
            $table->string('company_address')->default('Lagos');
            $table->string('postal_code')->default('000');
            $table->string('city_name')->default('Lagos');
            $table->char('country_code', 4)->default('NG');
            $table->enum('flight_ticket_option', ['CONFIRM', 'DELAY_TO_QUEUE', 'DELAY_TO_CANCEL'])->default('DELAY_TO_CANCEL');
            $table->string('delay')->comment('No_of_day_to_delay')->default('6');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_settings');
    }
};
