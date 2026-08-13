<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReturnableRequirement;
use App\Models\ReturnableType;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateProduct extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $categoryId = '';
    public string $price = '';
    public string $estimatedCost = '0.00';
    public bool $active = true;
    public string $notes = '';
    public $image = null;

    // Returnable requirements items: array of ['returnable_type_id' => int, 'quantity' => int]
    public array $requirements = [];

    // Quick Category Modal State
    public string $quickCategoryName = '';
    public string $quickCategorySortOrder = '0';

    // Quick Returnable Type Modal State
    public string $quickReturnableName = '';

    public ?string $errorMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para crear productos.');
        }

        $defaultCategory = Category::where('active', true)->orderBy('name', 'asc')->first();
        if ($defaultCategory) {
            $this->categoryId = (string)$defaultCategory->id;
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

    public function createQuickCategory()
    {
        $this->validate([
            'quickCategoryName' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'quickCategorySortOrder' => ['nullable', 'integer'],
        ]);

        $cat = Category::create([
            'name' => trim($this->quickCategoryName),
            'sort_order' => (int) $this->quickCategorySortOrder,
            'active' => true,
        ]);

        $this->categoryId = (string) $cat->id;
        $this->quickCategoryName = '';
        $this->quickCategorySortOrder = '0';

        $this->dispatch('close-modal', 'quick-category-modal');
        $this->dispatch('notify-toast', type: 'success', title: 'Categoría Creada', message: "Categoría '{$cat->name}' creada y seleccionada.");
    }

    public function createQuickReturnableType()
    {
        $this->validate([
            'quickReturnableName' => ['required', 'string', 'max:255', 'unique:returnable_types,name'],
        ]);

        $type = ReturnableType::create([
            'name' => trim($this->quickReturnableName),
            'active' => true,
        ]);

        $this->requirements[] = [
            'returnable_type_id' => $type->id,
            'quantity' => 1,
        ];

        $this->quickReturnableName = '';

        $this->dispatch('close-modal', 'quick-returnable-modal');
        $this->dispatch('notify-toast', type: 'success', title: 'Envase Creado', message: "Tipo de envase '{$type->name}' creado y agregado.");
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
            $imagePath = null;
            if ($this->image) {
                $imagePath = $this->image->store('products', 'public');
            }

            $product = Product::create([
                'category_id' => $this->categoryId,
                'name' => trim($this->name),
                'price' => bcadd((string)$this->price, '0', 2),
                'estimated_cost' => bcadd((string)$this->estimatedCost, '0', 2),
                'active' => $this->active,
                'notes' => trim($this->notes) ?: null,
                'image' => $imagePath,
            ]);

            // Save Returnable Requirements
            foreach ($this->requirements as $req) {
                if (!empty($req['returnable_type_id']) && (int)($req['quantity'] ?? 0) > 0) {
                    ProductReturnableRequirement::create([
                        'product_id' => $product->id,
                        'returnable_type_id' => $req['returnable_type_id'],
                        'quantity' => (int)$req['quantity'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'No se pudo guardar el producto. Verifique los datos ingresados.';
            return;
        }

        session()->flash('success', "Producto '{$product->name}' creado correctamente.");
        $this->redirect('/gestion/productos');
    }

    public function render()
    {
        $categories = Category::where('active', true)->orderBy('name', 'asc')->get();
        $returnableTypes = ReturnableType::where('active', true)->orderBy('name', 'asc')->get();

        return view('livewire.create-product', [
            'categories' => $categories,
            'returnableTypes' => $returnableTypes,
        ])->layout('layouts.app', ['title' => 'Nuevo Producto - Pedidos Negocio']);
    }
}
