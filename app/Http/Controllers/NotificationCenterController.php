<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $notifications = $user->notifications()->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $user->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $user->unreadNotifications->markAsRead();

        return back()->with('status', 'Toutes les notifications ont été marquées comme lues.');
    }
}
