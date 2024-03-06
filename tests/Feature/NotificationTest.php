<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as Notify;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->pushNotificationEnabled()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    public function testItReturnsUserNotifications(): void
    {
        Notification::factory(10)->create(['notifiable_id' => $this->user->id]);
        Notification::factory(5)->read()->create(['notifiable_id' => $this->user->id]);
        $response = $this->get('/notifications?type=all&page=1&per_page=10');
        $response->assertStatus(200);
        $this->assertEquals(
            ['page' => 1, 'per_page' => 10, 'total' => 15],
            $response->json()['meta']
        );
    }

    public function testItReturnsUserUnreadNotifications(): void
    {
        Notification::factory(5)->create(['notifiable_id' => $this->user->id]);
        Notification::factory(10)->read()->create(['notifiable_id' => $this->user->id]);
        $response = $this->get('/notifications?type=unread&page=1&per_page=10');
        $response->assertStatus(200);
        $this->assertEquals(
            ['page' => 1, 'per_page' => 10, 'total' => 5],
            $response->json()['meta']
        );
    }

    public function testItReturnsUserReadNotifications(): void
    {
        Notification::factory(10)->create(['notifiable_id' => $this->user->id]);
        Notification::factory(5)->read()->create(['notifiable_id' => $this->user->id]);
        $response = $this->get('/notifications?type=read&page=1&per_page=10');
        $response->assertStatus(200);
        $this->assertEquals(
            ['page' => 1, 'per_page' => 10, 'total' => 5],
            $response->json()['meta']
        );
    }

    public function testItReturnsASingleNotification(): void
    {
        Notification::factory()->create(['notifiable_id' => $this->user->id]);
        $notification = $this->user->notifications()->first();
        $response = $this->get("/notifications/$notification?->id/details");
        $response->assertStatus(200);
        $this->assertEquals($notification?->id, $response->json()['data']['id']);
        $this->assertEquals(true, $response->json()['data']['read']);
    }

    public function testItMarksNotificationAsRead(): void
    {
        Notification::factory()->create(['notifiable_id' => $this->user->id]);
        $notification = $this->user->notifications()->first();
        $response = $this->put("/notifications/$notification?->id/read");
        $response->assertStatus(200);
        $this->assertEquals(true, $response->json()['data']['read']);
    }

    public function testItMarksNotificationAsUnread(): void
    {
        Notification::factory()->read()->create(['notifiable_id' => $this->user->id]);
        $notification = $this->user->notifications()->first();
        $response = $this->put("/notifications/$notification?->id/unread");
        $response->assertStatus(200);
        $this->assertEquals(false, $response->json()['data']['read']);
    }

    public function testItMarksAllNotificationsAsRead(): void
    {
        Notification::factory(10)->create(['notifiable_id' => $this->user->id]);
        $response = $this->put('/notifications/read-all');
        $response->assertStatus(200);
        foreach ($this->user->notifications()->get() as $notification) {
            $this->assertNotNull($notification->read_at);
        }
    }

    /**
     * @dataProvider notificationChannels
     *
     * @param  array<string, mixed>  $notificationChannels
     */
    public function testItTriggersNotification(array $notificationChannels): void
    {
        Notify::fake();
        $title = fake()->sentence(5);
        $body = fake()->sentence(30);
        $response = $this->postJson('/notifications/trigger', [
            'title' => $title,
            'body' => $body,
            'channels' => $notificationChannels,
        ]);
        $response->assertStatus(200);
        Notify::assertSentTo(
            $this->user,
            AppNotification::class,
            function ($notification, $channels) use ($title, $body, $notificationChannels) {
                foreach ($notificationChannels as $channel) {
                    $this->assertContains($channel, $channels);
                }
                $this->assertEquals(strtolower($title), strtolower($notification->toArray($this->user)['title']));
                $this->assertEquals($body, $notification->toArray($this->user)['body']);

                return true;
            }
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function notificationChannels(): array
    {
        return [
            'push' => [
                ['firebase'],
            ],
            'email' => [
                ['mail'],
            ],
            'in_app' => [
                ['database'],
            ],
            'all' => [
                ['firebase', 'mail', 'database'],
            ],
        ];
    }
}
