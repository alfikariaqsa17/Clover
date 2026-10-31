<?php

namespace App\Support;

use App\Models\UserNotification;

class Notify
{
    public static function to(?int $userId, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        if (! $userId) {
            return;
        }
        UserNotification::create(['user_id' => $userId, 'type' => $type, 'title' => $title, 'body' => $body, 'url' => $url]);
    }
}
