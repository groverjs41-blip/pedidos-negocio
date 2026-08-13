<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    if ($user && $user->isActive()) {
        return redirect('/inicio');
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

    // Mobile Menu Page
    Route::get('/menu', \App\Livewire\MenuPage::class)->name('menu');

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

    // Returnable container routes (role:caja,reparto or admin)
    Route::get('/tazas', \App\Livewire\ReturnableDashboard::class)->middleware('role:caja,reparto')->name('tazas.dashboard');
    Route::get('/tazas/por-recoger', \App\Livewire\ReturnablePending::class)->middleware('role:caja,reparto')->name('tazas.por-recoger');
    Route::get('/tazas/clientes/{customer}', \App\Livewire\CustomerReturnables::class)->middleware('role:caja,reparto')->name('tazas.cliente');

    // Daily closure routes (role:caja or admin)
    Route::get('/cierre', \App\Livewire\DailyClosureDashboard::class)->middleware('role:caja')->name('cierre.dashboard');

    // Admin Management routes (role:admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/gestion', \App\Livewire\ManageHub::class)->name('gestion.hub');

        // Products
        Route::get('/gestion/productos', \App\Livewire\ManageProducts::class)->name('gestion.productos');
        Route::get('/gestion/productos/nuevo', \App\Livewire\CreateProduct::class)->name('gestion.productos.nuevo');
        Route::get('/gestion/productos/{product}/editar', \App\Livewire\EditProduct::class)->name('gestion.productos.editar');

        // Categories
        Route::get('/gestion/categorias', \App\Livewire\ManageCategories::class)->name('gestion.categorias');

        // Customers
        Route::get('/gestion/clientes', \App\Livewire\ManageCustomers::class)->name('gestion.clientes');
        Route::get('/gestion/clientes/nuevo', \App\Livewire\CreateCustomer::class)->name('gestion.clientes.nuevo');
        Route::get('/gestion/clientes/{customer}', \App\Livewire\CustomerDetail::class)->name('gestion.clientes.detalle');
        Route::get('/gestion/clientes/{customer}/editar', \App\Livewire\EditCustomer::class)->name('gestion.clientes.editar');

        // Users
        Route::get('/gestion/usuarios', \App\Livewire\ManageUsers::class)->name('gestion.usuarios');
        Route::get('/gestion/usuarios/nuevo', \App\Livewire\CreateUser::class)->name('gestion.usuarios.nuevo');
        Route::get('/gestion/usuarios/{user}/editar', \App\Livewire\EditUser::class)->name('gestion.usuarios.editar');

        // Returnable Types
        Route::get('/gestion/envases', \App\Livewire\ManageReturnableTypes::class)->name('gestion.envases');

        // Business Settings
        Route::get('/gestion/configuracion', \App\Livewire\BusinessSettingsForm::class)->name('gestion.configuracion');
    });
});
