<?php

namespace App\Services;

use App\Events\OrderChanged;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OperationalNotification;

class OperationalNotificationService
{
    /**
     * Notify relevant users and broadcast event for an operational order state change.
     */
    public function notifyOrderStatusChange(Order $order, string $action, ?string $previousStatus = null): void
    {
        $order->loadMissing('items');

        $config = $this->getNotificationConfig($order, $action);
        if (!$config) {
            return;
        }

        $targetRoles = $config['target_roles'];
        $title = $config['title'];
        $message = $config['message'];
        $url = $config['url'];
        $soundType = $config['sound_type'];

        // Find users with target roles who are active
        $users = User::where('active', true)
            ->whereHas('roles', function ($query) use ($targetRoles) {
                $query->whereIn('slug', $targetRoles);
            })
            ->get();

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

        // Broadcast OrderChanged event over Reverb
        event(new OrderChanged(
            order: $order,
            previousStatus: $previousStatus,
            action: $action,
            soundType: $soundType,
            targetRoles: $targetRoles
        ));
    }

    /**
     * Build title, message, url, sound, and target roles based on action type.
     */
    private function getNotificationConfig(Order $order, string $action): ?array
    {
        $customer = $order->customer_name_snapshot ?? 'Venta Mostrador';

        $items = [];
        foreach ($order->items as $item) {
            $items[] = "{$item->quantity} × {$item->product_name}";
        }
        $summary = implode(', ', $items);

        return match ($action) {
            'ORDER_CREATED' => [
                'target_roles' => ['admin', 'cocina'],
                'title' => 'NUEVO PEDIDO',
                'message' => "Pedido {$order->number} • {$customer} ({$summary})",
                'url' => '/cocina',
                'sound_type' => 'kitchen',
            ],
            'PREPARING' => [
                'target_roles' => ['admin', 'cocina', 'pedidos'],
                'title' => 'Pedido en preparación',
                'message' => "Pedido {$order->number} está siendo preparado",
                'url' => '/cocina',
                'sound_type' => null,
            ],
            'READY' => [
                'target_roles' => ['admin', 'reparto', 'pedidos', 'caja', 'cocina'],
                'title' => 'PEDIDO LISTO PARA RECOGER',
                'message' => "Pedido {$order->number} • {$customer}",
                'url' => '/reparto',
                'sound_type' => 'delivery',
            ],
            'DELIVERING' => [
                'target_roles' => ['admin', 'pedidos'],
                'title' => 'Pedido en reparto',
                'message' => "Pedido {$order->number} en camino",
                'url' => '/pedidos',
                'sound_type' => null,
            ],
            'DELIVERED' => [
                'target_roles' => ['admin', 'caja'],
                'title' => 'Pedido entregado',
                'message' => "Pedido {$order->number} entregado",
                'url' => '/caja',
                'sound_type' => null,
            ],
            'CANCELLED' => [
                'target_roles' => ['admin', 'cocina', 'reparto'],
                'title' => 'Pedido cancelado',
                'message' => "Pedido {$order->number} cancelado",
                'url' => '/pedidos',
                'sound_type' => null,
            ],
            default => null,
        };
    }
}
