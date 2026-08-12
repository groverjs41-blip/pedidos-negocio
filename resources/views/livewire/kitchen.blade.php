<div wire:poll.15s class="kitchen-layout" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">
    <style>
        .kitchen-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }
        .kitchen-card.status-preparing {
            border-color: rgba(245, 158, 11, 0.3);
            background: var(--bg-elevated);
        }

        .elapsed-badge {
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .elapsed-badge.fresh {
            background: var(--success-light);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .elapsed-badge.delayed {
            background: var(--danger-light);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            animation: pulse-flash 2.0s infinite alternate;
        }
        @keyframes pulse-flash {
            from { opacity: 0.7; }
            to { opacity: 1; transform: scale(1.02); }
        }

        .kitchen-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
        }
        .kitchen-order-number { font-size: 1.4rem; font-weight: 800; color: var(--text-main); }
        .kitchen-order-customer { font-size: 0.9rem; color: var(--text-muted); margin-top: 0.2rem; }

        .kitchen-items-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .kitchen-item { display: flex; align-items: center; gap: 1rem; font-size: 1.3rem; font-weight: 700; }
        .kitchen-item-qty {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            border-radius: 8px;
            min-width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        .kitchen-item-name { margin-top: 2px; }

        .kitchen-notes {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-left: 4px solid var(--primary);
            padding: 0.85rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-style: italic;
            color: #fbbf24;
        }

        .btn-kitchen-action {
            width: 100%;
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            transition: all 0.2s;
        }
        .btn-start { background: var(--primary); color: #0b1220; }
        .btn-start:hover { background: var(--primary-hover); }
        .btn-ready { background: var(--success); color: #0b1220; }
        .btn-ready:hover { background: #059669; }
        .btn-kitchen-action:disabled { opacity: 0.5; cursor: not-allowed; }
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

    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
        <h1 style="font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            🍳 Vista Cocina
            <span class="delivery-badge-count active" style="font-size: 0.85rem; padding: 2px 10px;">{{ count($orders) }} pendientes</span>
        </h1>
    </div>

    <!-- Active orders loop -->
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
                            👤 {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}
                        </div>
                    </div>
                    
                    <span class="elapsed-badge {{ $isDelayed ? 'delayed' : 'fresh' }}">
                        ⏱️ {{ $elapsedMinutes }}m transcurrido
                    </span>
                </div>

                <!-- Items -->
                <div class="kitchen-items-list">
                    @foreach($order->items as $item)
                        <div class="kitchen-item">
                            <span class="kitchen-item-qty">{{ $item->quantity }}x</span>
                            <span class="kitchen-item-name">{{ $item->product_name }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Special notes -->
                @if($order->notes)
                    <div class="kitchen-notes">
                        "{{ $order->notes }}"
                    </div>
                @endif

                <!-- Transition Trigger buttons -->
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
            <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-surface); border-radius: 20px; border: 1px solid var(--border); color: var(--text-muted); font-size: 0.95rem;">
                👍 ¡Todo al día! No hay pedidos pendientes de preparar en la cocina.
            </div>
        @endforelse
    </div>
</div>
