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
        Schema::create('app_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('version');
            $table->string('build_number');
            $table->string('store_link')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('platform');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_updates');
    }
};
