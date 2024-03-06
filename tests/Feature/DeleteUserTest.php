<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function testThatItDeletesUserAccount(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->delete('/delete-account');

        $response->assertStatus(200);
        $response->assertSee('Account deleted successfully');
        $this->assertSoftDeleted('users', ['email' => $user->email]);
    }
}
