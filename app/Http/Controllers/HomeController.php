<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the main start landing page.
     */
    public function index()
    {
        $user = Auth::user();
        return view('home.inicio', compact('user'));
    }

    /**
     * Placeholder for Toma de Pedidos.
     */
    public function pedidosPlaceholder()
    {
        $this->authorizeRole('pedidos');
        return view('home.placeholder', [
            'title' => 'Toma de Pedidos',
            'module' => 'pedidos',
        ]);
    }

    /**
     * Placeholder for Cocina.
     */
    public function cocinaPlaceholder()
    {
        $this->authorizeRole('cocina');
        return view('home.placeholder', [
            'title' => 'Módulo de Cocina',
            'module' => 'cocina',
        ]);
    }

    /**
     * Placeholder for Reparto.
     */
    public function repartoPlaceholder()
    {
        $this->authorizeRole('reparto');
        return view('home.placeholder', [
            'title' => 'Módulo de Reparto',
            'module' => 'reparto',
        ]);
    }

    /**
     * Placeholder for Caja.
     */
    public function cajaPlaceholder()
    {
        $this->authorizeRole('caja');
        return view('home.placeholder', [
            'title' => 'Módulo de Caja / Cobranza',
            'module' => 'caja',
        ]);
    }

    /**
     * Authorize user access based on role.
     */
    private function authorizeRole(string $role)
    {
        $user = Auth::user();
        if (!$user->hasRole($role) && !$user->hasRole('admin')) {
            abort(403, 'No tienes permiso para acceder a este módulo.');
        }
    }
}
