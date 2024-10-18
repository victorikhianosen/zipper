<?php

use App\Enums\Status\AlertStatus;
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
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->constrained('admins');
            $table->string('title');
            $table->longText('body');
            $table->string('status')->default(AlertStatus::PENDING->value)->comment('Enum:AlertStatus');
            $table->string('target');
            $table->unsignedBigInteger('user_count')->nullable();
            $table->timestamp('dispatched_at');
            $table->json('channels');
            $table->text('failed_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
