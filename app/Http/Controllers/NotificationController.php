<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationTriggerRequest;
use App\Http\Requests\PushNotificationSubscriptionRequest;
use App\Http\Resources\NotificationResource;
use App\Notifications\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function getUserNotifications(Request $request): JsonResponse
    {
        $notifications = match ($request->type) {
            'read' => $this->authUser()->readNotifications(),
            'all' => $this->authUser()->notifications(),
            default => $this->authUser()->unreadNotifications(),
        };
        $resource = $this->paginate($notifications->latest());

        return $this->success(
            data: NotificationResource::collection($resource['data']),
            meta: $resource['meta']
        );
    }

    public function getNotification(DatabaseNotification $notification): JsonResponse
    {
        if (! $notification->read_at) {
            $notification->markAsRead();
            $notification->refresh();
        }

        return $this->success(data: new NotificationResource($notification));
    }

    public function markNotificationAsUnread(DatabaseNotification $notification): JsonResponse
    {
        if ($notification->read_at) {
            $notification->markAsUnread();
            $notification->refresh();
        }

        return $this->success(data: new NotificationResource($notification));
    }

    public function markAllNotificationAsRead(): JsonResponse
    {
        foreach ($this->authUser()->unreadNotifications()->get() as $notification) {
            $notification->markAsRead();
        }

        return $this->success();
    }

    public function togglePushNotification(PushNotificationSubscriptionRequest $request, string $action): JsonResponse
    {
        $this->notificationService->togglePushNotification(
            $this->authUser(),
            $request->device_token,
            $action,
        );

        return $this->success('Push notification '.($action == 'on' ? 'enabled' : 'disabled'));
    }

    public function getPushNotificationStatus(PushNotificationSubscriptionRequest $request): JsonResponse
    {
        return $this->success(data: ['push_notification_enabled' => in_array(
            $request->device_token,
            $this->authUser()->device_tokens ?? []
        )]);
    }

    public function triggerTestNotification(NotificationTriggerRequest $request): JsonResponse
    {
        $this->authUser()->notify(new AppNotification(
            $request->title ?? 'Test notification',
            $request->body ?? fake()->sentence(10),
            $request->channels ?? ['firebase']
        ));

        return $this->success('Notification sent');
    }
}
