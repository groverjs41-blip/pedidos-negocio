<div wire:poll.15s class="kitchen-layout" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">

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
            <span class="page-title-icon green">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle><line x1="5.4" y1="2" x2="18.6" y2="2" stroke-width="2"></line></svg>
            </span>
            Vista Cocina
            <span class="delivery-badge-count active">{{ count($orders) }} pendientes</span>
        </h1>
    </div>

    {{-- Active orders --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        @forelse($orders as $order)
            @php
                $elapsedMinutes = now()->diffInMinutes($order->ordered_at);
                $isDelayed = $elapsedMinutes >= 15;
            @endphp
            <div class="kitchen-card {{ $order->status === \App\Enums\OrderStatus::PREPARING ? 'status-preparing' : '' }}">
                <div class="kitchen-card-header">
                    <div>
                        <div class="kitchen-order-number">{{ $order->number }}</div>
                        <div class="kitchen-order-customer">
                            {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}
                        </div>
                    </div>

                    <span class="elapsed-badge {{ $isDelayed ? 'delayed' : 'fresh' }}">
                        <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        {{ $elapsedMinutes }}m
                    </span>
                </div>

                {{-- Items --}}
                <div class="kitchen-items-list">
                    @foreach($order->items as $item)
                        <div class="kitchen-item">
                            <span class="kitchen-item-qty">{{ $item->quantity }}x</span>
                            <span class="kitchen-item-name">{{ $item->product_name }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Special notes --}}
                @if($order->notes)
                    <div class="kitchen-notes">
                        "{{ $order->notes }}"
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div>
                    @if($order->status === \App\Enums\OrderStatus::NEW)
                        <button type="button"
                                wire:click="startPreparingOrder({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="startPreparingOrder({{ $order->id }})"
                                class="btn-kitchen-action btn-start">
                            <span wire:loading wire:target="startPreparingOrder({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="startPreparingOrder({{ $order->id }})">EMPEZAR PREPARACIÓN</span>
                            <span wire:loading wire:target="startPreparingOrder({{ $order->id }})">Procesando...</span>
                        </button>
                    @elseif($order->status === \App\Enums\OrderStatus::PREPARING)
                        <button type="button"
                                wire:click="markOrderReady({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="markOrderReady({{ $order->id }})"
                                class="btn-kitchen-action btn-ready">
                            <span wire:loading wire:target="markOrderReady({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="markOrderReady({{ $order->id }})">MARCAR COMO LISTO</span>
                            <span wire:loading wire:target="markOrderReady({{ $order->id }})">Procesando...</span>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state">
                <svg viewBox="0 0 24 24" style="width: 32px; height: 32px; stroke: var(--text-light); fill: none; stroke-width: 1.5; margin: 0 auto 0.75rem;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <div>¡Todo al día! No hay pedidos pendientes de preparar.</div>
            </div>
        @endforelse
    </div>
</div>
