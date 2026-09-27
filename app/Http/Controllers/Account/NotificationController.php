<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** Avisos dentro de Tinku. Al abrirlos quedan leídos; los nuevos se marcan en la lista. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->limit(50)->get();
        $unreadIds = $notifications->whereNull('read_at')->pluck('id')->all();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('account.notifications', ['notifications' => $notifications, 'unreadIds' => $unreadIds]);
    }
}
