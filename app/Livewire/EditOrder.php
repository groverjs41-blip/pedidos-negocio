<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Livewire\Component;

class EditOrder extends Component
{
    public Order $order;
    public string $searchQuery = '';
    public ?int $selectedCustomerId = null;
    public string $selectedCustomerName = '';
    public string $selectedCustomerPhone = '';
    public string $selectedCustomerAddress = '';
    
    public ?int $selectedCategoryId = null;
    public string $productSearch = '';
    
    public array $cart = []; // Structure: [product_id => [id, name, price, quantity]]
    public string $notes = '';
    
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(Order $order): void
    {
        if ($order->status !== OrderStatus::NEW) {
            abort(403, 'Solo se pueden editar pedidos con estado "Nuevo".');
        }

        $this->order = $order;
        $this->selectedCustomerId = $order->customer_id;
        $this->selectedCustomerName = $order->customer_name_snapshot ?? 'Venta Mostrador';
        $this->selectedCustomerPhone = $order->customer_phone_snapshot ?? '';
        $this->selectedCustomerAddress = $order->delivery_address_snapshot ?? '';
        $this->notes = $order->notes ?? '';

        // Load items into the cart
        foreach ($order->items as $item) {
            if ($item->product_id) {
                $this->cart[$item->product_id] = [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'price' => (string) $item->unit_price,
                    'quantity' => $item->quantity,
                ];
            }
        }

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
            
            $this->dispatch('focus-search-product');
        }
    }

    public function selectCounterSale(): void
    {
        $this->selectedCustomerId = null;
        $this->selectedCustomerName = 'Venta Mostrador';
        $this->selectedCustomerPhone = '';
        $this->selectedCustomerAddress = '';
        $this->searchQuery = '';
        $this->errorMessage = null;
        
        $this->dispatch('focus-search-product');
    }

    public function clearCustomer(): void
    {
        $this->selectedCustomerId = null;
        $this->selectedCustomerName = '';
        $this->selectedCustomerPhone = '';
        $this->selectedCustomerAddress = '';
        $this->dispatch('focus-search-customer');
    }

    public function selectCategory(int $id): void
    {
        $this->selectedCategoryId = $id;
        $this->productSearch = ''; // Clear search when switching categories
    }

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
                'price' => (string) $product->price,
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
     * Calculate grand total of the cart using bcmath scaling.
     */
    public function getCartTotalProperty(): string
    {
        $total = '0.00';
        foreach ($this->cart as $item) {
            $line = bcmul((string) $item['quantity'], (string) $item['price'], 2);
            $total = bcadd($total, $line, 2);
        }
        return $total;
    }

    /**
     * Update the order details.
     */
    public function updateOrder(OrderService $orderService)
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
            $items = [];
            foreach ($this->cart as $item) {
                $items[] = [
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                ];
            }

            $orderService->updateNewOrder($this->order, [
                'customer_id' => $this->selectedCustomerId,
                'notes' => $this->notes,
                'items' => $items,
            ], auth()->user());

            session()->flash('successMessage', "Pedido {$this->order->number} actualizado con éxito.");
            return redirect('/pedidos');

        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al actualizar el pedido: ' . $e->getMessage();
        }
    }

    public function render()
    {
        // Search globally if query provided, else filter by selected category
        if (!empty($this->productSearch)) {
            $products = Product::where('active', true)
                ->where('name', 'like', '%' . $this->productSearch . '%')
                ->orderBy('name')
                ->get();
        } else {
            $products = $this->selectedCategoryId 
                ? Product::where('category_id', $this->selectedCategoryId)->where('active', true)->orderBy('name')->get() 
                : [];
        }

        return view('livewire.edit-order', [
            'activeCategories' => Category::where('active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'categoryProducts' => $products,
        ])->title('Editar Pedido');
    }
}
