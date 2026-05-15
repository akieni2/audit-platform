<?php

namespace App\Services\Realtime;

use App\Models\User;
use Illuminate\Notifications\Notification;

class RuntimeNotificationDispatcher
{
    public function __construct(private RuntimeBroadcastService $broadcast) {}

    public function dispatch(User $user, Notification $notification): void
    {
        $user->notify($notification);

        if ($this->broadcast->shouldBroadcast()) {
            // WebSocket-ready hook — no mandatory driver
        }
    }
}
