<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationUnreadController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
        ]);
    }
}
