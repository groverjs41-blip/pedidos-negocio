<div wire:poll.15s class="kitchen-layout" style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

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
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap warning">
                    <x-ui.icon name="chef" class="w-5 h-5" />
                </div>
                Cocina KDS
                <span class="status-badge PREPARING" style="font-size: 0.8rem; padding: 4px 12px; margin-left: 0.5rem;">{{ count($orders) }} pendientes</span>
            </h1>
            <div class="page-header-subtitle">Comandas de preparación en tiempo real</div>
        </div>
    </div>

    {{-- Active orders --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
        @forelse($orders as $order)
            @php
                $elapsedMinutes = now()->diffInMinutes($order->ordered_at);
                $isDelayed = $elapsedMinutes >= 15;
            @endphp
            <div class="kds-card {{ $order->status === \App\Enums\OrderStatus::PREPARING ? 'status-preparing' : '' }} stagger-item">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 0.85rem;">
                    <div>
                        <div class="kds-order-num">{{ $order->number }}</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                            👤 {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}
                        </div>
                    </div>

                    <span class="{{ $isDelayed ? 'delayed-badge' : 'status-badge NEW' }}">
                        <x-ui.icon name="clock" class="w-3.5 h-3.5" />
                        {{ $isDelayed ? 'DEMORADO' : '' }} {{ $elapsedMinutes }}m
                    </span>
                </div>

                {{-- Items List (LARGE TEXT FOR KITCHEN READABILITY) --}}
                <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                    @foreach($order->items as $item)
                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                            <span class="kds-item-qty">{{ $item->quantity }}x</span>
                            <span class="kds-item-name">{{ $item->product_name }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Special notes --}}
                @if($order->notes)
                    <div style="background: var(--warning-light); border: 1px solid rgba(245, 185, 66, 0.2); border-left: 4px solid var(--warning); padding: 0.85rem; border-radius: var(--radius-sm); font-size: 0.875rem; font-style: italic; color: var(--warning-text);">
                        "{{ $order->notes }}"
                    </div>
                @endif

                {{-- KDS Action Buttons --}}
                <div style="margin-top: auto;">
                    @if($order->status === \App\Enums\OrderStatus::NEW)
                        <button type="button"
                                wire:click="startPreparingOrder({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="startPreparingOrder({{ $order->id }})"
                                class="btn-kds-action"
                                style="background: var(--warning); color: #0E141B;">
                            <span wire:loading wire:target="startPreparingOrder({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="startPreparingOrder({{ $order->id }})">EMPEZAR PREPARACIÓN</span>
                            <span wire:loading wire:target="startPreparingOrder({{ $order->id }})">Procesando...</span>
                        </button>
                    @elseif($order->status === \App\Enums\OrderStatus::PREPARING)
                        <button type="button"
                                wire:click="markOrderReady({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="markOrderReady({{ $order->id }})"
                                class="btn-kds-action"
                                style="background: var(--primary); color: var(--primary-text);">
                            <span wire:loading wire:target="markOrderReady({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="markOrderReady({{ $order->id }})">MARCAR COMO LISTO</span>
                            <span wire:loading wire:target="markOrderReady({{ $order->id }})">Procesando...</span>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1;">
                <x-ui.empty-state
                    title="¡Cocina al día!"
                    description="No hay comandas pendientes de preparación en este momento."
                    icon="chef"
                />
            </div>
        @endforelse
    </div>
</div>
