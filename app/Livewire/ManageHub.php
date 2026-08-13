<?php

namespace App\Livewire;

use Livewire\Component;

class ManageHub extends Component
{
    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para acceder al panel de gestión.');
        }
    }

    public function render()
    {
        return view('livewire.manage-hub')
            ->layout('layouts.app', ['title' => 'Panel de Gestión - Pedidos Negocio']);
    }
}
