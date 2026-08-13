<div wire:poll.15s style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="dollar" class="w-5 h-5" />
                </div>
                Panel de Cobranza
            </h1>
            <div class="page-header-subtitle">Gestión de caja, deuda de clientes y abonos</div>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('caja.pagos') }}" class="chip-btn" style="text-decoration: none; padding: 0.5rem 1rem;">
                📋 Historial de Pagos
            </a>
        </div>
    </div>

    {{-- Metrics Grid --}}
    <div class="metrics-grid">
        <div class="metric-card">
            <span class="metric-val" style="color: var(--primary);">${{ number_format((float)$todayCollected, 2) }}</span>
            <span class="metric-label">Cobrado Hoy</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--warning-text);">${{ number_format((float)$totalOutstanding, 2) }}</span>
            <span class="metric-label">Saldo Pendiente</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--info-text);">{{ $debtorsCount }}</span>
            <span class="metric-label">Clientes con Deuda</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--violet-text);">{{ $unpaidDeliveredCount }}</span>
            <span class="metric-label">Entregados Sin Pagar</span>
        </div>
    </div>

    {{-- Customer Search --}}
    <div class="card" style="padding: 1.35rem; display: flex; flex-direction: column; gap: 1rem;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
            Buscar Cliente para Cobro / Abono
        </h2>

        <div style="position: relative;">
            <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                <x-ui.icon name="search" class="w-5 h-5" />
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="searchQuery"
                class="form-input pos-search-input"
                placeholder="Buscar cliente por nombre o teléfono (ej. Juan, 555...)"
                autofocus
            >
        </div>

        @if(strlen(trim($searchQuery)) >= 2)
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                @forelse($searchResults as $cust)
                    @php $bal = $cust->outstandingBalance(); @endphp
                    <a href="{{ route('caja.cliente', $cust->id) }}" class="card" style="padding: 1rem; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                                {{ $cust->name }}
                                @if(!$cust->active)
                                    <span style="font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                                @endif
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                @if($cust->phone) 📞 {{ $cust->phone }} @endif
                                @if($cust->address) • 📍 {{ $cust->address }} @endif
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-weight: 800; font-size: 1.05rem; color: {{ bccomp($bal, '0.00', 2) > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                                Saldo: ${{ number_format((float)$bal, 2) }}
                            </div>
                            <div style="font-size: 0.775rem; color: var(--primary); font-weight: 700; margin-top: 2px;">
                                Ver Estado de Cuenta →
                            </div>
                        </div>
                    </a>
                @empty
                    <div style="text-align: center; color: var(--text-muted); padding: 1.5rem; font-size: 0.875rem;">
                        No se encontraron clientes con "{{ $searchQuery }}".
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    {{-- Unpaid Delivered Orders --}}
    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
            Pedidos Entregados Pendientes de Cobro
        </h2>

        @php
            $pendingDeliveredOrders = \App\Models\Order::where('status', \App\Enums\OrderStatus::DELIVERED)
                ->orderBy('ordered_at', 'asc')
                ->get()
                ->filter(fn($o) => bccomp($o->outstandingBalance(), '0.00', 2) > 0);
        @endphp

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @forelse($pendingDeliveredOrders as $order)
                <div class="card stagger-item" style="padding: 1.15rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: 800; font-size: 1.05rem; color: var(--text-main);">{{ $order->number }}</span>
                            <x-ui.status-badge :status="$order->paymentStatus()" />
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                            👤 {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}
                            • Entregado: {{ $order->delivered_at?->format('d/m H:i') }}
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 1.25rem;">
                        <div style="text-align: right;">
                            <div style="font-size: 0.775rem; color: var(--text-muted);">Total: ${{ number_format((float)$order->total, 2) }} | Pagado: ${{ number_format((float)$order->paidAmount(), 2) }}</div>
                            <div style="font-size: 1.1rem; font-weight: 800; color: var(--warning-text);">
                                Pendiente: ${{ number_format((float)$order->outstandingBalance(), 2) }}
                            </div>
                        </div>

                        <a href="{{ route('caja.pedido', $order->id) }}" class="btn-primary" style="text-decoration: none; height: 42px; font-size: 0.85rem; padding: 0 1rem;">
                            COBRAR PEDIDO
                        </a>
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    title="No hay pedidos entregados pendientes"
                    description="Todos los pedidos entregados están completamente pagados."
                    icon="check"
                />
            @endforelse
        </div>
    </div>
</div>
