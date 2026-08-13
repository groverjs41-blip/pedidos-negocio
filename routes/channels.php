<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.operations', function ($user) {
    return $user && $user->isActive() && $user->hasAnyRole(['admin', 'pedidos', 'cocina', 'reparto']);
});

Broadcast::channel('payments.operations', function ($user) {
    return $user && $user->isActive() && $user->hasAnyRole(['admin', 'caja', 'pedidos']);
});

Broadcast::channel('returnables.operations', function ($user) {
    return $user && $user->isActive() && $user->hasAnyRole(['admin', 'caja', 'reparto', 'pedidos']);
});

Broadcast::channel('closures.operations', function ($user) {
    return $user && $user->isActive() && $user->hasAnyRole(['admin', 'caja']);
});



