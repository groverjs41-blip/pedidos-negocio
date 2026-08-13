<?php

namespace App\Livewire;

use App\Models\ReturnableType;
use Livewire\Component;

class ManageReturnableTypes extends Component
{
    public string $name = '';
    public int $sortOrder = 0;
    public bool $active = true;

    public ?ReturnableType $editingType = null;
    public bool $showModal = false;

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para gestionar tipos de envases.');
        }
    }

    public function openCreateModal()
    {
        $this->editingType = null;
        $this->name = '';
        $this->sortOrder = ReturnableType::max('sort_order') + 1;
        $this->active = true;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $typeId)
    {
        $type = ReturnableType::findOrFail($typeId);
        $this->editingType = $type;
        $this->name = $type->name;
        $this->sortOrder = (int)$type->sort_order;
        $this->active = (bool)$type->active;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->editingType = null;
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ]);

        try {
            if ($this->editingType) {
                $this->editingType->update([
                    'name' => trim($this->name),
                    'sort_order' => $this->sortOrder,
                    'active' => $this->active,
                ]);
                $this->successMessage = "Tipo de envase '{$this->name}' actualizado correctamente.";
            } else {
                ReturnableType::create([
                    'name' => trim($this->name),
                    'sort_order' => $this->sortOrder,
                    'active' => $this->active,
                ]);
                $this->successMessage = "Tipo de envase '{$this->name}' creado correctamente.";
            }

            $this->closeModal();
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function toggleActive(int $typeId)
    {
        $type = ReturnableType::findOrFail($typeId);
        $type->update(['active' => !$type->active]);
        $this->successMessage = "Estado del envase '{$type->name}' actualizado correctamente.";
    }

    public function render()
    {
        $types = ReturnableType::orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.manage-returnable-types', [
            'types' => $types,
        ])->layout('layouts.app', ['title' => 'Gestión de Envases - Pedidos Negocio']);
    }
}
