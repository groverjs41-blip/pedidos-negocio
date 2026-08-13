<div wire:poll.15s class="list-orders-layout" style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

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

    <div class="page-header" style="margin-bottom: 0.5rem;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap blue">
                    <x-ui.icon name="list" class="w-5 h-5" />
                </div>
                Seguimiento de Pedidos
            </h1>
            <div class="page-header-subtitle">Monitoreo de comandas registradas hoy</div>
        </div>
        <a href="{{ route('pedidos.nuevo') }}" class="btn-primary" style="text-decoration: none; height: 42px; padding: 0 1rem; font-size: 0.85rem;">
            + Nuevo Pedido
        </a>
    </div>

    {{-- Filter chips --}}
    <div class="category-chips">
        <button wire:click="changeFilter('TODOS')" class="chip-btn {{ $statusFilter === 'TODOS' ? 'active' : '' }}">
            Todos
        </button>
        @foreach($statuses as $value => $label)
            <button wire:click="changeFilter('{{ $value }}')" class="chip-btn {{ $statusFilter === $value ? 'active' : '' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Orders Cards Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
        @forelse($orders as $order)
            <div wire:click="viewOrder({{ $order->id }})" class="order-card stagger-item">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span class="order-card-number">{{ $order->number }}</span>
                        <span style="font-size: 0.775rem; color: var(--text-muted); margin-left: 0.4rem;">{{ $order->ordered_at->format('H:i') }}</span>
                    </div>
                    <x-ui.status-badge :status="$order->status" />
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.2rem; margin-top: 0.2rem;">
                    <span class="order-card-customer">👤 {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                    <span class="order-card-summary">
                        🍔
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.5rem; margin-top: 0.2rem;">
                    <span style="font-size: 0.775rem; color: var(--text-muted);">
                        @if($order->deliveryUser)
                            🛵 {{ $order->deliveryUser->name }}
                        @else
                            Tienda / Sin asignar
                        @endif
                    </span>
                    <span class="order-card-total">${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1;">
                <x-ui.empty-state
                    title="Sin pedidos"
                    description="No se encontraron pedidos registrados hoy con este filtro."
                    icon="list"
                />
            </div>
        @endforelse
    </div>

    {{-- Detail Modal --}}
    @if($selectedOrderId && $selectedOrder)
        <div class="modal-overlay" wire:click.self="closeModal">
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Pedido {{ $selectedOrder->number }}</h3>
                    <button type="button" wire:click="closeModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    {{-- Customer info --}}
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Cliente</div>
                        <div style="font-size: 0.95rem; font-weight: 700; color: var(--primary);">
                            {{ $selectedOrder->customer_name_snapshot ?? 'Venta Mostrador' }}
                        </div>
                        @if($selectedOrder->customer_phone_snapshot)
                            <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.15rem;">
                                📞 Teléfono: {{ $selectedOrder->customer_phone_snapshot }}
                            </div>
                        @endif
                        @if($selectedOrder->delivery_address_snapshot)
                            <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.15rem;">
                                📍 Dirección: {{ $selectedOrder->delivery_address_snapshot }}
                            </div>
                        @endif
                    </div>

                    {{-- Items table --}}
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Productos</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                                    <th style="text-align: left; padding: 0.4rem 0;">Producto</th>
                                    <th style="text-align: center; width: 60px;">Cant</th>
                                    <th style="text-align: right; width: 80px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedOrder->items as $item)
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 0.5rem 0; color: var(--text-main);">{{ $item->product_name }}</td>
                                        <td style="text-align: center; color: var(--text-main);">{{ $item->quantity }}</td>
                                        <td style="text-align: right; color: var(--text-main); font-weight: 600;">${{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div style="display: flex; justify-content: space-between; align-items: center; font-weight: 800; margin-top: 0.5rem; font-size: 1.1rem; border-top: 2px solid var(--border); padding-top: 0.5rem;">
                            <span>TOTAL:</span>
                            <span style="color: var(--primary);">${{ number_format((float)$selectedOrder->total, 2) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-top: 0.35rem; color: var(--text-muted);">
                            <span>Estado de pago: <x-ui.status-badge :status="$selectedOrder->paymentStatus()" /></span>
                            <span>Pagado: ${{ number_format((float)$selectedOrder->paidAmount(), 2) }} | Saldo: ${{ number_format((float)$selectedOrder->outstandingBalance(), 2) }}</span>
                        </div>
                    </div>

                    @if($selectedOrder->notes)
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Notas especiales</div>
                            <div style="background: var(--bg-surface); padding: 0.65rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-style: italic; color: var(--warning-text); border: 1px solid var(--border);">
                                "{{ $selectedOrder->notes }}"
                            </div>
                        </div>
                    @endif

                    {{-- Timeline --}}
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Historial de Estados</div>
                        <div class="timeline">
                            @foreach($selectedOrder->histories as $log)
                                <div class="timeline-item">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; color: var(--text-main);">
                                        <x-ui.status-badge :status="$log->to_status" />
                                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">{{ $log->created_at->format('H:i') }}</span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                        Por {{ $log->user->name }}. {{ $log->notes }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                        @if($selectedOrder->status === \App\Enums\OrderStatus::NEW)
                            <a href="{{ route('pedidos.edit', $selectedOrder->id) }}" class="chip-btn" style="text-decoration: none; text-align: center; padding: 0.65rem; background: var(--primary-light); color: var(--primary); border-color: rgba(39,230,164,0.3); font-weight: 700;">
                                ✏️ Editar Pedido
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
                            <div style="background: var(--danger-light); border: 1px solid rgba(239, 83, 80, 0.2); border-radius: var(--radius-md); padding: 0.85rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                <span style="font-size: 0.8rem; color: var(--danger-text); font-weight: 600;">
                                    Cancelar pedido:
                                </span>
                                <input type="text"
                                       wire:model="cancellationNotes"
                                       placeholder="Motivo de cancelación (opcional)..."
                                       class="form-input"
                                       style="padding: 0.5rem; font-size: 0.85rem; height: 40px;">
                                <button type="button"
                                        wire:click="cancelSelectedOrder"
                                        wire:confirm="¿Seguro que deseas cancelar este pedido?"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelSelectedOrder"
                                        class="btn-primary"
                                        style="background: var(--danger); color: #FFFFFF; height: 42px; font-size: 0.85rem;">
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
