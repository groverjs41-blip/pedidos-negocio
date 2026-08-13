<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationCenter extends Component
{
    public bool $open = false;

    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();
        if ($user) {
            $user->notifications()->where('id', $notificationId)->first()?->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
    }

    public function render()
    {
        $user = Auth::user();
        $notifications = $user ? $user->notifications()->take(10)->get() : collect();
        $unreadCount = $user ? $user->unreadNotifications()->count() : 0;

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
}
