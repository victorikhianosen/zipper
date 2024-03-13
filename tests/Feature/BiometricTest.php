<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BiometricTest extends TestCase
{
    use RefreshDatabase;

    public function testItTogglesUserLoginBiometricsOn(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/biometrics/login');
        $response->assertStatus(200);
        $this->assertTrue($user->refresh()->login_biometric_activated_at !== null);
    }

    public function testItTogglesUserLoginBiometricsOff(): void
    {
        $user = User::factory()->create(['login_biometric_activated_at' => now()]);
        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/biometrics/login');
        $response->assertStatus(200);
        $this->assertTrue($user->refresh()->login_biometric_activated_at === null);
    }
}
