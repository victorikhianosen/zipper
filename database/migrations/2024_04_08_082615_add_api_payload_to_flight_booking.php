<?php

use App\Enums\Status\FlightStatus;
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
        Schema::table('book_flights', function (Blueprint $table) {
            $table->after('search_meta', function ($table) {
                $table->json('request_body')->nullable();
                $table->string('status')->default(FlightStatus::PENDING->value);
                $table->string('tracking_status')->default(FlightStatus::UPCOMING->value);
                $table->string('payment_status')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_flights', function (Blueprint $table) {
            $table->dropColumn([
                'request_body',
                'status', 'tracking_status',
            ]);
        });
    }
};
