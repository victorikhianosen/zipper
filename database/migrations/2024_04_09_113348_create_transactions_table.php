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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('offer_id');
            $table->foreignUuid('book_flight_id')->index()->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique()->index();
            $table->unsignedDouble('amount')->default(0);
            $table->string('description')->nullable();
            $table->string('service_type')->index();
            $table->char('transaction_type', 2)->index();
            $table->string('category');
            $table->string('gateway');
            $table->string('status');
            $table->json('meta')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
