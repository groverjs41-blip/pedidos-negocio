<?php

namespace App\Livewire;

use Livewire\Component;

class MenuPage extends Component
{
    public function render()
    {
        return view('livewire.menu-page')
            ->layout('layouts.app', ['title' => 'Menú General - Pedidos Negocio']);
    }
}
