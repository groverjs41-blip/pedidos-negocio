<?php

namespace App\Livewire;

use App\Models\Customer;
use Livewire\Component;

class EditCustomer extends Component
{
    public Customer $customer;

    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $addressReference = '';
    public string $notes = '';
    public bool $active = true;

    public ?string $errorMessage = null;

    public function mount(Customer $customer)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para editar clientes.');
        }

        $this->customer = $customer;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->address = $customer->address ?? '';
        $this->addressReference = $customer->address_reference ?? '';
        $this->notes = $customer->notes ?? '';
        $this->active = (bool)$customer->active;
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
            $this->customer->update([
                'name' => trim($this->name),
                'phone' => trim($this->phone) ?: null,
                'address' => trim($this->address) ?: null,
                'address_reference' => trim($this->addressReference) ?: null,
                'notes' => trim($this->notes) ?: null,
                'active' => $this->active,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        session()->flash('success', "Cliente '{$this->customer->name}' actualizado correctamente.");
        $this->redirect('/gestion/clientes/' . $this->customer->id);
    }

    public function render()
    {
        return view('livewire.edit-customer')
            ->layout('layouts.app', ['title' => 'Editar Cliente - Pedidos Negocio']);
    }
}
