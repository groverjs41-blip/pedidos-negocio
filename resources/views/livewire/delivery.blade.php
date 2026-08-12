<div wire:poll.15s class="delivery-layout" style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
    <style>
        .section-heading {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delivery-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }
        .delivery-card.my-delivery {
            border-color: rgba(168, 85, 247, 0.3);
            background: var(--bg-elevated);
        }

        .delivery-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
        }
        .delivery-order-number { font-size: 1.15rem; font-weight: 800; }
        .delivery-time { font-size: 0.75rem; color: var(--text-muted); }

        .delivery-info-group { display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.95rem; }
        .info-row { display: flex; gap: 0.5rem; align-items: flex-start; }
        .info-label { color: var(--text-muted); min-width: 20px; }
        .info-value { color: var(--text-main); font-weight: 500; }

        .phone-link { color: var(--primary); text-decoration: none; font-weight: 700; }
        .phone-link:hover { text-decoration: underline; }

        .delivery-items-summary {
            font-size: 0.85rem;
            color: var(--text-muted);
            background: rgba(0,0,0,0.15);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .btn-delivery-action {
            width: 100%;
            border: none;
            padding: 0.85rem;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            transition: all 0.2s;
        }
        .btn-claim { background: var(--info); color: #0b1220; }
        .btn-claim:hover { background: #2563eb; }
        .btn-deliver { background: var(--success); color: #0b1220; }
        .btn-deliver:hover { background: #059669; }
        .btn-delivery-action:disabled { opacity: 0.5; cursor: not-allowed; }

        .no-delivery-orders {
            text-align: center;
            padding: 2rem;
            background: var(--bg-surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>

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

    <div style="border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
        <h1 style="font-size: 1.35rem; font-weight: 700;">🛵 Panel de Reparto</h1>
    </div>

    <!-- Section 1: Mis Entregas (Claimed by current user) -->
    <div>
        <h2 class="section-heading">
            <span>Mis Repartos Activos</span>
            <span class="delivery-badge-count active" style="font-size: 0.85rem; padding: 2px 10px;">{{ count($myDeliveries) }}</span>
        </h2>
        
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @forelse($myDeliveries as $order)
                <div class="delivery-card my-delivery">
                    <div class="delivery-card-header">
                        <span class="delivery-order-number">{{ $order->number }}</span>
                        <span class="delivery-time">Tomado: {{ $order->delivering_at->format('H:i') }}</span>
                    </div>

                    <div class="delivery-info-group">
                        <div class="info-row">
                            <span class="info-label">👤</span>
                            <span class="info-value">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>
                        @if($order->customer_phone_snapshot)
                            <div class="info-row">
                                <span class="info-label">📞</span>
                                <span class="info-value">
                                    <a href="tel:{{ $order->customer_phone_snapshot }}" class="phone-link">
                                        {{ $order->customer_phone_snapshot }}
                                    </a>
                                </span>
                            </div>
                        @endif
                        @if($order->delivery_address_snapshot)
                            <div class="info-row">
                                <span class="info-label">📍</span>
                                <span class="info-value">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">💵</span>
                            <span class="info-value" style="color: var(--primary); font-weight: 700;">
                                Total a cobrar: ${{ number_format($order->total, 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="delivery-items-summary">
                        🍔
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>

                    @if($order->notes)
                        <div style="font-size: 0.85rem; font-style: italic; color: var(--text-muted);">
                            Nota: "{{ $order->notes }}"
                        </div>
                    @endif

                    <div>
                        <button type="button" 
                                wire:click="markOrderDelivered({{ $order->id }})" 
                                wire:loading.attr="disabled"
                                wire:target="markOrderDelivered({{ $order->id }})"
                                class="btn-delivery-action btn-deliver">
                            <span wire:loading wire:target="markOrderDelivered({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="markOrderDelivered({{ $order->id }})">MARCAR COMO ENTREGADO</span>
                            <span wire:loading wire:target="markOrderDelivered({{ $order->id }})">Procesando...</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="no-delivery-orders">
                    No tienes repartos activos asignados. Toma un pedido de la lista de abajo para comenzar.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section 2: Pedidos Listos (Open for claiming) -->
    <div>
        <h2 class="section-heading">
            <span>Pedidos Listos para Retirar</span>
            <span class="delivery-badge-count" style="font-size: 0.85rem; padding: 2px 10px;">{{ count($readyOrders) }}</span>
        </h2>
        
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @forelse($readyOrders as $order)
                <div class="delivery-card">
                    <div class="delivery-card-header">
                        <span class="delivery-order-number">{{ $order->number }}</span>
                        <span class="delivery-time">Listo: {{ $order->ready_at->format('H:i') }}</span>
                    </div>

                    <div class="delivery-info-group">
                        <div class="info-row">
                            <span class="info-label">👤</span>
                            <span class="info-value">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>
                        @if($order->delivery_address_snapshot)
                            <div class="info-row">
                                <span class="info-label">📍</span>
                                <span class="info-value">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="delivery-items-summary">
                        🍔
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>

                    <div>
                        <button type="button" 
                                wire:click="claimOrder({{ $order->id }})" 
                                wire:loading.attr="disabled"
                                wire:target="claimOrder({{ $order->id }})"
                                class="btn-delivery-action btn-claim">
                            <span wire:loading wire:target="claimOrder({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="claimOrder({{ $order->id }})">TOMAR PEDIDO (REPARTIR)</span>
                            <span wire:loading wire:target="claimOrder({{ $order->id }})">Procesando...</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="no-delivery-orders">
                    No hay pedidos listos esperando reparto en este momento.
                </div>
            @endforelse
        </div>
    </div>
</div>
