<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReturnableRequirement;
use App\Models\ReturnableType;
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

    // Quick Customer Modal State
    public string $quickCustomerName = '';
    public string $quickCustomerPhone = '';
    public string $quickCustomerAddress = '';
    public string $quickCustomerRef = '';

    // Quick Product Modal State
    public string $quickProductName = '';
    public string $quickProductCategoryId = '';
    public string $quickProductPrice = '';
    public bool $quickProductActive = true;
    public string $quickProdReturnableTypeId = '';
    public int $quickProdReturnableQty = 1;

    // Inner Quick Category State (inside Quick Product modal)
    public string $quickProductCatName = '';

    // Inner Quick Returnable Type State
    public string $quickReturnableName = '';
    public int $quickReturnableSortOrder = 0;
    public bool $quickReturnableActive = true;

    // Idempotency token
    public string $submissionToken = '';

    public function mount(): void
    {
        $this->generateSubmissionToken();

        // Select first active category by default if available
        $firstCategory = Category::where('active', true)->orderBy('sort_order')->orderBy('name')->first();
        if ($firstCategory) {
            $this->selectedCategoryId = $firstCategory->id;
            $this->quickProductCategoryId = (string) $firstCategory->id;
        }

        $customerParam = request()->query('customer');
        if ($customerParam) {
            $cust = Customer::where('id', $customerParam)->where('active', true)->first();
            if ($cust) {
                $this->selectCustomer($cust->id);
            }
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

        $this->dispatch('focus-search-product');
    }

    // Quick Return Modal State
    public bool $showReturnModal = false;
    public array $returnQuantities = [];

    public function getSelectedCustomerDebtProperty(): string
    {
        if (!$this->selectedCustomerId) return '0.00';
        $customer = Customer::find($this->selectedCustomerId);
        return $customer ? $customer->outstandingBalance() : '0.00';
    }

    public function getSelectedCustomerReturnablesProperty(): array
    {
        if (!$this->selectedCustomerId) return [];
        $customer = Customer::find($this->selectedCustomerId);
        if (!$customer) return [];
        return app(\App\Services\ReturnableService::class)->getCustomerSummary($customer);
    }

    public function openReturnModal(): void
    {
        if (!$this->selectedCustomerId) return;
        $this->returnQuantities = [];
        $summary = $this->selectedCustomerReturnables;
        foreach ($summary as $item) {
            $this->returnQuantities[$item['type']->id] = 0;
        }
        $this->showReturnModal = true;
    }

    public function closeReturnModal(): void
    {
        $this->showReturnModal = false;
        $this->returnQuantities = [];
    }

    public function submitQuickReturn(\App\Services\ReturnableService $returnableService): void
    {
        if (!$this->selectedCustomerId) {
            $this->closeReturnModal();
            return;
        }
        $customer = Customer::find($this->selectedCustomerId);
        if (!$customer) {
            $this->closeReturnModal();
            return;
        }

        $items = [];
        foreach ($this->returnQuantities as $typeId => $qty) {
            $q = (int) $qty;
            if ($q > 0) {
                $items[] = [
                    'returnable_type_id' => (int) $typeId,
                    'quantity' => $q,
                ];
            }
        }

        if (empty($items)) {
            $this->closeReturnModal();
            return;
        }

        try {
            $returnableService->recordReturnBatch(
                $customer,
                $items,
                auth()->user(),
                (string) Str::uuid(),
                null,
                'Devolución registrada en nuevo pedido'
            );
            $this->dispatch('notify-toast', type: 'success', title: 'Devolución Registrada', message: 'Envases devueltos registrados correctamente.');
            $this->closeReturnModal();
        } catch (\Throwable $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'No se pudo registrar la devolución de envases.');
        }
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
     * Create Quick Customer from modal without resetting order form or cart.
     */
    public function createQuickCustomer()
    {
        $this->validate([
            'quickCustomerName' => ['required', 'string', 'max:255'],
            'quickCustomerPhone' => ['nullable', 'string', 'max:50'],
            'quickCustomerAddress' => ['nullable', 'string'],
            'quickCustomerRef' => ['nullable', 'string'],
        ]);

        $customer = Customer::create([
            'name' => trim($this->quickCustomerName),
            'phone' => trim($this->quickCustomerPhone) ?: null,
            'address' => trim($this->quickCustomerAddress) ?: null,
            'location_notes' => trim($this->quickCustomerRef) ?: null,
            'active' => true,
        ]);

        $this->selectedCustomerId = $customer->id;
        $this->selectedCustomerName = $customer->name;
        $this->selectedCustomerPhone = $customer->phone ?? '';
        $this->selectedCustomerAddress = $customer->address ?? '';

        $this->quickCustomerName = '';
        $this->quickCustomerPhone = '';
        $this->quickCustomerAddress = '';
        $this->quickCustomerRef = '';

        $this->dispatch('close-modal', 'quick-customer-modal');
        $this->dispatch('notify-toast', type: 'success', title: 'Cliente Creado', message: "Cliente '{$customer->name}' asignado al pedido.");
    }

    /**
     * Create Quick Category from within Quick Product modal.
     */
    public function createQuickProductCat()
    {
        $this->validate([
            'quickProductCatName' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $cat = Category::create([
            'name' => trim($this->quickProductCatName),
            'active' => true,
        ]);

        $this->quickProductCategoryId = (string) $cat->id;
        $this->selectedCategoryId = $cat->id;
        $this->quickProductCatName = '';

        $this->dispatch('close-modal', 'quick-prod-cat-modal');
        $this->dispatch('notify-toast', type: 'success', title: 'Categoría Creada', message: "Categoría '{$cat->name}' seleccionada.");
    }

    /**
     * Create Quick Returnable Type from within Quick Product modal.
     */
    public function createQuickReturnableType()
    {
        $this->validate([
            'quickReturnableName' => ['required', 'string', 'max:255', 'unique:returnable_types,name'],
            'quickReturnableSortOrder' => ['nullable', 'integer', 'min:0'],
        ]);

        $returnableType = ReturnableType::create([
            'name' => trim($this->quickReturnableName),
            'sort_order' => $this->quickReturnableSortOrder ?: 0,
            'active' => $this->quickReturnableActive,
        ]);

        $this->quickProdReturnableTypeId = (string) $returnableType->id;
        $this->quickReturnableName = '';

        $this->dispatch('close-modal', 'quick-returnable-type-modal');
        $this->dispatch('notify-toast', type: 'success', title: 'Envase Creado', message: "Tipo de envase '{$returnableType->name}' seleccionado.");
    }

    /**
     * Create Quick Product and automatically add it to the cart.
     */
    public function createQuickProduct()
    {
        $this->validate([
            'quickProductName' => ['required', 'string', 'max:255'],
            'quickProductCategoryId' => ['required', 'exists:categories,id'],
            'quickProductPrice' => ['required', 'numeric', 'min:0'],
            'quickProdReturnableTypeId' => ['nullable', 'exists:returnable_types,id'],
            'quickProdReturnableQty' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::create([
            'category_id' => $this->quickProductCategoryId,
            'name' => trim($this->quickProductName),
            'price' => bcadd((string)$this->quickProductPrice, '0', 2),
            'active' => $this->quickProductActive,
        ]);

        if (!empty($this->quickProdReturnableTypeId) && $this->quickProdReturnableQty > 0) {
            ProductReturnableRequirement::create([
                'product_id' => $product->id,
                'returnable_type_id' => (int) $this->quickProdReturnableTypeId,
                'quantity' => (int) $this->quickProdReturnableQty,
            ]);
        }

        $this->selectedCategoryId = (int) $this->quickProductCategoryId;
        $this->addToCart($product->id);

        $this->quickProductName = '';
        $this->quickProductPrice = '';
        $this->quickProdReturnableTypeId = '';
        $this->quickProdReturnableQty = 1;

        $this->dispatch('close-modal', 'quick-product-modal');
        $this->dispatch('notify-toast', type: 'success', title: 'Producto Creado', message: "Producto '{$product->name}' agregado al pedido.");
    }

    /**
     * Select active category (or null/0 for all products).
     */
    public function selectCategory(?int $id): void
    {
        $this->selectedCategoryId = ($id === 0 || $id === null) ? null : $id;
        $this->productSearch = '';
    }

    public function render()
    {
        $activeCategories = Category::where('active', true)->orderBy('sort_order')->orderBy('name')->get();

        // If selectedCategoryId is null but categories exist, default to first category if not explicitly set to all
        if (is_null($this->selectedCategoryId) && $activeCategories->isNotEmpty() && empty($this->productSearch)) {
            // Keep null to show all or select first category
        }

        if (!empty($this->productSearch)) {
            $products = Product::where('active', true)
                ->where('name', 'like', '%' . $this->productSearch . '%')
                ->orderBy('name')
                ->get();
        } else {
            $query = Product::where('active', true);
            if ($this->selectedCategoryId) {
                $query->where('category_id', $this->selectedCategoryId);
            }
            $products = $query->orderBy('name')->get();
        }

        return view('livewire.create-order', [
            'activeCategories' => $activeCategories,
            'returnableTypes' => ReturnableType::where('active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'categoryProducts' => $products,
        ])->title('Nuevo Pedido');
    }

    /**
     * Add product to cart.
     */
    public function addToCart(int $productId): void
    {
        $product = Product::where('id', $productId)->where('active', true)->first();
        if (!$product) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'El producto no está disponible.');
            return;
        }

        if (!$product->category->active) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'La categoría de este producto está inactiva.');
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

    public string $serviceMode = 'KITCHEN';

    // Direct Counter Sale Active Order & Payment State
    public ?int $activeDirectOrderId = null;
    public string $directPaymentMethod = 'CASH';
    public string $directPaymentReference = '';
    public string $directPaymentAmount = '';

    public function setServiceMode(string $mode): void
    {
        if (in_array($mode, ['KITCHEN', 'DIRECT'])) {
            $this->serviceMode = $mode;
        }
    }

    public function getActiveDirectOrderProperty(): ?Order
    {
        if (!$this->activeDirectOrderId) return null;
        return Order::with(['items', 'paymentAllocations'])->find($this->activeDirectOrderId);
    }

    /**
     * Submit order to Kitchen or register Direct Sale.
     */
    public function submitOrder(OrderService $orderService): void
    {
        if (empty($this->selectedCustomerName)) {
            $this->dispatch('notify-toast', type: 'warning', title: 'Cliente Requerido', message: 'Debe seleccionar un cliente o venta mostrador.');
            return;
        }

        if (empty($this->cart)) {
            $this->dispatch('notify-toast', type: 'warning', title: 'Carrito Vacío', message: 'El carrito de compras está vacío.');
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
                'service_mode' => $this->serviceMode,
            ], auth()->user());

            if ($this->serviceMode === 'DIRECT') {
                $this->activeDirectOrderId = $order->id;
                $this->directPaymentAmount = (string) $order->total;
                $this->directPaymentMethod = \App\Enums\PaymentMethod::CASH->value;
                $this->directPaymentReference = '';
                $this->dispatch('notify-toast', type: 'success', title: 'Venta en Puesto', message: "Pedido #{$order->number} registrado.");
            } else {
                $this->dispatch('notify-toast', type: 'success', title: 'Pedido Enviado', message: "Pedido #{$order->number} enviado a cocina correctamente.");

                // Reset state completely and close mobile cart
                $this->resetOrderForm();
                $this->dispatch('order-submitted-success');
            }

        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error al enviar', message: $e->getMessage());
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error del sistema', message: 'Error al crear el pedido. Verifique los datos ingresados.');
        }
    }

    public function startDirectPreparing(OrderService $orderService): void
    {
        $order = $this->activeDirectOrder;
        if (!$order) return;

        try {
            $orderService->startPreparing($order, auth()->user());
            $this->dispatch('notify-toast', type: 'info', title: 'En Preparación', message: "Pedido #{$order->number} en preparación.");
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: $e->getMessage());
        }
    }

    public function markDirectDelivered(OrderService $orderService): void
    {
        $order = $this->activeDirectOrder;
        if (!$order) return;

        try {
            $orderService->markDirectDelivered($order, auth()->user());
            $this->directPaymentAmount = (string) $order->fresh()->outstandingBalance();
            $this->dispatch('notify-toast', type: 'success', title: 'Entregado', message: "Pedido #{$order->number} marcado como entregado.");
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: $e->getMessage());
        }
    }

    public function submitDirectPayment(\App\Services\PaymentService $paymentService): void
    {
        $order = $this->activeDirectOrder;
        if (!$order) return;

        try {
            $paymentMethodEnum = \App\Enums\PaymentMethod::tryFrom($this->directPaymentMethod) ?? \App\Enums\PaymentMethod::CASH;
            $paymentToken = (string) Str::uuid();

            $paymentService->recordOrderPayment(
                $order,
                $this->directPaymentAmount,
                $paymentMethodEnum,
                trim($this->directPaymentReference) ?: null,
                'Cobro de venta en puesto',
                auth()->user(),
                $paymentToken
            );

            $this->dispatch('notify-toast', type: 'success', title: 'Venta Completada', message: "Cobro registrado para el pedido #{$order->number}.");

            $this->resetOrderForm();
            $this->dispatch('order-submitted-success');
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error en cobro', message: $e->getMessage());
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'No se pudo registrar el pago.');
        }
    }

    public function resetOrderForm(): void
    {
        $this->selectedCustomerId = null;
        $this->selectedCustomerName = '';
        $this->selectedCustomerPhone = '';
        $this->selectedCustomerAddress = '';
        $this->searchQuery = '';
        $this->productSearch = '';
        $this->cart = [];
        $this->notes = '';
        $this->serviceMode = 'KITCHEN';
        $this->activeDirectOrderId = null;
        $this->directPaymentAmount = '';
        $this->directPaymentReference = '';
        $this->directPaymentMethod = \App\Enums\PaymentMethod::CASH->value;

        $this->generateSubmissionToken();
        $this->dispatch('focus-search-customer');
    }
}
