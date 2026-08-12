<div wire:poll.15s class="delivery-layout" style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">

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

    <div class="page-header">
        <h1 class="page-title">
            <span class="page-title-icon violet">
                <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </span>
            Panel de Reparto
        </h1>
    </div>

    {{-- Section 1: My Active Deliveries --}}
    <div>
        <h2 class="section-heading">
            <span>Mis Repartos Activos</span>
            <span class="delivery-badge-count active">{{ count($myDeliveries) }}</span>
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
                            <span class="info-label">
                                <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </span>
                            <span class="info-value">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>
                        @if($order->customer_phone_snapshot)
                            <div class="info-row">
                                <span class="info-label">
                                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                </span>
                                <span class="info-value">
                                    <a href="tel:{{ $order->customer_phone_snapshot }}" class="phone-link">
                                        {{ $order->customer_phone_snapshot }}
                                    </a>
                                </span>
                            </div>
                        @endif
                        @if($order->delivery_address_snapshot)
                            <div class="info-row">
                                <span class="info-label">
                                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                </span>
                                <span class="info-value">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">
                                <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2;"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </span>
                            <span class="info-value" style="color: var(--primary); font-weight: 700;">
                                Total a cobrar: ${{ number_format($order->total, 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="delivery-items-summary">
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>

                    @if($order->notes)
                        <div style="font-size: 0.825rem; font-style: italic; color: var(--text-muted);">
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

    {{-- Section 2: Ready Orders --}}
    <div>
        <h2 class="section-heading">
            <span>Pedidos Listos para Retirar</span>
            <span class="delivery-badge-count">{{ count($readyOrders) }}</span>
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
                            <span class="info-label">
                                <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </span>
                            <span class="info-value">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>
                        @if($order->delivery_address_snapshot)
                            <div class="info-row">
                                <span class="info-label">
                                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                </span>
                                <span class="info-value">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="delivery-items-summary">
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
