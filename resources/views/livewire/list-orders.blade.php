<div wire:poll.15s class="list-orders-layout" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">
    <style>
        .filters-container {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none;
        }
        .filters-container::-webkit-scrollbar { display: none; }
        
        .filter-chip {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .filter-chip:hover { color: var(--text-main); }
        .filter-chip.active {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }

        .order-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: all 0.2s;
        }
        .order-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-hover);
            background: var(--bg-elevated);
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-number { font-weight: 700; font-size: 1.05rem; }
        .order-time { font-size: 0.75rem; color: var(--text-muted); margin-left: 0.5rem; }
        
        .badge-status {
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-NEW { background: var(--info-light); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-PREPARING { background: var(--primary-light); color: #fde047; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-READY { background: var(--success-light); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-DELIVERING { background: rgba(168, 85, 247, 0.15); color: #e9d5ff; border: 1px solid rgba(168, 85, 247, 0.3); }
        .status-DELIVERED { background: rgba(75, 85, 99, 0.2); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }
        .status-CANCELLED { background: var(--danger-light); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }

        .order-card-body { display: flex; flex-direction: column; gap: 0.25rem; }
        .order-customer { font-weight: 600; font-size: 0.95rem; }
        .order-items-summary { font-size: 0.85rem; color: var(--text-muted); }

        .order-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border);
            padding-top: 0.5rem;
            font-size: 0.85rem;
        }
        .order-total { font-weight: 700; color: var(--primary); }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(11, 18, 32, 0.8);
            backdrop-filter: blur(4px);
            z-index: 150;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-content {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
            animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalPop {
            from { transform: scale(0.96); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
        }
        .modal-title { font-size: 1.25rem; font-weight: 700; }
        .btn-close-modal {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }

        .modal-body { display: flex; flex-direction: column; gap: 1.25rem; }
        .modal-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }

        .modal-items-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .modal-items-table th {
            text-align: left;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            font-weight: 600;
        }
        .modal-items-table td { padding: 0.5rem 0; border-bottom: 1px solid var(--border); }
        .modal-items-table tr:last-child td { border-bottom: none; }

        /* Timeline */
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-left: 0.5rem;
            border-left: 2px solid var(--border);
            margin-left: 0.5rem;
        }
        .timeline-item { position: relative; padding-left: 1rem; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.35rem;
            top: 0.35rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
        }
        .timeline-header { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; font-weight: 600; }
        .timeline-time { font-size: 0.75rem; color: var(--text-muted); font-weight: 400; }
        .timeline-body { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem; }

        .cancel-section {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .btn-cancel {
            background: var(--danger);
            border: none;
            color: #0b1220;
            padding: 0.65rem;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cancel:hover { background: #dc2626; }
        .btn-cancel:disabled { opacity: 0.5; cursor: not-allowed; }

        .btn-edit {
            background: var(--primary-light);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: var(--primary);
            padding: 0.65rem;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-edit:hover { background: rgba(245, 158, 11, 0.25); }
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
        <h1 style="font-size: 1.35rem; font-weight: 700;">📋 Pedidos de Hoy</h1>
        <a href="{{ route('pedidos.nuevo') }}" class="btn-pos-submit" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.85rem;">
            + Nuevo Pedido
        </a>
    </div>

    <!-- Filter chips -->
    <div class="filters-container">
        <button wire:click="changeFilter('TODOS')" class="filter-chip {{ $statusFilter === 'TODOS' ? 'active' : '' }}">
            Todos
        </button>
        @foreach($statuses as $value => $label)
            <button wire:click="changeFilter('{{ $value }}')" class="filter-chip {{ $statusFilter === $value ? 'active' : '' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- Orders Cards List -->
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @forelse($orders as $order)
            <div wire:click="viewOrder({{ $order->id }})" class="order-card">
                <div class="order-card-header">
                    <div>
                        <span class="order-number">{{ $order->number }}</span>
                        <span class="order-time">{{ $order->ordered_at->format('H:i') }}</span>
                    </div>
                    <span class="badge-status status-{{ $order->status->value }}">
                        {{ $order->status->label() }}
                    </span>
                </div>
                <div class="order-card-body">
                    <div class="order-customer">
                        👤 {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}
                    </div>
                    <div class="order-items-summary">
                        🍔
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>
                </div>
                <div class="order-card-footer">
                    <span style="color: var(--text-muted);">
                        @if($order->deliveryUser)
                            🛵 {{ $order->deliveryUser->name }}
                        @else
                            Tienda / Sin asignar
                        @endif
                    </span>
                    <span class="order-total">${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted); background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border);">
                No se encontraron pedidos registrados hoy con este filtro.
            </div>
        @endforelse
    </div>

    <!-- Detail Modal -->
    @if($selectedOrderId && $selectedOrder)
        <div class="modal-overlay" wire:click.self="closeModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Pedido {{ $selectedOrder->number }}</h3>
                    <button type="button" wire:click="closeModal" class="btn-close-modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <!-- Customer snapshots info -->
                    <div>
                        <div class="modal-section-title">Cliente</div>
                        <div style="font-size: 0.95rem; font-weight: 600;">
                            {{ $selectedOrder->customer_name_snapshot ?? 'Venta Mostrador' }}
                        </div>
                        @if($selectedOrder->customer_phone_snapshot)
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                                📞 Teléfono: {{ $selectedOrder->customer_phone_snapshot }}
                            </div>
                        @endif
                        @if($selectedOrder->delivery_address_snapshot)
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                                📍 Dirección: {{ $selectedOrder->delivery_address_snapshot }}
                            </div>
                        @endif
                    </div>

                    <!-- Items summary table -->
                    <div>
                        <div class="modal-section-title">Productos</div>
                        <table class="modal-items-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style="text-align: center; width: 60px;">Cant</th>
                                    <th style="text-align: right; width: 80px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedOrder->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td style="text-align: center;">{{ $item->quantity }}</td>
                                        <td style="text-align: right;">${{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-top: 0.5rem; font-size: 1rem; border-top: 1px solid var(--border); padding-top: 0.5rem;">
                            <span>Total:</span>
                            <span style="color: var(--primary);">${{ number_format($selectedOrder->total, 2) }}</span>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($selectedOrder->notes)
                        <div>
                            <div class="modal-section-title">Notas especiales</div>
                            <div style="background: rgba(0,0,0,0.15); padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.85rem; font-style: italic;">
                                "{{ $selectedOrder->notes }}"
                            </div>
                        </div>
                    @endif

                    <!-- History logs -->
                    <div>
                        <div class="modal-section-title">Historial de Estados</div>
                        <div class="timeline">
                            @foreach($selectedOrder->histories as $log)
                                <div class="timeline-item">
                                    <div class="timeline-header">
                                        <span>{{ $log->to_status->label() }}</span>
                                        <span class="timeline-time">{{ $log->created_at->format('H:i') }}</span>
                                    </div>
                                    <div class="timeline-body">
                                        Por {{ $log->user->name }}. {{ $log->notes }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                        @if($selectedOrder->status === \App\Enums\OrderStatus::NEW)
                            <a href="{{ route('pedidos.edit', $selectedOrder->id) }}" class="btn-edit">
                                ✏️ Editar Pedido
                            </a>
                        @endif

                        <!-- Cancelable checking -->
                        @php
                            $canCancel = false;
                            $currentStatus = $selectedOrder->status;
                            if (in_array($currentStatus, [\App\Enums\OrderStatus::NEW, \App\Enums\OrderStatus::PREPARING, \App\Enums\OrderStatus::READY])) {
                                $canCancel = true;
                            } elseif ($currentStatus === \App\Enums\OrderStatus::DELIVERING && auth()->user()->hasRole('admin')) {
                                $canCancel = true;
                            }
                        @endphp

                        @if($canCancel)
                            <div class="cancel-section">
                                <span style="font-size: 0.8rem; color: #fca5a5; font-weight: 500;">
                                    Cancelar pedido:
                                </span>
                                <input type="text" 
                                       wire:model="cancellationNotes" 
                                       placeholder="Motivo de cancelación (opcional)..." 
                                       class="login-input" 
                                       style="padding: 0.5rem; font-size: 0.85rem; border-radius: 8px;">
                                <button type="button" 
                                        wire:click="cancelSelectedOrder" 
                                        wire:confirm="¿Seguro que deseas cancelar este pedido?"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelSelectedOrder"
                                        class="btn-cancel">
                                    <span wire:loading wire:target="cancelSelectedOrder" class="spinner"></span>
                                    Confirmar Cancelar Pedido
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
