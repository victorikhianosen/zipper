<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->pushNotificationEnabled()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    public function testItTogglesPushNotificationOn(): void
    {
        $token = Str::random(100);
        $response = $this->postJson('/push-notification/on', [
            'device_token' => $token,
        ]);
        $response->assertStatus(200);
        $response->assertSee('Push notification enabled');
        $this->assertContains($token, $this->user->refresh()->device_tokens ?? []);
    }

    public function testItTogglesPushNotificationOff(): void
    {
        $token = Str::random(100);
        $this->postJson('/push-notification/on', [
            'device_token' => $token,
        ]);
        $this->assertContains($token, $this->user->refresh()->device_tokens ?? []);
        $response = $this->postJson('/push-notification/off', [
            'device_token' => $token,
        ]);
        $response->assertStatus(200);
        $response->assertSee('Push notification disabled');
        $this->assertNotContains($token, $this->user->refresh()->device_tokens ?? []);
    }

    public function testItReturnsPushNotificationStatus(): void
    {
        $token = Str::random(100);
        $this->postJson('/push-notification/on', [
            'device_token' => $token,
        ]);
        $this->assertContains($token, $this->user->refresh()->device_tokens ?? []);
        $response = $this->getJson('/push-notification/status?device_token='.$token);
        $response->assertStatus(200);
        $response->assertSee('push_notification_enabled');
        $this->assertEquals(true, $response->json()['data']['push_notification_enabled']);
    }

    public function testItSendsPushNotification(): void
    {
        Notification::fake();
        $title = fake()->sentence(5);
        $body = fake()->sentence(100);
        $this->user->notify(new AppNotification($title, $body, ['firebase']));
        Notification::assertSentTo(
            $this->user,
            AppNotification::class,
            function ($notification, $channels) use ($title, $body) {
                $this->assertContains('firebase', $channels);
                $this->assertEquals(strtolower($title), strtolower($notification->toArray($this->user)['title']));
                $this->assertEquals($body, $notification->toArray($this->user)['body']);

                return true;
            }
        );
    }
}
