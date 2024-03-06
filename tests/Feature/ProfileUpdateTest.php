<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use ImageKit\ImageKit;
use Laravel\Sanctum\Sanctum;
use Tests\Fakes\FakeImageKit;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public User $user;

    /** @var array<string, string> */
    public array $passwordPayload = [
        'old_password' => 'password',
        'password' => 'newpassword',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['avatar' => asset('oldAvatar.png')]);
        Sanctum::actingAs($this->user, ['*']);
        $this->app->instance(ImageKit::class, new FakeImageKit());
    }

    // public function testItSetsUserProfilePhoto(): void
    // {
    //     $this->app->instance(ImageKit::class, new FakeImageKit());
    //     $file = UploadedFile::fake()->image('avatar.png');
    //     $response = $this->postJson('/profile/update-avatar', ['avatar' => $file]);
    //     $response->assertStatus(200);
    //     $response->assertJson(['message' => 'Profile photo updated successfully.']);
    //     print_r($response->json());
    //     $this->assertEquals(asset('avatar.png'), $this->user->avatar);
    // }

    // public function testItUpdatesUserProfilePhoto(): void
    // {
    //     $this->app->instance(ImageKit::class, new FakeImageKit());
    //     $this->assertEquals(asset('oldAvatar.png'), $this->user->avatar);
    //     $file = UploadedFile::fake()->image('avatar.png');
    //     $response = $this->postJson('/profile/update-avatar', ['avatar' => $file]);
    //     $response->assertStatus(200);
    //     $this->assertEquals(asset('avatar.png'), $this->user->avatar);
    // }

    public function testItUpdatesUserProfile(): void
    {
        $payload = [
            'first_name' => 'Goke',
            'last_name' => 'Adewuyi',
            'phone' => '09060040819',
        ];
        $response = $this->postJson('/profile/update', $payload);
        $response->assertStatus(200);
        $response->assertSee('Profile updated successfully');
        $payload['id'] = $this->user->id;
        $this->assertDatabaseHas('users', $payload);
    }

    /**
     * @dataProvider failProfileValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToUpdatesUserProfileDueToInvalidInputs(array $inputData): void
    {
        $response = $this->postJson('/profile/update', $inputData);
        $response->assertStatus(422);
    }

    public function testItUpdatesUserPasswordAndSendsInAppAndPushNotifications(): void
    {
        Notification::fake();
        $response = $this->postJson('/profile/update-password', $this->passwordPayload);
        $response->assertStatus(200);
        $response->assertSee('Password updated successfully');
        $this->assertTrue(Hash::check('newpassword', $this->user->refresh()->password));
        Notification::assertSentTo($this->user, AppNotification::class, function ($notification, $channels) {
            $this->assertContains('database', $channels);
            $this->assertContains('firebase', $channels);
            $this->assertEquals(
                strtolower('password updated'),
                strtolower($notification->toArray($this->user)['title'])
            );

            return true;
        });
    }

    /**
     * @dataProvider failPasswordValidationInputs
     *
     * @param  array<string, mixed>  $inputData
     */
    public function testItFailsToUpdatesUserPasswordDueToInvalidInputs(array $inputData): void
    {
        $response = $this->postJson('/profile/update-password', $inputData);
        $response->assertStatus(422);
    }

    /**
     * @return array<string, mixed>
     */
    public static function failProfileValidationInputs(): array
    {
        return [
            'no_data' => [
                [],
            ],
            'no_phone' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function failPasswordValidationInputs(): array
    {
        return [
            'no_data' => [
                [],
            ],
            'no_old_password' => [
                [
                    'password' => 'password',
                ],
            ],
            'no_new_password' => [
                [
                    'old_password' => 'Doe',
                ],
            ],
            'invalid_old_password' => [
                [
                    'old_password' => 'invalidpassword',
                    'password' => 'newpassword',
                ],
            ],
            'short_new_password' => [
                [
                    'old_password' => 'password',
                    'password' => 'pass',
                ],
                [
                    'old_password' => 'password',
                    'password' => 'passw',
                ],
                [
                    'old_password' => 'password',
                    'password' => 'passwo',
                ],
                [
                    'old_password' => 'password',
                    'password' => 'passwor',
                ],
            ],
        ];
    }
}
