<div wire:poll.15s class="list-orders-layout" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">

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
            <span class="page-title-icon blue">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </span>
            Pedidos de Hoy
        </h1>
        <a href="{{ route('pedidos.nuevo') }}" class="btn-pos-submit" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.85rem; height: auto;">
            + Nuevo Pedido
        </a>
    </div>

    {{-- Filter chips --}}
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

    {{-- Orders Cards List --}}
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
                        {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}
                    </div>
                    <div class="order-items-summary">
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>
                </div>
                <div class="order-card-footer">
                    <span style="color: var(--text-muted);">
                        @if($order->deliveryUser)
                            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; vertical-align: -2px;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            {{ $order->deliveryUser->name }}
                        @else
                            Tienda / Sin asignar
                        @endif
                    </span>
                    <span class="order-total">${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        @empty
            <div class="empty-state">
                No se encontraron pedidos registrados hoy con este filtro.
            </div>
        @endforelse
    </div>

    {{-- Detail Modal --}}
    @if($selectedOrderId && $selectedOrder)
        <div class="modal-overlay" wire:click.self="closeModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Pedido {{ $selectedOrder->number }}</h3>
                    <button type="button" wire:click="closeModal" class="btn-close-modal">&times;</button>
                </div>

                <div class="modal-body">
                    {{-- Customer info --}}
                    <div>
                        <div class="modal-section-title">Cliente</div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-main);">
                            {{ $selectedOrder->customer_name_snapshot ?? 'Venta Mostrador' }}
                        </div>
                        @if($selectedOrder->customer_phone_snapshot)
                            <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.15rem;">
                                Teléfono: {{ $selectedOrder->customer_phone_snapshot }}
                            </div>
                        @endif
                        @if($selectedOrder->delivery_address_snapshot)
                            <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.15rem;">
                                Dirección: {{ $selectedOrder->delivery_address_snapshot }}
                            </div>
                        @endif
                    </div>

                    {{-- Items table --}}
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

                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-top: 0.5rem; font-size: 1rem; border-top: 2px solid var(--border); padding-top: 0.5rem;">
                            <span>Total:</span>
                            <span style="color: var(--primary);">${{ number_format($selectedOrder->total, 2) }}</span>
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if($selectedOrder->notes)
                        <div>
                            <div class="modal-section-title">Notas especiales</div>
                            <div style="background: var(--bg-elevated); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-style: italic; color: var(--text-muted); border: 1px solid var(--border);">
                                "{{ $selectedOrder->notes }}"
                            </div>
                        </div>
                    @endif

                    {{-- History --}}
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

                    {{-- Modal Actions --}}
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                        @if($selectedOrder->status === \App\Enums\OrderStatus::NEW)
                            <a href="{{ route('pedidos.edit', $selectedOrder->id) }}" class="btn-edit">
                                Editar Pedido
                            </a>
                        @endif

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
                                <span style="font-size: 0.8rem; color: var(--danger); font-weight: 500;">
                                    Cancelar pedido:
                                </span>
                                <input type="text"
                                       wire:model="cancellationNotes"
                                       placeholder="Motivo de cancelación (opcional)..."
                                       class="form-input"
                                       style="padding: 0.5rem; font-size: 0.85rem;">
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
