<div class="create-order-layout">
    <style>
        .create-order-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-main);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .close-alert {
            background: transparent;
            border: none;
            color: inherit;
            font-size: 1.2rem;
            cursor: pointer;
            line-height: 1;
        }

        /* Search input */
        .search-box {
            display: flex;
            gap: 0.5rem;
        }

        .form-input {
            flex-grow: 1;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: var(--primary);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Search dropdown results */
        .search-results {
            list-style: none;
            background: #1e293b;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-top: 0.5rem;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .search-result-item:hover {
            background: rgba(245, 158, 11, 0.1);
        }

        .customer-name {
            font-weight: 500;
        }

        .customer-phone {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Selected customer badge */
        .customer-badge {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.25);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .customer-badge-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .customer-badge-name {
            font-weight: 600;
            color: var(--text-main);
        }

        .customer-badge-detail {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .btn-clear-customer {
            background: transparent;
            border: none;
            color: var(--danger);
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
        }

        /* Category scrolling Chips */
        .category-scroll-container {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none; /* Firefox */
        }

        .category-scroll-container::-webkit-scrollbar {
            display: none; /* Chrome */
        }

        .category-chip {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 0.6rem 1.2rem;
            border-radius: 99px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .category-chip:hover {
            border-color: rgba(255, 255, 255, 0.2);
            color: var(--text-main);
        }

        .category-chip.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #0f172a;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        /* Product Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.75rem;
        }

        .product-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .product-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.15);
            background: var(--card-hover);
        }

        .product-image-container {
            width: 100%;
            height: 100px;
            overflow: hidden;
            background: rgba(0,0,0,0.2);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-image-placeholder {
            width: 100%;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            background: rgba(0, 0, 0, 0.15);
        }

        .product-info {
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex-grow: 1;
        }

        .product-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.3;
        }

        .product-price {
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 700;
            margin-top: auto;
        }

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Shopping Cart section */
        .cart-section {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 0.5rem;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(245, 158, 11, 0.15);
        }

        .cart-items-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-height: 180px;
            overflow-y: auto;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-details {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .cart-item-name {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .cart-item-price {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-qty {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-main);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .btn-qty:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .cart-item-qty {
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 14px;
            text-align: center;
        }

        .btn-remove {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
            margin-left: 0.25rem;
            transition: color 0.2s;
        }

        .btn-remove:hover {
            color: var(--danger);
        }

        /* Notes box */
        .notes-container {
            width: 100%;
        }

        .form-textarea {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 0.6rem 0.8rem;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.85rem;
            outline: none;
            resize: none;
        }

        .form-textarea:focus {
            border-color: var(--primary);
        }

        /* Cart footer */
        .cart-footer {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border);
        }

        .cart-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .cart-total-amount {
            color: var(--primary);
        }

        .btn-submit-order {
            background: var(--primary);
            border: none;
            color: #0f172a;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            padding: 0.9rem;
            border-radius: 14px;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
            transition: all 0.2s ease;
        }

        .btn-submit-order:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-submit-order:active {
            transform: translateY(0);
        }

        .btn-submit-order:disabled {
            background: var(--text-muted);
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
    </style>

    <!-- Offline Connection Status -->
    <div wire:offline class="offline-banner">
        ⚠️ SIN CONEXIÓN - Este pedido NO se ha enviado a cocina.
    </div>

    <!-- Feedback messages -->
    @if($successMessage)
        <div class="alert alert-success">
            <span>🎉 {{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="alert alert-danger">
            <span>⚠️ {{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <!-- 1. Customer Selection Panel -->
    <div class="glass-panel" style="padding: 1.25rem;">
        <h2 class="section-title">Cliente</h2>
        
        @if(empty($selectedCustomerName))
            <div class="search-box">
                <input type="text" 
                       wire:model.live="searchQuery" 
                       placeholder="Buscar cliente por nombre o teléfono..." 
                       class="form-input">
                <button type="button" wire:click="selectCounterSale" class="btn-secondary">
                    Mostrador
                </button>
            </div>
            
            @if(!empty($searchQuery) && count($this->customers) > 0)
                <ul class="search-results">
                    @foreach($this->customers as $customer)
                        <li wire:click="selectCustomer({{ $customer->id }})" class="search-result-item">
                            <span class="customer-name">{{ $customer->name }}</span>
                            @if($customer->phone)
                                <span class="customer-phone">📞 {{ $customer->phone }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @elseif(!empty($searchQuery))
                <div class="search-results" style="padding: 0.75rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                    No se encontraron clientes activos.
                </div>
            @endif
        @else
            <div class="customer-badge">
                <div class="customer-badge-info">
                    <span class="customer-badge-name">👤 {{ $selectedCustomerName }}</span>
                    @if($selectedCustomerPhone)
                        <span class="customer-badge-detail">📞 {{ $selectedCustomerPhone }}</span>
                    @endif
                    @if($selectedCustomerAddress)
                        <span class="customer-badge-detail">📍 {{ $selectedCustomerAddress }}</span>
                    @endif
                </div>
                <button type="button" wire:click="clearCustomer" class="btn-clear-customer">Cambiar</button>
            </div>
        @endif
    </div>

    <!-- 2. Horizontal scrolling category chips -->
    <div class="category-scroll-container">
        @foreach($activeCategories as $category)
            <button type="button" 
                    wire:click="selectCategory({{ $category->id }})" 
                    class="category-chip {{ $selectedCategoryId === $category->id ? 'active' : '' }}">
                {{ $category->name }}
            </button>
        @endforeach
    </div>

    <!-- 3. Product Selection Grid -->
    <div class="products-grid">
        @forelse($categoryProducts as $product)
            <div wire:click="addToCart({{ $product->id }})" class="product-card">
                @if($product->image)
                    <div class="product-image-container">
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-image">
                    </div>
                @else
                    <div class="product-image-placeholder">🍔</div>
                @endif
                <div class="product-info">
                    <span class="product-name">{{ $product->name }}</span>
                    <span class="product-price">${{ number_format($product->price, 2) }}</span>
                </div>
            </div>
        @empty
            <div class="no-products">No hay productos activos en esta categoría.</div>
        @endforelse
    </div>

    <!-- 4. Shopping Cart -->
    @if(!empty($cart))
        <div class="glass-panel cart-section">
            <h2 class="section-title">Carrito de Compra</h2>
            <div class="cart-items-list">
                @foreach($cart as $item)
                    <div class="cart-item">
                        <div class="cart-item-details">
                            <span class="cart-item-name">{{ $item['name'] }}</span>
                            <span class="cart-item-price">${{ number_format($item['price'], 2) }}</span>
                        </div>
                        <div class="cart-item-actions">
                            <button type="button" wire:click="decrementQty({{ $item['id'] }})" class="btn-qty">-</button>
                            <span class="cart-item-qty">{{ $item['quantity'] }}</span>
                            <button type="button" wire:click="incrementQty({{ $item['id'] }})" class="btn-qty">+</button>
                            <button type="button" wire:click="removeFromCart({{ $item['id'] }})" class="btn-remove">&times;</button>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="notes-container">
                <textarea wire:model="notes" 
                          placeholder="Notas especiales para cocina..." 
                          class="form-textarea" 
                          rows="2"></textarea>
            </div>

            <div class="cart-footer">
                <div class="cart-total-row">
                    <span>Total Pedido:</span>
                    <span class="cart-total-amount">${{ number_format($this->cartTotal, 2) }}</span>
                </div>
                
                <button type="button" 
                        wire:click="submitOrder" 
                        wire:offline.attr="disabled"
                        class="btn-submit-order">
                    ENVIAR PEDIDO A COCINA
                </button>
            </div>
        </div>
    @endif
</div>
