<div class="pos-layout" x-data="{ mobileCartOpen: false }" @order-submitted-success.window="mobileCartOpen = false">
    {{-- Offline status warning (kept persistent as per rule 2) --}}
    <div wire:offline class="alert alert-danger">
        <x-ui.icon name="alert" class="w-4 h-4" />
        <span><strong>SIN CONEXIÓN:</strong> Este pedido NO se ha enviado a cocina.</span>
    </div>

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

            @if($this->activeDirectOrder)
                <div class="alert alert-warning" style="margin-bottom: 0.75rem; background: rgba(245, 158, 11, 0.15); border: 1px solid var(--warning-border); border-radius: var(--radius-md); padding: 0.75rem 1rem; color: var(--warning-text); font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <x-ui.icon name="alert" class="w-5 h-5" />
                    <span>⚡ VENTA EN PUESTO EN CURSO (Pedido #{{ $this->activeDirectOrder->number }}). Completa la venta actual para realizar otras operaciones.</span>
                </div>
            @endif

            {{-- 1. Compact Customer Selector Bar --}}
            <div class="customer-bar">
                @if(!$selectedCustomerName)
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-grow: 1;">
                        <div style="position: relative; flex-grow: 1;">
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
                        <button type="button" x-on:click="$dispatch('open-modal', 'quick-customer-modal')" class="chip-btn" style="height: 42px; padding: 0 0.85rem; font-weight: 700; white-space: nowrap;">
                            + Cliente
                        </button>
                        <button type="button" wire:click="selectCounterSale" class="btn-counter-sale" style="height: 42px; white-space: nowrap;">
                            Venta Mostrador
                        </button>
                    </div>
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
                                {{-- DEBT NOTICE --}}
                                @if(bccomp($this->selectedCustomerDebt, '0.00', 2) > 0)
                                    <div style="font-size: 0.775rem; color: #F87171; font-weight: 700; margin-top: 2px;">
                                        💳 Saldo pendiente: @money($this->selectedCustomerDebt)
                                    </div>
                                @endif

                                {{-- RETURNABLES PENDING & QUICK RETURN BUTTON --}}
                                @if(count($this->selectedCustomerReturnables) > 0)
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 4px; flex-wrap: wrap;">
                                        <span style="font-size: 0.775rem; color: var(--warning-text); font-weight: 700;">
                                            📦 ENVASES POR RECOGER: 
                                            @foreach($this->selectedCustomerReturnables as $index => $item)
                                                {{ $item['balance'] ?? $item['outstanding'] ?? 0 }}x {{ ($item['returnable_type'] ?? $item['type'])->name }}{{ $index < count($this->selectedCustomerReturnables) - 1 ? ', ' : '' }}
                                            @endforeach
                                        </span>
                                        <button type="button" wire:click="openReturnModal" class="px-2 py-0.5 text-xs font-bold rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 transition-all">
                                            [ REGISTRAR DEVOLUCIÓN ]
                                        </button>
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
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <div style="position: relative; flex-grow: 1;">
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
                    <button type="button" x-on:click="$dispatch('open-modal', 'quick-product-modal')" class="chip-btn" style="height: 44px; padding: 0 1rem; font-weight: 700; white-space: nowrap;">
                        + Producto
                    </button>
                </div>

                {{-- Horizontal Categories Chips --}}
                <div class="category-chips">
                    <button
                        type="button"
                        wire:click="selectCategory(0)"
                        class="chip-btn {{ (is_null($selectedCategoryId) || $selectedCategoryId === 0) && empty($productSearch) ? 'active' : '' }}"
                    >
                        TODOS
                    </button>
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
                    <div wire:key="product-{{ $prod->id }}" class="product-card stagger-item">
                        <button
                            type="button"
                            wire:click="addToCart({{ $prod->id }})"
                            class="product-card-hit-target"
                            aria-label="Agregar {{ $prod->name }} al pedido"
                        ></button>

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
                                <span class="btn-add-product" aria-hidden="true">
                                    +
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1;">
                        <x-ui.empty-state
                            title="No hay productos disponibles"
                            description="Selecciona otra categoría o agrega un nuevo producto."
                            icon="bag"
                        />
                    </div>
                @endforelse
            </div>
        </div>

        {{-- MOBILE BACKDROP FOR CART SHEET --}}
        <div x-show="mobileCartOpen" @click="mobileCartOpen = false" x-transition.opacity class="lg:hidden" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 99994; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);" x-cloak></div>

        {{-- RIGHT PANEL (30% Sticky Cart Panel) --}}
        <div class="pos-right" :class="{ 'mobile-cart-open': mobileCartOpen }">
            @if($this->activeDirectOrder)
                @php $directOrder = $this->activeDirectOrder; @endphp
                <div class="cart-panel" style="border: 2px solid var(--primary); background: var(--bg-card);">
                    <div class="cart-header" style="background: rgba(16, 185, 129, 0.15); border-bottom: 1px solid var(--primary-border); padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--primary); display: block;">
                                ⚡ VENTA EN PUESTO
                            </span>
                            <span style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">
                                Pedido #{{ $directOrder->number }}
                            </span>
                        </div>
                        <button type="button" @click="mobileCartOpen = false" class="lg:hidden text-slate-400 hover:text-white font-bold text-xl leading-none">&times;</button>
                    </div>

                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem; flex-grow: 1; overflow-y: auto;">
                        {{-- Order Summary --}}
                        <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                                CLIENTE: {{ $directOrder->customer_name_snapshot ?? 'Venta Mostrador' }}
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                @foreach($directOrder->items as $i)
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-main);">
                                        <span>{{ $i->quantity }}x {{ $i->product_name }}</span>
                                        <span style="font-weight: 600;">@money($i->line_total)</span>
                                    </div>
                                @endforeach
                            </div>
                            <div style="border-top: 1px dashed var(--border); margin-top: 0.5rem; padding-top: 0.5rem; display: flex; justify-content: space-between; font-weight: 800; font-size: 1rem; color: var(--primary);">
                                <span>TOTAL</span>
                                <span>@money($directOrder->total)</span>
                            </div>
                        </div>

                        {{-- Stepper Actions --}}
                        @if($directOrder->status === \App\Enums\OrderStatus::NEW)
                            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-lg); padding: 1rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; text-align: center;">
                                <div style="font-size: 1.05rem; font-weight: 800; color: #F59E0B; letter-spacing: 0.02em;">
                                    📋 PEDIDO REGISTRADO
                                </div>
                                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                                    Listo para iniciar preparación en puesto
                                </div>
                                <button
                                    type="button"
                                    wire:click="startDirectPreparing"
                                    wire:loading.attr="disabled"
                                    class="btn-primary"
                                    style="width: 100%; height: 46px; font-weight: 800; font-size: 0.95rem; background: #F59E0B; color: #000000; border: none; border-radius: var(--radius-md); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);"
                                >
                                    <span wire:loading wire:target="startDirectPreparing" class="spinner"></span>
                                    <span wire:loading.remove wire:target="startDirectPreparing">🍳 INICIAR PREPARACIÓN →</span>
                                </button>
                            </div>
                        @elseif($directOrder->status === \App\Enums\OrderStatus::PREPARING)
                            <div style="background: rgba(245, 158, 11, 0.12); border: 2px solid #F59E0B; border-radius: var(--radius-lg); padding: 1.25rem 1rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; text-align: center; box-shadow: 0 8px 24px rgba(245, 158, 11, 0.15);">
                                <div style="font-size: 1.2rem; font-weight: 900; color: #F59E0B; letter-spacing: 0.03em; text-transform: uppercase;">
                                    🍳 PREPARANDO PEDIDO
                                </div>
                                
                                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); font-family: monospace; background: var(--bg-surface); padding: 0.25rem 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                                    #{{ $directOrder->number }}
                                </div>

                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 800; color: #F59E0B; font-size: 0.9rem;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #F59E0B; box-shadow: 0 0 8px #F59E0B;"></span>
                                    <span>EN PREPARACIÓN</span>
                                </div>

                                @if($directOrder->preparing_at)
                                    <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                                        Iniciado {{ $directOrder->preparing_at->diffForHumans() }}
                                    </div>
                                @endif

                                <button
                                    type="button"
                                    wire:click="markDirectDelivered"
                                    wire:loading.attr="disabled"
                                    class="btn-primary"
                                    style="width: 100%; height: 48px; font-weight: 800; font-size: 0.95rem; background: #059669; color: #FFFFFF; border: none; border-radius: var(--radius-md); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); margin-top: 0.25rem;"
                                >
                                    <span wire:loading wire:target="markDirectDelivered" class="spinner"></span>
                                    <span wire:loading.remove wire:target="markDirectDelivered">📦 ENTREGAR PEDIDO →</span>
                                </button>
                            </div>
                        @elseif($directOrder->status === \App\Enums\OrderStatus::DELIVERED)
                            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-md); padding: 0.75rem 1rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.35rem; text-align: center;">
                                <div style="font-size: 0.95rem; font-weight: 800; color: #10B981;">
                                    📦 PEDIDO ENTREGADO
                                </div>
                                <div style="font-size: 0.775rem; font-weight: 600; color: var(--text-muted);">
                                    Listo para registrar envases y cobrar
                                </div>
                            </div>
                            {{-- Envases Retornables Sección --}}
                            @if($directOrder->customer_id && count($directOrder->returnablePlans) > 0)
                                <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.5rem;">
                                    <div style="font-size: 0.8rem; font-weight: 800; color: var(--warning-text); text-transform: uppercase;">
                                        📦 ENVASES ENTREGADOS AL CLIENTE
                                    </div>
                                    @if($directReturnablesRecorded || $directOrder->returnableMovements()->where('movement_type', 'OUT')->exists())
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">
                                            ✓ Envases registrados para este pedido
                                        </div>
                                    @elseif($directReturnablesHandled)
                                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                                            ✓ Se continuó sin dejar envases
                                        </div>
                                    @else
                                        @foreach($directOrder->returnablePlans as $plan)
                                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                                <span>{{ $plan->returnableType->name }}</span>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    wire:model="directReturnableQuantities.{{ $plan->returnable_type_id }}"
                                                    style="width: 70px; height: 34px; text-align: center;"
                                                    class="form-input"
                                                />
                                            </div>
                                        @endforeach
                                        <div style="display: flex; gap: 0.5rem; margin-top: 4px;">
                                            <button
                                                type="button"
                                                wire:click="recordDirectReturnables"
                                                wire:loading.attr="disabled"
                                                class="chip-btn"
                                                style="flex: 1; justify-content: center; font-weight: 700; background: var(--bg-card); height: 36px;"
                                            >
                                                <span wire:loading wire:target="recordDirectReturnables" class="spinner"></span>
                                                <span wire:loading.remove wire:target="recordDirectReturnables">📦 Registrar Envases</span>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="skipDirectReturnables"
                                                wire:loading.attr="disabled"
                                                class="chip-btn"
                                                style="justify-content: center; font-weight: 600; background: transparent; border: 1px solid var(--border); color: var(--text-muted); height: 36px; font-size: 0.75rem;"
                                            >
                                                Continuar sin dejar envases
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Payment Form --}}
                            <div style="display: flex; flex-direction: column; gap: 0.85rem; background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                                <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-main); text-align: center; text-transform: uppercase;">
                                    💳 COBRO DE VENTA EN PUESTO
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 700;">
                                    <span>Saldo Pendiente:</span>
                                    <span style="color: var(--danger-text);">@money($directOrder->outstandingBalance())</span>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                    <label class="form-label" style="font-size: 0.775rem;">Método de Pago</label>
                                    <select wire:model="directPaymentMethod" class="form-input" style="height: 38px; font-size: 0.85rem;">
                                        <option value="CASH">Efectivo</option>
                                        <option value="QR">QR</option>
                                        <option value="TRANSFER">Transferencia</option>
                                        <option value="CARD">Tarjeta</option>
                                        <option value="OTHER">Otro</option>
                                    </select>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                    <label class="form-label" style="font-size: 0.775rem;">Monto a cobrar</label>
                                    <input type="number" step="0.01" wire:model="directPaymentAmount" class="form-input" style="height: 38px; font-size: 0.85rem; font-weight: 700;">
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                    <label class="form-label" style="font-size: 0.775rem;">Referencia (Opcional)</label>
                                    <input type="text" wire:model="directPaymentReference" class="form-input" placeholder="Nro de comprobante..." style="height: 38px; font-size: 0.85rem;">
                                </div>

                                <button
                                    type="button"
                                    wire:click="submitDirectPayment"
                                    wire:loading.attr="disabled"
                                    class="btn-primary"
                                    style="height: 44px; font-weight: 800; font-size: 0.95rem; background: var(--primary);"
                                >
                                    <span wire:loading wire:target="submitDirectPayment" class="spinner"></span>
                                    <span wire:loading.remove wire:target="submitDirectPayment">💵 COBRAR @money($directPaymentAmount ?: '0.00')</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="cart-panel">
                    <div class="cart-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span>Tu Pedido</span>
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
                                    title="Tu pedido está vacío"
                                    description="Selecciona productos del menú para comenzar."
                                    icon="bag"
                                />
                            </div>
                        @endforelse
                    </div>

                    {{-- Service Mode Selector --}}
                    <div class="service-mode-selector" style="margin-bottom: 0.5rem;">
                        <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">
                            TIPO DE ATENCIÓN
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; background: var(--bg-surface); padding: 4px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                            <button
                                type="button"
                                wire:click="setServiceMode('KITCHEN')"
                                class="chip-btn {{ $serviceMode === 'KITCHEN' ? 'active' : '' }}"
                                style="justify-content: center; font-weight: 700; height: 36px; font-size: 0.8rem;"
                            >
                                🍳 Enviar a cocina
                            </button>
                            <button
                                type="button"
                                wire:click="setServiceMode('DIRECT')"
                                class="chip-btn {{ $serviceMode === 'DIRECT' ? 'active' : '' }}"
                                style="justify-content: center; font-weight: 700; height: 36px; font-size: 0.8rem;"
                            >
                                ⚡ Venta en puesto
                            </button>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label" style="font-size: 0.775rem;">Notas del Pedido</label>
                        <textarea
                            wire:model="notes"
                            rows="2"
                            class="form-input"
                            placeholder="Ej. Sin cebolla, salsa extra..."
                            style="resize: none; font-size: 0.825rem; height: 50px; padding: 6px 10px;"
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
                            <span wire:loading.remove wire:target="submitOrder">
                                {{ $serviceMode === 'DIRECT' ? 'INICIAR VENTA EN PUESTO →' : 'ENVIAR A COCINA →' }}
                            </span>
                            <span wire:loading wire:target="submitOrder">PROCESANDO...</span>
                        </button>
                    </div>
                </div>
            @endif
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

    {{-- MODAL CLIENTE RÁPIDO --}}
    <x-ui.modal name="quick-customer-modal" title="Nuevo Cliente Rápido" maxWidth="md">
        <form wire:submit.prevent="createQuickCustomer" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Nombre del Cliente *</label>
                <input type="text" wire:model="quickCustomerName" class="form-input" placeholder="Ej. Carlos Pérez" required>
                @error('quickCustomerName') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Teléfono</label>
                <input type="text" wire:model="quickCustomerPhone" class="form-input" placeholder="Ej. 70012345">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Dirección</label>
                <input type="text" wire:model="quickCustomerAddress" class="form-input" placeholder="Ej. Av. Principal #123">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Referencia de Ubicación</label>
                <input type="text" wire:model="quickCustomerRef" class="form-input" placeholder="Ej. Casa portón verde junto a la plaza">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" wire:loading.attr="disabled" x-on:click="$dispatch('close-modal', 'quick-customer-modal')" class="chip-btn" style="padding: 0.5rem 1rem;">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="createQuickCustomer" class="btn-primary" style="height: 38px; padding: 0 1.25rem;">
                    <span wire:loading wire:target="createQuickCustomer" class="spinner"></span>
                    <span wire:loading.remove wire:target="createQuickCustomer">Guardar y Seleccionar</span>
                    <span wire:loading wire:target="createQuickCustomer">CREANDO...</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    {{-- MODAL PRODUCTO RÁPIDO --}}
    <x-ui.modal name="quick-product-modal" title="Nuevo Producto Rápido" maxWidth="md">
        <form wire:submit.prevent="createQuickProduct" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Nombre del Producto *</label>
                <input type="text" wire:model="quickProductName" class="form-input" placeholder="Ej. Sándwich de Pollo Especial" required>
                @error('quickProductName') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="form-label">Categoría *</label>
                    <button type="button" x-on:click="$dispatch('open-modal', 'quick-prod-cat-modal')" class="chip-btn" style="padding: 2px 8px; font-size: 0.75rem;">
                        + Nueva Categoría
                    </button>
                </div>
                <select wire:model="quickProductCategoryId" class="form-input" required>
                    @foreach($activeCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('quickProductCategoryId') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Precio *</label>
                <input type="number" step="0.01" wire:model="quickProductPrice" class="form-input" placeholder="0.00" required>
                @error('quickProductPrice') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            {{-- SECCIÓN OPTATIVA: ENVASE RETORNABLE (Requerimientos 9, 10, 11) --}}
            <div style="border-top: 1px dashed var(--border); padding-top: 0.75rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                    ENVASE RETORNABLE (OPCIONAL)
                </span>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem; align-items: flex-end;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="form-label" style="font-size: 0.775rem;">Tipo de envase</label>
                            <button type="button" x-on:click="$dispatch('open-modal', 'quick-returnable-type-modal')" class="chip-btn" style="padding: 2px 8px; font-size: 0.75rem;">
                                + Nuevo
                            </button>
                        </div>
                        <select wire:model="quickProdReturnableTypeId" class="form-input" style="font-size: 0.85rem;">
                            <option value="">Sin envase obligatorio</option>
                            @foreach($returnableTypes as $rt)
                                <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label" style="font-size: 0.775rem;">Cant. por unidad</label>
                        <input type="number" min="1" wire:model="quickProdReturnableQty" class="form-input" style="font-size: 0.85rem;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" wire:loading.attr="disabled" x-on:click="$dispatch('close-modal', 'quick-product-modal')" class="chip-btn" style="padding: 0.5rem 1rem;">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="createQuickProduct" class="btn-primary" style="height: 38px; padding: 0 1.25rem;">
                    <span wire:loading wire:target="createQuickProduct" class="spinner"></span>
                    <span wire:loading.remove wire:target="createQuickProduct">Guardar y Agregar</span>
                    <span wire:loading wire:target="createQuickProduct">CREANDO...</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    {{-- MODAL CATEGORÍA RÁPIDA (DENTRO DE PRODUCTO RÁPIDO) --}}
    <x-ui.modal name="quick-prod-cat-modal" title="Nueva Categoría" maxWidth="sm">
        <form wire:submit.prevent="createQuickProductCat" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Nombre de Categoría *</label>
                <input type="text" wire:model="quickProductCatName" class="form-input" placeholder="Ej. Sándwiches" required>
                @error('quickProductCatName') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" wire:loading.attr="disabled" x-on:click="$dispatch('close-modal', 'quick-prod-cat-modal')" class="chip-btn" style="padding: 0.5rem 1rem;">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="createQuickProductCat" class="btn-primary" style="height: 38px; padding: 0 1.25rem;">
                    <span wire:loading wire:target="createQuickProductCat" class="spinner"></span>
                    <span wire:loading.remove wire:target="createQuickProductCat">Guardar</span>
                    <span wire:loading wire:target="createQuickProductCat">CREANDO...</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    {{-- MODAL NUEVO TIPO DE ENVASE (DENTRO DE PRODUCTO RÁPIDO - Requerimiento 10) --}}
    <x-ui.modal name="quick-returnable-type-modal" title="Nuevo Tipo de Envase" maxWidth="sm">
        <form wire:submit.prevent="createQuickReturnableType" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Nombre del Envase *</label>
                <input type="text" wire:model="quickReturnableName" class="form-input" placeholder="Ej. Taza, Botella de Vidrio" required>
                @error('quickReturnableName') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Orden</label>
                <input type="number" min="0" wire:model="quickReturnableSortOrder" class="form-input" placeholder="0">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" wire:loading.attr="disabled" x-on:click="$dispatch('close-modal', 'quick-returnable-type-modal')" class="chip-btn" style="padding: 0.5rem 1rem;">
                    Cancelar
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="createQuickReturnableType" class="btn-primary" style="height: 38px; padding: 0 1.25rem;">
                    <span wire:loading wire:target="createQuickReturnableType" class="spinner"></span>
                    <span wire:loading.remove wire:target="createQuickReturnableType">Guardar Envase</span>
                    <span wire:loading wire:target="createQuickReturnableType">CREANDO...</span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    {{-- MODAL DEVOLUCIÓN RÁPIDA (CONSERVA EL CARRITO ACTIVO) --}}
    @if($showReturnModal)
        <div class="modal-overlay" wire:click.self="closeReturnModal">
            <div class="modal-content" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">Registrar Devolución de Envases</h3>
                    <button type="button" wire:click="closeReturnModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Cliente: <strong>{{ $selectedCustomerName }}</strong>
                </div>

                <form wire:submit.prevent="submitQuickReturn" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 0.75rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                        @foreach($this->selectedCustomerReturnables as $item)
                            <div style="background: var(--bg-surface); padding: 0.75rem 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">{{ ($item['returnable_type'] ?? $item['type'])->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--warning-text);">Pendientes: {{ $item['balance'] ?? $item['outstanding'] ?? 0 }}</div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input
                                        type="number"
                                        min="0"
                                        max="{{ $item['balance'] ?? $item['outstanding'] ?? 999 }}"
                                        wire:model="returnQuantities.{{ ($item['returnable_type'] ?? $item['type'])->id }}"
                                        style="width: 70px; height: 36px; text-align: center;"
                                        class="form-input"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem;">
                        <button type="button" wire:click="closeReturnModal" class="btn-secondary" style="padding: 0.5rem 1rem;">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary" style="padding: 0.5rem 1.25rem;">
                            <span wire:loading wire:target="submitQuickReturn" class="spinner"></span>
                            <span wire:loading.remove wire:target="submitQuickReturn">REGISTRAR DEVOLUCIÓN</span>
                            <span wire:loading wire:target="submitQuickReturn">REGISTRANDO...</span>
                        </button>
                    </div>
                </form>
            </div>
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
