<?php

namespace App\Services;

use App\Events\OrderChanged;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OperationalNotification;

class OperationalNotificationService
{
    protected OperationalNotificationPreferenceService $prefService;

    public function __construct(?OperationalNotificationPreferenceService $prefService = null)
    {
        $this->prefService = $prefService ?? app(OperationalNotificationPreferenceService::class);
    }

    /**
     * Notify relevant users and broadcast event for an operational order state change based on per-user preferences.
     */
    public function notifyOrderStatusChange(Order $order, string $action, ?string $previousStatus = null): void
    {
        $order->loadMissing('items');

        $config = $this->getNotificationConfig($order, $action);
        if (!$config) {
            return;
        }

        $title = $config['title'];
        $message = $config['message'];
        $url = $config['url'];
        $soundType = $config['sound_type'];

        // Get recipients dynamically from OperationalNotificationPreferenceService
        $recipients = $this->prefService->getRecipients($action);
        $targetUserIds = $recipients['targetUserIds'];
        $soundUserIds = $recipients['soundUserIds'];
        $browserUserIds = $recipients['browserUserIds'];

        if (!empty($targetUserIds)) {
            $users = User::whereIn('id', $targetUserIds)->where('active', true)->get();

            $notification = new OperationalNotification(
                orderId: (string) $order->id,
                orderNumber: $order->number,
                action: $action,
                title: $title,
                message: $message,
                url: $url,
                soundType: $soundType
            );

            foreach ($users as $user) {
                // Deduplicate: avoid creating duplicate notification if one already exists for this order & action
                $exists = $user->notifications()
                    ->where('data->order_id', (string) $order->id)
                    ->where('data->action', $action)
                    ->exists();

                if (!$exists) {
                    $user->notify($notification);
                }
            }
        }

        // Broadcast OrderChanged event over Reverb with user ID arrays
        event(new OrderChanged(
            order: $order,
            previousStatus: $previousStatus,
            action: $action,
            soundType: $soundType,
            targetRoles: [],
            targetUserIds: $targetUserIds,
            soundUserIds: $soundUserIds,
            browserUserIds: $browserUserIds,
            originUserId: auth()->id()
        ));
    }

    /**
     * Build title, message, url, and sound type based on action type.
     */
    private function getNotificationConfig(Order $order, string $action): ?array
    {
        if ($order->service_mode === \App\Enums\ServiceMode::DIRECT && in_array($action, ['ORDER_CREATED', 'PREPARING'])) {
            return null;
        }

        $customer = $order->customer_name_snapshot ?? 'Venta Mostrador';

        $items = [];
        foreach ($order->items as $item) {
            $items[] = "{$item->quantity} × {$item->product_name}";
        }
        $summary = implode(', ', $items);

        return match ($action) {
            'ORDER_CREATED' => [
                'title' => 'NUEVO PEDIDO',
                'message' => "Pedido {$order->number} • {$customer} ({$summary})",
                'url' => '/cocina',
                'sound_type' => 'kitchen',
            ],
            'PREPARING' => [
                'title' => 'Pedido en preparación',
                'message' => "Pedido {$order->number} está siendo preparado",
                'url' => '/cocina',
                'sound_type' => null,
            ],
            'READY' => [
                'title' => 'PEDIDO LISTO PARA RECOGER',
                'message' => "Pedido {$order->number} • {$customer}",
                'url' => '/reparto',
                'sound_type' => 'delivery',
            ],
            'DELIVERING' => [
                'title' => 'Pedido en reparto',
                'message' => "Pedido {$order->number} en camino",
                'url' => '/pedidos',
                'sound_type' => null,
            ],
            'DELIVERED' => [
                'title' => 'Pedido entregado',
                'message' => "Pedido {$order->number} entregado",
                'url' => '/caja',
                'sound_type' => 'delivery',
            ],
            'CANCELLED' => [
                'title' => 'Pedido cancelado',
                'message' => "Pedido {$order->number} cancelado",
                'url' => '/pedidos',
                'sound_type' => null,
            ],
            default => null,
        };
    }
}
