<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    if ($user && $user->isActive()) {
        return $user->hasRole('admin')
            ? redirect('/admin')
            : redirect('/inicio');
    }

    return redirect()->route('login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated & active users routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/inicio', [HomeController::class, 'index'])->name('inicio');

    // Operational routes
    Route::get('/pedidos/nuevo', \App\Livewire\CreateOrder::class)->middleware('role:pedidos')->name('pedidos.nuevo');
    Route::get('/pedidos', \App\Livewire\ListOrders::class)->middleware('role:pedidos')->name('pedidos.index');
    Route::get('/pedidos/{order}/editar', \App\Livewire\EditOrder::class)->middleware('role:pedidos')->name('pedidos.edit');
    Route::get('/cocina', \App\Livewire\Kitchen::class)->middleware('role:cocina')->name('cocina');
    Route::get('/reparto', \App\Livewire\Delivery::class)->middleware('role:reparto')->name('reparto');
    // Cashier routes (role:caja or admin)
    Route::get('/caja', \App\Livewire\CashierDashboard::class)->middleware('role:caja')->name('caja.dashboard');
    Route::get('/caja/clientes/{customer}', \App\Livewire\CustomerAccount::class)->middleware('role:caja')->name('caja.cliente');
    Route::get('/caja/pedidos/{order}', \App\Livewire\PayOrder::class)->middleware('role:caja')->name('caja.pedido');
    Route::get('/caja/pagos', \App\Livewire\ListPayments::class)->middleware('role:caja')->name('caja.pagos');

});
