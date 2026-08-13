<?php

namespace App\Livewire;

use App\Models\Customer;
use Livewire\Component;

class CreateCustomer extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $addressReference = '';
    public string $notes = '';
    public bool $active = true;

    public ?string $errorMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para crear clientes.');
        }
    }

    public function save()
    {
        $this->errorMessage = null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'addressReference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $customer = Customer::create([
                'name' => trim($this->name),
                'phone' => trim($this->phone) ?: null,
                'address' => trim($this->address) ?: null,
                'location_notes' => trim($this->addressReference) ?: null,
                'notes' => trim($this->notes) ?: null,
                'active' => $this->active,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        session()->flash('success', "Cliente '{$customer->name}' creado correctamente.");
        $this->redirect('/gestion/clientes');
    }

    public function render()
    {
        return view('livewire.create-customer')
            ->layout('layouts.app', ['title' => 'Nuevo Cliente - Pedidos Negocio']);
    }
}
