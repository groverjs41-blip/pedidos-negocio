<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $today = now()->toDateString();
        
        $totalOrders = Order::whereDate('ordered_at', $today)->count();
        $newOrders = Order::whereDate('ordered_at', $today)->where('status', OrderStatus::NEW)->count();
        $preparingOrders = Order::whereDate('ordered_at', $today)->where('status', OrderStatus::PREPARING)->count();
        $readyOrders = Order::whereDate('ordered_at', $today)->where('status', OrderStatus::READY)->count();
        $deliveringOrders = Order::whereDate('ordered_at', $today)->where('status', OrderStatus::DELIVERING)->count();
        $deliveredOrders = Order::whereDate('ordered_at', $today)->where('status', OrderStatus::DELIVERED)->count();

        return [
            Stat::make('Pedidos de Hoy', $totalOrders)
                ->description('Total registrados hoy')
                ->color('info'),
            Stat::make('Nuevos', $newOrders)
                ->description('Esperando en cola')
                ->color('info'),
            Stat::make('Preparando', $preparingOrders)
                ->description('En cocina')
                ->color('warning'),
            Stat::make('Listos', $readyOrders)
                ->description('Listos para repartir')
                ->color('success'),
            Stat::make('En Reparto', $deliveringOrders)
                ->description('En camino')
                ->color('primary'),
            Stat::make('Entregados', $deliveredOrders)
                ->description('Completados hoy')
                ->color('success'),
        ];
    }
}
