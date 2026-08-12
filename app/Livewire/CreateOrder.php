<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Livewire\Component;

class CreateOrder extends Component
{
    public string $searchQuery = '';
    public ?int $selectedCustomerId = null;
    public string $selectedCustomerName = '';
    public string $selectedCustomerPhone = '';
    public string $selectedCustomerAddress = '';
    
    public ?int $selectedCategoryId = null;
    
    public array $cart = []; // Structure: [product_id => [id, name, price, quantity]]
    public string $notes = '';
    
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        // Select first active category by default if available
        $firstCategory = Category::where('active', true)->orderBy('sort_order')->orderBy('name')->first();
        if ($firstCategory) {
            $this->selectedCategoryId = $firstCategory->id;
        }
    }

    /**
     * Search active customers.
     */
    public function getCustomersProperty()
    {
        if (strlen($this->searchQuery) < 2) {
            return [];
        }

        return Customer::where('active', true)
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('phone', 'like', '%' . $this->searchQuery . '%');
            })
            ->take(5)
            ->get();
    }

    /**
     * Select a customer.
     */
    public function selectCustomer(int $id): void
    {
        $customer = Customer::where('id', $id)->where('active', true)->first();
        if ($customer) {
            $this->selectedCustomerId = $customer->id;
            $this->selectedCustomerName = $customer->name;
            $this->selectedCustomerPhone = $customer->phone ?? '';
            $this->selectedCustomerAddress = $customer->address ?? '';
            $this->searchQuery = '';
            $this->errorMessage = null;
        }
    }

    /**
     * Set Counter Sale (Venta Mostrador).
     */
    public function selectCounterSale(): void
    {
        $this->selectedCustomerId = null;
        $this->selectedCustomerName = 'Venta Mostrador';
        $this->selectedCustomerPhone = '';
        $this->selectedCustomerAddress = '';
        $this->searchQuery = '';
        $this->errorMessage = null;
    }

    /**
     * Clear customer selection.
     */
    public function clearCustomer(): void
    {
        $this->selectedCustomerId = null;
        $this->selectedCustomerName = '';
        $this->selectedCustomerPhone = '';
        $this->selectedCustomerAddress = '';
    }

    /**
     * Select active category.
     */
    public function selectCategory(int $id): void
    {
        $this->selectedCategoryId = $id;
    }

    /**
     * Add product to cart.
     */
    public function addToCart(int $productId): void
    {
        $product = Product::where('id', $productId)->where('active', true)->first();
        if (!$product) {
            $this->errorMessage = 'El producto no está disponible.';
            return;
        }

        if (!$product->category->active) {
            $this->errorMessage = 'La categoría de este producto está inactiva.';
            return;
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
            ];
        }

        $this->errorMessage = null;
    }

    public function incrementQty(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        }
    }

    public function decrementQty(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']--;
            if ($this->cart[$productId]['quantity'] <= 0) {
                unset($this->cart[$productId]);
            }
        }
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    /**
     * Get grand total of the cart.
     */
    public function getCartTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    /**
     * Submit order to the Kitchen.
     */
    public function submitOrder(OrderService $orderService): void
    {
        if (empty($this->selectedCustomerName)) {
            $this->errorMessage = 'Debe seleccionar un cliente o venta mostrador.';
            return;
        }

        if (empty($this->cart)) {
            $this->errorMessage = 'El carrito de compras está vacío.';
            return;
        }

        try {
            // Reformat items array for service layer
            $items = [];
            foreach ($this->cart as $item) {
                $items[] = [
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                ];
            }

            $order = $orderService->createOrder([
                'customer_id' => $this->selectedCustomerId,
                'notes' => $this->notes,
                'items' => $items,
            ], auth()->user());

            $this->successMessage = "Pedido {$order->number} enviado a cocina.";
            
            // Reset state
            $this->clearCustomer();
            $this->cart = [];
            $this->notes = '';
            $this->errorMessage = null;

        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error inesperado al crear el pedido: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.create-order', [
            'activeCategories' => Category::where('active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'categoryProducts' => $this->selectedCategoryId 
                ? Product::where('category_id', $this->selectedCategoryId)->where('active', true)->orderBy('name')->get() 
                : [],
        ])->title('Nuevo Pedido');
    }
}
