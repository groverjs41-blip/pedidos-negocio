<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.operations', function ($user) {
    return $user && $user->isActive() && $user->hasAnyRole(['admin', 'pedidos', 'cocina', 'reparto']);
});
