<div wire:poll.15s class="delivery-layout" style="max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem; width: 100%;">

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

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap violet">
                    <x-ui.icon name="truck" class="w-5 h-5" />
                </div>
                Panel de Reparto
            </h1>
            <div class="page-header-subtitle">Gestión de despachos y entregas</div>
        </div>
    </div>

    {{-- Section 1: Mis Repartos Activos --}}
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
                Mis Repartos Activos
            </h2>
            <span class="status-badge DELIVERING">{{ count($myDeliveries) }}</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
            @forelse($myDeliveries as $order)
                <div class="card stagger-item" style="padding: 1.25rem; border-left: 4px solid var(--violet); display: flex; flex-direction: column; gap: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                        <span style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">{{ $order->number }}</span>
                        <span style="font-size: 0.775rem; color: var(--text-muted);">Tomado: {{ $order->delivering_at->format('H:i') }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.9rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <x-ui.icon name="user" class="w-4 h-4 text-muted" />
                            <span style="color: var(--text-main); font-weight: 600;">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>

                        @if($order->customer_phone_snapshot)
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <x-ui.icon name="phone" class="w-4 h-4 text-muted" />
                                    <span style="color: var(--text-main);">{{ $order->customer_phone_snapshot }}</span>
                                </div>
                                <a href="tel:{{ $order->customer_phone_snapshot }}" class="chip-btn" style="text-decoration: none; padding: 3px 10px; font-size: 0.75rem; background: var(--info-light); color: var(--info-text); border-color: rgba(77, 159, 255, 0.2);">
                                    📞 LLAMAR
                                </a>
                            </div>
                        @endif

                        @if($order->delivery_address_snapshot)
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <x-ui.icon name="map-pin" class="w-4 h-4 text-muted" style="margin-top: 2px;" />
                                <span style="color: var(--text-main);">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif

                        <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.2rem;">
                            <x-ui.icon name="dollar" class="w-4 h-4 text-muted" />
                            <span style="color: var(--primary); font-weight: 800; font-size: 1.05rem;">
                                A COBRAR: @money($order->total)
                            </span>
                        </div>
                    </div>

                    <div style="font-size: 0.8rem; color: var(--text-muted); background: var(--bg-surface); padding: 0.6rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>

                    @if($order->notes)
                        <div style="font-size: 0.825rem; font-style: italic; color: var(--warning-text);">
                            Nota: "{{ $order->notes }}"
                        </div>
                    @endif

                    <button type="button"
                            wire:click="markOrderDelivered({{ $order->id }})"
                            wire:loading.attr="disabled"
                            wire:target="markOrderDelivered({{ $order->id }})"
                            class="btn-primary"
                            style="width: 100%; height: 50px; background: var(--success); color: #07110D; font-size: 0.95rem; font-weight: 800;">
                        <span wire:loading wire:target="markOrderDelivered({{ $order->id }})" class="spinner"></span>
                        <span wire:loading.remove wire:target="markOrderDelivered({{ $order->id }})">✓ MARCAR COMO ENTREGADO</span>
                        <span wire:loading wire:target="markOrderDelivered({{ $order->id }})">Procesando...</span>
                    </button>
                </div>
            @empty
                <x-ui.empty-state
                    title="Sin repartos activos"
                    description="Toma un pedido de la lista de abajo para comenzar la entrega."
                    icon="truck"
                />
            @endforelse
        </div>
    </div>

    {{-- Section 2: Pedidos Listos para Retirar --}}
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
                Pedidos Listos para Retirar
            </h2>
            <span class="status-badge READY">{{ count($readyOrders) }}</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
            @forelse($readyOrders as $order)
                <div class="card stagger-item" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                        <span style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">{{ $order->number }}</span>
                        <span style="font-size: 0.775rem; color: var(--text-muted);">Listo: {{ $order->ready_at->format('H:i') }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.875rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <x-ui.icon name="user" class="w-4 h-4 text-muted" />
                            <span style="color: var(--text-main); font-weight: 600;">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>
                        @if($order->delivery_address_snapshot)
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <x-ui.icon name="map-pin" class="w-4 h-4 text-muted" style="margin-top: 2px;" />
                                <span style="color: var(--text-main);">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif
                    </div>

                    <div style="font-size: 0.8rem; color: var(--text-muted); background: var(--bg-surface); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>

                    <button type="button"
                            wire:click="claimOrder({{ $order->id }})"
                            wire:loading.attr="disabled"
                            wire:target="claimOrder({{ $order->id }})"
                            class="btn-primary"
                            style="width: 100%; height: 48px; background: var(--info); color: #0E141B; font-size: 0.9rem; font-weight: 800;">
                        <span wire:loading wire:target="claimOrder({{ $order->id }})" class="spinner"></span>
                        <span wire:loading.remove wire:target="claimOrder({{ $order->id }})">TOMAR PEDIDO (REPARTIR)</span>
                        <span wire:loading wire:target="claimOrder({{ $order->id }})">Procesando...</span>
                    </button>
                </div>
            @empty
                <x-ui.empty-state
                    title="No hay pedidos listos"
                    description="No hay comandas esperando reparto en este momento."
                    icon="truck"
                />
            @endforelse
        </div>
    </div>

    {{-- Returnable Prompt Modal --}}
    @if($showReturnablePrompt && $promptOrder)
        <div class="modal-overlay" wire:click.self="closePrompt">
            <div class="modal-content" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">¿Dejaste envases con el cliente?</h3>
                    <button type="button" wire:click="closePrompt" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    Cliente: <strong>{{ $promptOrder->customer_name_snapshot }}</strong> (Pedido {{ $promptOrder->number }})
                </div>

                <form wire:submit.prevent="registerLeftContainers" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                        @foreach($activeReturnableTypes as $t)
                            <div style="background: var(--bg-surface); padding: 0.75rem 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">{{ $t->name }}</span>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input
                                        type="number"
                                        min="0"
                                        max="50"
                                        wire:model="outQuantities.{{ $t->id }}"
                                        class="form-input"
                                        style="width: 70px; text-align: center; font-weight: 800;"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                        <button type="button" wire:click="closePrompt" class="chip-btn" style="flex: 1; height: 44px; text-align: center;">No dejé envases</button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="registerLeftContainers"
                            class="btn-primary"
                            style="flex: 1; height: 44px; font-size: 0.85rem;"
                        >
                            <span wire:loading wire:target="registerLeftContainers" class="spinner"></span>
                            <span wire:loading.remove wire:target="registerLeftContainers">REGISTRAR ENVASES</span>
                            <span wire:loading wire:target="registerLeftContainers">PROCESANDO...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
