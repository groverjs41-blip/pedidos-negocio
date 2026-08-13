<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReturnableRequirement;
use App\Models\ReturnableType;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProduct extends Component
{
    use WithFileUploads;

    public Product $product;

    public string $name = '';
    public string $categoryId = '';
    public string $price = '';
    public string $estimatedCost = '0.00';
    public bool $active = true;
    public string $notes = '';
    public $image = null;

    public array $requirements = [];
    public ?string $errorMessage = null;

    public function mount(Product $product)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para editar productos.');
        }

        $this->product = $product;
        $this->name = $product->name;
        $this->categoryId = (string)$product->category_id;
        $this->price = (string)$product->price;
        $this->estimatedCost = (string)$product->estimated_cost;
        $this->active = (bool)$product->active;
        $this->notes = $product->notes ?? '';

        foreach ($product->returnableRequirements ?? [] as $req) {
            $this->requirements[] = [
                'returnable_type_id' => $req->returnable_type_id,
                'quantity' => $req->quantity,
            ];
        }
    }

    public function addRequirement()
    {
        $defaultType = ReturnableType::where('active', true)->first();
        if ($defaultType) {
            $this->requirements[] = [
                'returnable_type_id' => $defaultType->id,
                'quantity' => 1,
            ];
        }
    }

    public function removeRequirement(int $index)
    {
        unset($this->requirements[$index]);
        $this->requirements = array_values($this->requirements);
    }

    public function save()
    {
        $this->errorMessage = null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'categoryId' => ['required', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'estimatedCost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        try {
            $data = [
                'category_id' => $this->categoryId,
                'name' => trim($this->name),
                'price' => number_format((float)$this->price, 2, '.', ''),
                'estimated_cost' => number_format((float)$this->estimatedCost, 2, '.', ''),
                'active' => $this->active,
                'notes' => trim($this->notes) ?: null,
            ];

            if ($this->image) {
                $data['image_url'] = $this->image->store('products', 'public');
            }

            $this->product->update($data);

            // Sync Returnable Requirements
            ProductReturnableRequirement::where('product_id', $this->product->id)->delete();
            foreach ($this->requirements as $req) {
                if (!empty($req['returnable_type_id']) && (int)($req['quantity'] ?? 0) > 0) {
                    ProductReturnableRequirement::create([
                        'product_id' => $this->product->id,
                        'returnable_type_id' => $req['returnable_type_id'],
                        'quantity' => (int)$req['quantity'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        session()->flash('success', "Producto '{$this->product->name}' actualizado correctamente.");
        $this->redirect('/gestion/productos');
    }

    public function render()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $returnableTypes = ReturnableType::orderBy('name', 'asc')->get();

        return view('livewire.edit-product', [
            'categories' => $categories,
            'returnableTypes' => $returnableTypes,
        ])->layout('layouts.app', ['title' => 'Editar Producto - Pedidos Negocio']);
    }
}
