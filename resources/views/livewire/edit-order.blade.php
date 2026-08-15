<div class="pos-layout" x-data="{ mobileCartOpen: false }">
    {{-- Offline status warning --}}
    <div wire:offline class="alert alert-danger">
        <x-ui.icon name="alert" class="w-4 h-4" />
        <span><strong>SIN CONEXIÓN:</strong> Los cambios NO se guardarán.</span>
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
        {{-- LEFT PANEL --}}
        <div class="pos-left">
            <div class="page-header" style="margin-bottom: 0.75rem;">
                <div>
                    <h1 class="page-header-title">
                        <div class="header-icon-wrap mint">
                            <x-ui.icon name="list" class="w-5 h-5" />
                        </div>
                        Editar Pedido {{ $order->number }}
                    </h1>
                </div>
                <a href="{{ route('pedidos.index') }}" class="chip-btn" style="text-decoration: none;">
                    Volver
                </a>
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
                        </div>
                    </div>
                    <button type="button" wire:click="clearCustomer" style="background: transparent; border: none; color: var(--danger-text); font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                        Cambiar
                    </button>
                @endif
            </div>

            @if(!$selectedCustomerName && count($this->customers) > 0)
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-md); margin-top: -0.5rem; overflow: hidden; z-index: 10; display: flex; flex-direction: column;">
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

            {{-- 2. Product Search & Categories --}}
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
                        placeholder="Buscar productos..."
                    >
                </div>

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
                            @if($prod->image)
                                <img src="{{ asset('storage/' . $prod->image) }}" alt="{{ $prod->name }}" class="product-image" loading="lazy">
                            @else
                                <div class="product-placeholder">
                                    <x-ui.icon name="bag" class="w-10 h-10" />
                                </div>
                            @endif
                        </div>

                        <div class="product-card-body">
                            <span class="product-title">{{ $prod->name }}</span>
                            @if($prod->notes)
                                <span class="product-desc">{{ $prod->notes }}</span>
                            @endif
                            <div class="product-card-footer">
                                <span class="product-price">@money($prod->price)</span>
                                <button type="button" class="btn-add-product">
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

        {{-- MOBILE BACKDROP FOR CART SHEET --}}
        <div x-show="mobileCartOpen" @click="mobileCartOpen = false" x-transition.opacity class="lg:hidden" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 99994; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"></div>

        {{-- RIGHT PANEL Cart --}}
        <div class="pos-right" :class="{ 'mobile-cart-open': mobileCartOpen }">
            <div class="cart-panel">
                <div class="cart-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span>Editar Pedido</span>
                        @if($selectedCustomerName)
                            <span style="font-size: 0.775rem; color: var(--primary); font-weight: 600; margin-left: 0.5rem;">{{ $selectedCustomerName }}</span>
                        @endif
                    </div>
                    <button type="button" @click="mobileCartOpen = false" class="lg:hidden text-slate-400 hover:text-white font-bold text-xl leading-none">&times;</button>
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
                                title="El pedido está vacío"
                                description="Agrega productos para actualizar."
                                icon="bag"
                            />
                        </div>
                    @endforelse
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <label class="form-label" style="font-size: 0.775rem;">Notas del Pedido</label>
                    <textarea
                        wire:model="notes"
                        rows="2"
                        class="form-input"
                        placeholder="Ej. Sin cebolla..."
                        style="resize: none; font-size: 0.825rem; height: 60px; padding: 8px 10px;"
                    ></textarea>
                </div>

                <div class="cart-footer">
                    <div class="cart-total-row">
                        <span class="cart-total-label">TOTAL</span>
                        <span class="cart-total-value">@money($this->cartTotal)</span>
                    </div>

                    <button
                        type="button"
                        wire:click="updateOrder"
                        wire:loading.attr="disabled"
                        wire:target="updateOrder"
                        wire:offline.attr="disabled"
                        class="btn-submit-order"
                        @if(empty($selectedCustomerName) || empty($cart)) disabled @endif
                    >
                        <span wire:loading wire:target="updateOrder" class="spinner"></span>
                        <span wire:loading.remove wire:target="updateOrder">GUARDAR CAMBIOS →</span>
                        <span wire:loading wire:target="updateOrder">GUARDANDO...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MOBILE FLOATING CART BAR --}}
    @if(count($cart) > 0)
        <div class="mobile-cart-float-bar lg:hidden">
            <div>
                <div class="mobile-cart-float-qty">{{ count($cart) }} {{ count($cart) === 1 ? 'producto' : 'productos' }}</div>
                <div class="mobile-cart-float-total">@money($this->cartTotal)</div>
            </div>
            <button type="button" @click="mobileCartOpen = true" class="btn-view-order-sheet">
                VER PEDIDO →
            </button>
        </div>
    @endif

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
