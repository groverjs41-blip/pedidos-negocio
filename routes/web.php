<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
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

    // Placeholder routes for operational modules
    Route::get('/pedidos/nuevo', [HomeController::class, 'pedidosPlaceholder']);
    Route::get('/cocina', [HomeController::class, 'cocinaPlaceholder']);
    Route::get('/reparto', [HomeController::class, 'repartoPlaceholder']);
    Route::get('/caja', [HomeController::class, 'cajaPlaceholder']);
});
