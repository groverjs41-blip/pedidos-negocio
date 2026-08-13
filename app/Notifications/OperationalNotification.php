<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $orderId,
        public string $orderNumber,
        public string $action,
        public string $title,
        public string $message,
        public string $url,
        public ?string $soundType = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'action' => $this->action,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'sound_type' => $this->soundType,
        ];
    }
}
