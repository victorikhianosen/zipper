<?php

use App\Enums\AuthType;
use App\Enums\Status\UserStatus;
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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('gender')->nullable()->comment('Enums: Gender');
            $table->string('ref_code')->nullable();
            $table->string('password');
            $table->string('status')->default(UserStatus::ACTIVE->value);
            $table->string('auth_type')->default(AuthType::PASSWORD->value)->comment('Enums: AuthType');
            $table->text('device_tokens')->nullable();
            $table->text('avatar')->nullable();
            $table->string('verification_otp')->nullable();
            $table->timestamp('verification_otp_expiry')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('two_factor_enabled_at')->nullable();
            $table->timestamp('login_biometric_activated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
