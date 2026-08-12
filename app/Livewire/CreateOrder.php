<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Livewire\Component;
use Illuminate\Support\Str;

class CreateOrder extends Component
{
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

    // Idempotency token
    public string $submissionToken = '';

    public function mount(): void
    {
        $this->generateSubmissionToken();

        // Select first active category by default if available
        $firstCategory = Category::where('active', true)->orderBy('sort_order')->orderBy('name')->first();
        if ($firstCategory) {
            $this->selectedCategoryId = $firstCategory->id;
        }
    }

    /**
     * Generate a new submission UUID.
     */
    protected function generateSubmissionToken(): void
    {
        $this->submissionToken = Str::uuid()->toString();
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
            
            $this->dispatch('focus-search-product');
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
        
        $this->dispatch('focus-search-product');
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
        $this->dispatch('focus-search-customer');
    }

    /**
     * Select active category.
     */
    public function selectCategory(int $id): void
    {
        $this->selectedCategoryId = $id;
        $this->productSearch = ''; // Clear search when switching categories
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
     * Get grand total of the cart using precise bcmath scaling.
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
            $items = [];
            foreach ($this->cart as $item) {
                $items[] = [
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                ];
            }

            $order = $orderService->createOrder([
                'submission_token' => $this->submissionToken,
                'customer_id' => $this->selectedCustomerId,
                'notes' => $this->notes,
                'items' => $items,
            ], auth()->user());

            $this->successMessage = "Pedido {$order->number} enviado a cocina.";
            
            // 1. Reset state completely
            $this->selectedCustomerId = null;
            $this->selectedCustomerName = '';
            $this->selectedCustomerPhone = '';
            $this->selectedCustomerAddress = '';
            $this->searchQuery = '';
            $this->productSearch = '';
            $this->cart = [];
            $this->notes = '';
            $this->errorMessage = null;

            // 2. Generate a new token for subsequent order
            $this->generateSubmissionToken();

            // 3. Dispatch refocus browser event
            $this->dispatch('focus-search-customer');

        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error inesperado al crear el pedido: ' . $e->getMessage();
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

        return view('livewire.create-order', [
            'activeCategories' => Category::where('active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'categoryProducts' => $products,
        ])->title('Nuevo Pedido');
    }
}
