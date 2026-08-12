<div wire:poll.15s class="kitchen-layout">
    <style>
        .kitchen-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .kitchen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .kitchen-title {
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .kitchen-count {
            background: rgba(245, 158, 11, 0.15);
            color: var(--primary);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 99px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Kitchen Cards */
        .kitchen-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }

        .kitchen-card.status-preparing {
            border-color: rgba(245, 158, 11, 0.25);
        }

        /* Elapsed counter */
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
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .elapsed-badge.delayed {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            animation: pulse 2s infinite;
        }

        .kitchen-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
        }

        .kitchen-order-number {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .kitchen-order-customer {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
        }

        /* Items font sizes */
        .kitchen-items-list {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .kitchen-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 1.15rem; /* Larger font size for chef readability */
            font-weight: 500;
        }

        .kitchen-item-qty {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            border-radius: 6px;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary);
        }

        .kitchen-item-name {
            margin-top: 2px;
        }

        /* Kitchen Notes */
        .kitchen-notes {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-left: 3px solid var(--primary);
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-style: italic;
            color: #fbbf24;
        }

        /* Action Buttons */
        .btn-kitchen-action {
            width: 100%;
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            transition: all 0.2s;
        }

        .btn-start {
            background: var(--primary);
            color: #0f172a;
        }

        .btn-start:hover {
            background: var(--primary-hover);
        }

        .btn-ready {
            background: var(--success);
            color: #0f172a;
        }

        .btn-ready:hover {
            background: #059669;
        }

        .no-kitchen-orders {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--panel-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.95rem;
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

    <div class="kitchen-header">
        <h1 class="kitchen-title">
            🍳 Vista Cocina
            <span class="kitchen-count">{{ count($orders) }} active</span>
        </h1>
    </div>

    <!-- Active orders loop -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        @forelse($orders as $order)
            @php
                $elapsedMinutes = now()->diffInMinutes($order->ordered_at);
                $isDelayed = $elapsedMinutes >= 15;
            @endphp
            <div class="kitchen-card {{ $order->status === OrderStatus::PREPARING ? 'status-preparing' : '' }}">
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
                    @if($order->status === OrderStatus::NEW)
                        <button type="button" 
                                wire:click="startPreparingOrder({{ $order->id }})" 
                                class="btn-kitchen-action btn-start">
                            EMPEZAR PREPARACIÓN
                        </button>
                    @elseif($order->status === OrderStatus::PREPARING)
                        <button type="button" 
                                wire:click="markOrderReady({{ $order->id }})" 
                                class="btn-kitchen-action btn-ready">
                            MARCAR COMO LISTO
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="no-kitchen-orders">
                👍 ¡Todo al día! No hay pedidos pendientes de preparar en la cocina.
            </div>
        @endforelse
    </div>
</div>
