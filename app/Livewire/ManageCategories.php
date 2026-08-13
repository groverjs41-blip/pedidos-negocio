<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;

class ManageCategories extends Component
{
    public string $name = '';
    public int $sortOrder = 0;
    public bool $active = true;

    public ?Category $editingCategory = null;
    public bool $showModal = false;

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para gestionar categorías.');
        }
    }

    public function openCreateModal()
    {
        $this->editingCategory = null;
        $this->name = '';
        $this->sortOrder = Category::max('sort_order') + 1;
        $this->active = true;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $categoryId)
    {
        $cat = Category::findOrFail($categoryId);
        $this->editingCategory = $cat;
        $this->name = $cat->name;
        $this->sortOrder = (int)$cat->sort_order;
        $this->active = (bool)$cat->active;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->editingCategory = null;
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ]);

        try {
            if ($this->editingCategory) {
                $this->editingCategory->update([
                    'name' => trim($this->name),
                    'sort_order' => $this->sortOrder,
                    'active' => $this->active,
                ]);
                $this->successMessage = "Categoría '{$this->name}' actualizada correctamente.";
            } else {
                Category::create([
                    'name' => trim($this->name),
                    'sort_order' => $this->sortOrder,
                    'active' => $this->active,
                ]);
                $this->successMessage = "Categoría '{$this->name}' creada correctamente.";
            }

            $this->closeModal();
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function toggleActive(int $categoryId)
    {
        $cat = Category::findOrFail($categoryId);
        $cat->update(['active' => !$cat->active]);
        $this->successMessage = "Estado de categoría '{$cat->name}' actualizado correctamente.";
    }

    public function render()
    {
        $categories = Category::withCount('products')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.manage-categories', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Gestión de Categorías - Pedidos Negocio']);
    }
}
