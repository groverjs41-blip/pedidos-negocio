<div wire:poll.15s class="list-orders-layout">
    <style>
        .list-orders-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .title-main {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            color: #0f172a;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        /* Filter Chips */
        .filters-container {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none;
        }

        .filters-container::-webkit-scrollbar {
            display: none;
        }

        .filter-chip {
            background: rgba(30, 41, 59, 0.6);
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

        .filter-chip:hover {
            color: var(--text-main);
        }

        .filter-chip.active {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: var(--text-main);
            font-weight: 600;
        }

        /* Order Cards */
        .order-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: all 0.2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.12);
            background: var(--card-hover);
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-number {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-main);
        }

        .order-time {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-left: 0.5rem;
        }

        .badge-status {
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-NEW { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-PREPARING { background: rgba(245, 158, 11, 0.15); color: #fde047; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-READY { background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-DELIVERING { background: rgba(168, 85, 247, 0.15); color: #f3e8ff; border: 1px solid rgba(168, 85, 247, 0.3); }
        .status-DELIVERED { background: rgba(75, 85, 99, 0.15); color: #d1d5db; border: 1px solid rgba(75, 85, 99, 0.3); }
        .status-CANCELLED { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }

        .order-card-body {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .order-customer {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .order-items-summary {
            font-size: 0.85rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border);
            padding-top: 0.5rem;
            font-size: 0.85rem;
        }

        .order-total {
            font-weight: 700;
            color: var(--primary);
        }

        /* Modal overlay and panel */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-content {
            background: #1e293b;
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
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalPop 0.25s ease-out;
        }

        @keyframes modalPop {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .btn-close-modal {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .btn-close-modal:hover {
            color: var(--text-main);
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .modal-section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        /* Items List */
        .modal-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .modal-items-table th {
            text-align: left;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            font-weight: 500;
        }

        .modal-items-table td {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .modal-items-table tr:last-child td {
            border-bottom: none;
        }

        /* Timeline */
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-left: 0.5rem;
            border-left: 2px solid var(--border);
            margin-left: 0.5rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 1rem;
        }

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

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .timeline-time {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .timeline-body {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
        }

        /* Cancel box */
        .cancel-section {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-cancel {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 0.6rem;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        .btn-edit {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: var(--primary);
            padding: 0.6rem;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-edit:hover {
            background: rgba(245, 158, 11, 0.25);
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            background: var(--panel-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
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

    <div class="header-row">
        <h1 class="title-main">Pedidos de Hoy</h1>
        <a href="{{ route('pedidos.nuevo') }}" class="btn-primary">+ Nuevo Pedido</a>
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
            <div class="no-orders">
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
                        @if($selectedOrder->status === OrderStatus::NEW)
                            <a href="{{ route('pedidos.edit', $selectedOrder->id) }}" class="btn-edit">
                                ✏️ Editar Pedido
                            </a>
                        @endif

                        <!-- Cancelable checking -->
                        @php
                            $canCancel = false;
                            $currentStatus = $selectedOrder->status;
                            if (in_array($currentStatus, [OrderStatus::NEW, OrderStatus::PREPARING, OrderStatus::READY])) {
                                $canCancel = true;
                            } elseif ($currentStatus === OrderStatus::DELIVERING && auth()->user()->hasRole('admin')) {
                                $canCancel = true;
                            }
                        @endphp

                        @if($canCancel)
                            <div class="cancel-section">
                                <span style="font-size: 0.8rem; color: #fca5a5; font-weight: 500;">
                                    Cancelar pedido (pide confirmación):
                                </span>
                                <input type="text" 
                                       wire:model="cancellationNotes" 
                                       placeholder="Motivo de cancelación (opcional)..." 
                                       class="form-input" 
                                       style="padding: 0.5rem; font-size: 0.8rem; border-radius: 8px;">
                                <button type="button" 
                                        wire:click="cancelSelectedOrder" 
                                        class="btn-cancel">
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
