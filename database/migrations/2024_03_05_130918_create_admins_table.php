<?php

use App\Enums\Status\AdminStatus;
use App\Models\Admin;
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
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_code')->nullable();
            $table->string('phone')->nullable();
            $table->text('avatar')->nullable();
            $table->timestamp('two_factor_enabled_at')->nullable();
            $table->string('password');
            $table->string('status')->default(AdminStatus::ACTIVE->value)->comment('Enums: AdminStatus');
            $table->text('fcm_token')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expiry')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Admin::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@zippatravels.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
