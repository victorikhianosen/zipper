<?php

namespace App\Services;

use App\Models\User;

class NotificationService
{
    public function togglePushNotification(User $user, string $deviceToken, string $action): void
    {
        $deviceTokens = $user->device_tokens ?? [];
        if ($action == 'on') {
            if (! in_array($deviceToken, $deviceTokens)) {
                $deviceTokens[] = $deviceToken;
                $user->device_tokens = $deviceTokens;
                $user->save();
            }
        } else {
            if (in_array($deviceToken, $deviceTokens)) {
                $user->device_tokens = array_values(array_diff($deviceTokens, [$deviceToken]));
                $user->save();
            }
        }
    }
}
