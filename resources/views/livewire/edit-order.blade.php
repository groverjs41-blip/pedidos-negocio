<div class="pos-layout" x-data="{ mobileCartOpen: false }">
    <!-- Offline status warning -->
    <div wire:offline class="alert alert-danger">
        ⚠️ <strong>SIN CONEXIÓN:</strong> Los cambios NO se guardarán. El envío está temporalmente inhabilitado.
    </div>

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

    <div class="pos-container">
        <!-- LEFT PANEL (65% on Desktop) -->
        <div class="pos-left">
            <!-- Header back button -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                <h1 style="font-size: 1.25rem; font-weight: 700;">Editar Pedido {{ $order->number }}</h1>
                <a href="{{ route('pedidos.index') }}" class="category-btn" style="text-decoration: none;">
                    Volver
                </a>
            </div>

            <!-- 1. Customer Selection Area -->
            <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="login-label">Cliente del Pedido</label>
                    @if($selectedCustomerName)
                        <button type="button" wire:click="clearCustomer" style="background: transparent; border: none; color: var(--danger); font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                            ❌ Cambiar cliente
                        </button>
                    @endif
                </div>

                @if(!$selectedCustomerName)
                    <!-- Search Field -->
                    <div style="position: relative;">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchQuery" 
                            id="customerSearchInput"
                            class="login-input" 
                            placeholder="Buscar cliente por nombre o teléfono (min. 2 letras)..."
                            autocomplete="off"
                        >
                        <!-- Quick Counter Sale Button -->
                        <button type="button" wire:click="selectCounterSale" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: var(--primary-light); color: var(--primary); border: 1px solid rgba(245,158,11,0.3); border-radius: 8px; padding: 4px 10px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                            Mostrador
                        </button>
                    </div>

                    <!-- Search Results Dropdown -->
                    @if(count($this->customers) > 0)
                        <div style="background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 12px; margin-top: 0.25rem; overflow: hidden; z-index: 10; display: flex; flex-direction: column;">
                            @foreach($this->customers as $cust)
                                <button type="button" wire:click="selectCustomer({{ $cust->id }})" style="background: transparent; border: none; border-bottom: 1px solid var(--border); padding: 0.75rem 1rem; color: var(--text-main); text-align: left; cursor: pointer; display: flex; justify-content: space-between; align-items: center; width: 100%; transition: background 0.2s;">
                                    <div>
                                        <span style="font-weight: 600;">{{ $cust->name }}</span>
                                        @if($cust->phone) <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 0.5rem;">📞 {{ $cust->phone }}</span> @endif
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--primary); font-weight: 700;">Seleccionar</span>
                                </button>
                            @endforeach
                        </div>
                    @elseif(strlen($searchQuery) >= 2)
                        <div style="background: var(--bg-elevated); border: 1px solid var(--border); border-radius: 12px; margin-top: 0.25rem; padding: 0.75rem 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                            No se encontraron clientes activos con "{{ $searchQuery }}".
                        </div>
                    @endif
                @else
                    <!-- Customer Information Block -->
                    <div style="background: var(--bg-elevated); border: 1px solid var(--border); padding: 0.85rem 1rem; border-radius: 12px; display: flex; flex-direction: column; gap: 0.25rem;">
                        <div style="font-weight: 700; font-size: 1rem; color: var(--primary);">
                            👤 {{ $selectedCustomerName }}
                        </div>
                        @if($selectedCustomerPhone)
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                📞 Teléfono: <span style="color: var(--text-main); font-weight: 500;">{{ $selectedCustomerPhone }}</span>
                            </div>
                        @endif
                        @if($selectedCustomerAddress)
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                                📍 Dirección: <span style="color: var(--text-main); font-weight: 500;">{{ $selectedCustomerAddress }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- 2. Product Search & Categories Slider -->
            <div style="background: var(--bg-surface); padding: 1.25rem; border-radius: 16px; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex-grow: 1; min-width: 200px;">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="productSearch" 
                            id="productSearchInput"
                            class="login-input" 
                            placeholder="Buscar producto por nombre..."
                        >
                    </div>
                </div>

                <!-- Horizontal scrollable categories -->
                <div class="pos-categories">
                    @foreach($activeCategories as $cat)
                        <button 
                            type="button" 
                            wire:click="selectCategory({{ $cat->id }})" 
                            class="category-btn {{ $selectedCategoryId === $cat->id && empty($productSearch) ? 'active' : '' }}"
                        >
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>

                <!-- Products Grid -->
                <div class="pos-products">
                    @forelse($categoryProducts as $prod)
                        <div wire:click="addToCart({{ $prod->id }})" class="pos-product-card">
                            @if(isset($cart[$prod->id]))
                                <div class="pos-qty-badge">{{ $cart[$prod->id]['quantity'] }}</div>
                            @endif

                            @if($prod->image_path)
                                <img src="{{ asset('storage/' . $prod->image_path) }}" alt="{{ $prod->name }}" class="pos-product-image">
                            @else
                                <div class="pos-product-image-placeholder">
                                    <svg class="pos-product-placeholder-svg" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                                    </svg>
                                </div>
                            @endif

                            <div class="pos-product-info">
                                <span class="pos-product-name">{{ $prod->name }}</span>
                                <span class="pos-product-price">${{ number_format($prod->price, 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.9rem;">
                            No hay productos disponibles en esta categoría.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL (35% on Desktop - Cart Details) -->
        <div class="pos-right">
            <div class="pos-cart-panel">
                <h3 style="font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                    Carrito de Compra
                </h3>

                <div class="pos-cart-items">
                    @forelse($cart as $item)
                        <div class="pos-cart-item">
                            <div class="pos-cart-details">
                                <span class="pos-cart-name">{{ $item['name'] }}</span>
                                <span class="pos-cart-sub">${{ number_format($item['price'], 2) }} c/u</span>
                            </div>
                            <div class="pos-cart-actions">
                                <button type="button" wire:click="decrementQty({{ $item['id'] }})" class="pos-qty-btn">-</button>
                                <span class="pos-qty-val">{{ $item['quantity'] }}</span>
                                <button type="button" wire:click="incrementQty({{ $item['id'] }})" class="pos-qty-btn">+</button>
                                <button type="button" wire:click="removeFromCart({{ $item['id'] }})" class="pos-remove-btn">🗑️</button>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 2rem 0;">
                            El carrito está vacío.<br>Agrega productos haciendo clic en ellos.
                        </div>
                    @endforelse
                </div>

                <!-- Notes -->
                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <label class="login-label" style="font-size: 0.8rem;">Notas del Pedido</label>
                    <textarea 
                        wire:model="notes" 
                        rows="2" 
                        class="login-input" 
                        placeholder="Ej. Sin cebolla, entregar rápido..."
                        style="resize: none; font-size: 0.85rem; padding: 6px 10px;"
                    ></textarea>
                </div>

                <!-- Total and Submission -->
                <div class="pos-cart-footer">
                    <div class="pos-total-row">
                        <span>Total:</span>
                        <span class="pos-total-amount">${{ number_format($this->cartTotal, 2) }}</span>
                    </div>

                    <button 
                        type="button" 
                        wire:click="updateOrder"
                        wire:loading.attr="disabled"
                        wire:target="updateOrder"
                        wire:offline.attr="disabled"
                        class="btn-pos-submit"
                        @if(empty($selectedCustomerName) || empty($cart)) disabled @endif
                    >
                        <!-- Loading spinner during submission -->
                        <span wire:loading wire:target="updateOrder" class="spinner"></span>
                        <span wire:loading.remove wire:target="updateOrder">GUARDAR CAMBIOS</span>
                        <span wire:loading wire:target="updateOrder">GUARDANDO CAMBIOS...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBILE STICKY BOTTOM BAR -->
    @if(count($cart) > 0)
        <div class="mobile-cart-bar">
            <div class="mobile-cart-bar-info">
                <span class="mobile-cart-bar-qty">{{ count($cart) }} {{ count($cart) === 1 ? 'producto' : 'productos' }}</span>
                <span class="mobile-cart-bar-total">${{ number_format($this->cartTotal, 2) }}</span>
            </div>
            <button type="button" @click="mobileCartOpen = true" class="btn-mobile-cart-open">
                VER PEDIDO
            </button>
        </div>
    @endif

    <!-- MOBILE CART BOTTOM SHEET -->
    <div class="bottom-sheet-overlay" x-show="mobileCartOpen" x-transition.opacity @click.self="mobileCartOpen = false" style="display: none;">
        <div class="bottom-sheet-content" x-show="mobileCartOpen" x-transition.scale.95>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                <span style="font-weight: 700; font-size: 1.1rem;">Resumen del Pedido</span>
                <button type="button" @click="mobileCartOpen = false" style="background: transparent; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer; line-height: 1;">
                    &times;
                </button>
            </div>

            <!-- Items -->
            <div style="flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; max-height: 35vh;">
                @foreach($cart as $item)
                    <div class="pos-cart-item">
                        <div class="pos-cart-details">
                            <span class="pos-cart-name">{{ $item['name'] }}</span>
                            <span class="pos-cart-sub">${{ number_format($item['price'], 2) }} c/u</span>
                        </div>
                        <div class="pos-cart-actions">
                            <button type="button" wire:click="decrementQty({{ $item['id'] }})" class="pos-qty-btn">-</button>
                            <span class="pos-qty-val">{{ $item['quantity'] }}</span>
                            <button type="button" wire:click="incrementQty({{ $item['id'] }})" class="pos-qty-btn">+</button>
                            <button type="button" wire:click="removeFromCart({{ $item['id'] }})" class="pos-remove-btn">🗑️</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Notes -->
            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label class="login-label" style="font-size: 0.8rem;">Notas del Pedido</label>
                <textarea 
                    wire:model="notes" 
                    rows="2" 
                    class="login-input" 
                    placeholder="Notas adicionales..."
                    style="resize: none; font-size: 0.85rem;"
                ></textarea>
            </div>

            <!-- Total and send -->
            <div style="border-top: 1px solid var(--border); padding-top: 0.75rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <div class="pos-total-row">
                    <span>Total:</span>
                    <span class="pos-total-amount">${{ number_format($this->cartTotal, 2) }}</span>
                </div>

                <button 
                    type="button" 
                    wire:click="updateOrder"
                    wire:loading.attr="disabled"
                    wire:target="updateOrder"
                    wire:offline.attr="disabled"
                    class="btn-pos-submit"
                    @click="mobileCartOpen = false"
                >
                    <span wire:loading wire:target="updateOrder" class="spinner"></span>
                    <span wire:loading.remove wire:target="updateOrder">GUARDAR CAMBIOS</span>
                    <span wire:loading wire:target="updateOrder">GUARDANDO CAMBIOS...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Focus scripts -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('focus-search-customer', () => {
                const el = document.getElementById('customerSearchInput');
                if(el) {
                    el.value = '';
                    el.focus();
                }
            });

            Livewire.on('focus-search-product', () => {
                const el = document.getElementById('productSearchInput');
                if(el) {
                    el.value = '';
                    el.focus();
                }
            });
        });
    </script>
</div>
