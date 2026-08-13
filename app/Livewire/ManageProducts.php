<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ManageProducts extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryId = '';
    public string $activeFilter = '';

    public ?string $successMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para gestionar productos.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryId()
    {
        $this->resetPage();
    }

    public function updatingActiveFilter()
    {
        $this->resetPage();
    }

    public function toggleActive(int $productId)
    {
        $product = Product::findOrFail($productId);
        $product->update(['active' => !$product->active]);
        $this->successMessage = "Estado del producto '{$product->name}' actualizado correctamente.";
    }

    public function render()
    {
        $query = Product::with(['category', 'returnableRequirements.returnableType'])
            ->orderBy('name', 'asc');

        if (!empty(trim($this->search))) {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        if (!empty($this->categoryId)) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->activeFilter !== '') {
            $query->where('active', (bool)$this->activeFilter);
        }

        $products = $query->paginate(15);
        $categories = Category::orderBy('name', 'asc')->get();

        return view('livewire.manage-products', [
            'products' => $products,
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Gestión de Productos - Pedidos Negocio']);
    }
}
