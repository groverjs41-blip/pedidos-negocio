<div class="pos-layout" x-data="{ mobileCartOpen: false }">
    {{-- Offline status warning --}}
    <div wire:offline class="alert alert-danger">
        <x-ui.icon name="alert" class="w-4 h-4" />
        <span><strong>SIN CONEXIÓN:</strong> Este pedido NO se ha enviado a cocina.</span>
    </div>

    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <div class="pos-container">
        {{-- LEFT PANEL (70% on Desktop - Catalog) --}}
        <div class="pos-left">
            <div class="page-header" style="margin-bottom: 0.75rem;">
                <div>
                    <h1 class="page-header-title">
                        <div class="header-icon-wrap mint">
                            <x-ui.icon name="plus" class="w-5 h-5" />
                        </div>
                        Nuevo Pedido
                    </h1>
                    <div class="page-header-subtitle">¿Qué desea pedir el cliente?</div>
                </div>
            </div>

            {{-- 1. Compact Customer Selector Bar --}}
            <div class="customer-bar">
                @if(!$selectedCustomerName)
                    <div style="position: relative; flex-grow: 1; margin-right: 0.75rem;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                            <x-ui.icon name="search" class="w-4 h-4" />
                        </span>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            id="customerSearchInput"
                            class="form-input"
                            style="padding-left: 2.5rem; height: 42px;"
                            placeholder="🔍 Buscar cliente por nombre o teléfono..."
                            autocomplete="off"
                        >
                    </div>
                    <button type="button" wire:click="selectCounterSale" class="btn-counter-sale">
                        Venta Mostrador
                    </button>
                @else
                    <div class="customer-bar-left">
                        <div class="customer-avatar">
                            {{ strtoupper(substr($selectedCustomerName, 0, 1)) }}
                        </div>
                        <div>
                            <div class="customer-name-text">{{ $selectedCustomerName }}</div>
                            <div class="customer-details-inline">
                                @if($selectedCustomerPhone) 📞 {{ $selectedCustomerPhone }} @endif
                                @if($selectedCustomerAddress) • 📍 {{ $selectedCustomerAddress }} @endif
                            </div>
                            @if($selectedCustomerId)
                                @inject('returnableService', 'App\Services\ReturnableService')
                                @php
                                    $customerObj = \App\Models\Customer::find($selectedCustomerId);
                                    $pendingCount = $customerObj ? $returnableService->getCustomerTotalOutstanding($customerObj) : 0;
                                @endphp
                                @if($pendingCount > 0)
                                    <div style="font-size: 0.75rem; color: var(--warning-text); font-weight: 700; margin-top: 2px;">
                                        📦 Envases por recoger: {{ $pendingCount }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="clearCustomer" style="background: transparent; border: none; color: var(--danger-text); font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                        Cambiar
                    </button>
                @endif
            </div>

            {{-- Customer search results dropdown --}}
            @if(!$selectedCustomerName && count($this->customers) > 0)
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-md); margin-top: -0.5rem; overflow: hidden; z-index: 10; display: flex; flex-direction: column; box-shadow: var(--shadow-md);">
                    @foreach($this->customers as $cust)
                        <button type="button" wire:click="selectCustomer({{ $cust->id }})" style="background: transparent; border: none; border-bottom: 1px solid var(--border); padding: 0.75rem 1rem; color: var(--text-main); text-align: left; cursor: pointer; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <span style="font-weight: 600;">{{ $cust->name }}</span>
                                @if($cust->phone) <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 0.5rem;">📞 {{ $cust->phone }}</span> @endif
                            </div>
                            <span style="font-size: 0.775rem; color: var(--primary); font-weight: 700;">Seleccionar</span>
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- 2. Product Search & Category Chips --}}
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <div style="position: relative;">
                    <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                        <x-ui.icon name="search" class="w-5 h-5" />
                    </span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="productSearch"
                        id="productSearchInput"
                        class="form-input pos-search-input"
                        placeholder="Buscar sándwiches, bebidas, combos..."
                    >
                </div>

                {{-- Horizontal Categories Chips --}}
                <div class="category-chips">
                    @foreach($activeCategories as $cat)
                        <button
                            type="button"
                            wire:click="selectCategory({{ $cat->id }})"
                            class="chip-btn {{ $selectedCategoryId === $cat->id && empty($productSearch) ? 'active' : '' }}"
                        >
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- 3. Products Grid --}}
            <div class="products-grid">
                @forelse($categoryProducts as $prod)
                    <div wire:click="addToCart({{ $prod->id }})" class="product-card stagger-item">
                        @if(isset($cart[$prod->id]))
                            <div class="qty-badge-pulse">{{ $cart[$prod->id]['quantity'] }}</div>
                        @endif

                        <div class="product-image-container">
                            @if($prod->image_path)
                                <img src="{{ asset('storage/' . $prod->image_path) }}" alt="{{ $prod->name }}" class="product-image" loading="lazy">
                            @else
                                <div class="product-placeholder">
                                    <x-ui.icon name="bag" class="w-10 h-10" />
                                </div>
                            @endif
                        </div>

                        <div class="product-card-body">
                            <span class="product-title">{{ $prod->name }}</span>
                            @if($prod->description)
                                <span class="product-desc">{{ $prod->description }}</span>
                            @endif
                            <div class="product-card-footer">
                                <span class="product-price">@money($prod->price)</span>
                                <button type="button" class="btn-add-product" title="Agregar al pedido">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1;">
                        <x-ui.empty-state
                            title="No hay productos disponibles"
                            description="No se encontraron productos en esta categoría."
                            icon="bag"
                        />
                    </div>
                @endforelse
            </div>
        </div>

        {{-- RIGHT PANEL (30% Sticky Cart Panel) --}}
        <div class="pos-right">
            <div class="cart-panel">
                <div class="cart-header">
                    <span>Tu Pedido</span>
                    @if($selectedCustomerName)
                        <span style="font-size: 0.775rem; color: var(--primary); font-weight: 600;">{{ $selectedCustomerName }}</span>
                    @endif
                </div>

                <div class="cart-items-list">
                    @forelse($cart as $item)
                        <div class="cart-item-row">
                            <div class="cart-item-info">
                                <span class="cart-item-title">{{ $item['name'] }}</span>
                                <span class="cart-item-price">@money($item['price']) c/u</span>
                            </div>
                            <div class="cart-item-controls">
                                <button type="button" wire:click="decrementQty({{ $item['id'] }})" class="qty-control-btn">-</button>
                                <span class="qty-value">{{ $item['quantity'] }}</span>
                                <button type="button" wire:click="incrementQty({{ $item['id'] }})" class="qty-control-btn">+</button>
                                <button type="button" wire:click="removeFromCart({{ $item['id'] }})" class="btn-remove-item">
                                    <x-ui.icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <div style="margin: auto 0;">
                            <x-ui.empty-state
                                title="Tu pedido está vacío"
                                description="Selecciona productos del menú para comenzar."
                                icon="bag"
                            />
                        </div>
                    @endforelse
                </div>

                {{-- Notes --}}
                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <label class="form-label" style="font-size: 0.775rem;">Notas del Pedido</label>
                    <textarea
                        wire:model="notes"
                        rows="2"
                        class="form-input"
                        placeholder="Ej. Sin cebolla, salsa extra..."
                        style="resize: none; font-size: 0.825rem; height: 60px; padding: 8px 10px;"
                    ></textarea>
                </div>

                {{-- Cart Footer & Total --}}
                <div class="cart-footer">
                    <div class="cart-total-row">
                        <span class="cart-total-label">TOTAL</span>
                        <span class="cart-total-value">@money($this->cartTotal)</span>
                    </div>

                    <button
                        type="button"
                        wire:click="submitOrder"
                        wire:loading.attr="disabled"
                        wire:target="submitOrder"
                        wire:offline.attr="disabled"
                        class="btn-submit-order"
                        @if(empty($selectedCustomerName) || empty($cart)) disabled @endif
                    >
                        <span wire:loading wire:target="submitOrder" class="spinner"></span>
                        <span wire:loading.remove wire:target="submitOrder">ENVIAR A COCINA →</span>
                        <span wire:loading wire:target="submitOrder">ENVIANDO...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MOBILE FLOATING CART BAR --}}
    @if(count($cart) > 0)
        <div class="mobile-cart-float-bar">
            <div>
                <div class="mobile-cart-float-qty">{{ count($cart) }} {{ count($cart) === 1 ? 'producto' : 'productos' }}</div>
                <div class="mobile-cart-float-total">@money($this->cartTotal)</div>
            </div>
            <button type="button" @click="mobileCartOpen = true" class="btn-view-order-sheet">
                VER PEDIDO →
            </button>
        </div>
    @endif

    {{-- MOBILE BOTTOM SHEET MODAL --}}
    <div class="bottom-sheet-overlay" x-show="mobileCartOpen" x-transition.opacity @click.self="mobileCartOpen = false" style="display: none;">
        <div class="bottom-sheet-content" x-show="mobileCartOpen" x-transition.scale.95>
            <div class="bottom-sheet-handle"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">Tu Pedido</span>
                <button type="button" @click="mobileCartOpen = false" style="background: transparent; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer;">
                    &times;
                </button>
            </div>

            <div style="flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; max-height: 40vh;">
                @foreach($cart as $item)
                    <div class="cart-item-row">
                        <div class="cart-item-info">
                            <span class="cart-item-title">{{ $item['name'] }}</span>
                            <span class="cart-item-price">@money($item['price']) c/u</span>
                        </div>
                        <div class="cart-item-controls">
                            <button type="button" wire:click="decrementQty({{ $item['id'] }})" class="qty-control-btn">-</button>
                            <span class="qty-value">{{ $item['quantity'] }}</span>
                            <button type="button" wire:click="incrementQty({{ $item['id'] }})" class="qty-control-btn">+</button>
                            <button type="button" wire:click="removeFromCart({{ $item['id'] }})" class="btn-remove-item">
                                <x-ui.icon name="trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label class="form-label" style="font-size: 0.8rem;">Notas del Pedido</label>
                <textarea
                    wire:model="notes"
                    rows="2"
                    class="form-input"
                    placeholder="Notas adicionales..."
                    style="resize: none; font-size: 0.825rem; height: 50px;"
                ></textarea>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 0.75rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <div class="cart-total-row">
                    <span class="cart-total-label">TOTAL</span>
                    <span class="cart-total-value">@money($this->cartTotal)</span>
                </div>

                <button
                    type="button"
                    wire:click="submitOrder"
                    wire:loading.attr="disabled"
                    wire:target="submitOrder"
                    wire:offline.attr="disabled"
                    class="btn-submit-order"
                    @click="mobileCartOpen = false"
                >
                    <span wire:loading wire:target="submitOrder" class="spinner"></span>
                    <span wire:loading.remove wire:target="submitOrder">ENVIAR A COCINA →</span>
                    <span wire:loading wire:target="submitOrder">ENVIANDO...</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('focus-search-customer', () => {
                const el = document.getElementById('customerSearchInput');
                if(el) { el.value = ''; el.focus(); }
            });
            Livewire.on('focus-search-product', () => {
                const el = document.getElementById('productSearchInput');
                if(el) { el.value = ''; el.focus(); }
            });
        });
    </script>
</div>
