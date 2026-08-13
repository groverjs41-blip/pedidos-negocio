<div style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    @if(session()->has('success'))
        <div class="alert alert-success">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap warning">
                    <x-ui.icon name="user" class="w-5 h-5" />
                </div>
                {{ $customer->name }}
            </h1>
            <div class="page-header-subtitle">
                Vista 360° del cliente, historial de pedidos, saldo y envases.
            </div>
        </div>

        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
            <a href="{{ url('/pedidos/nuevo?customer=' . $customer->id) }}" class="btn-primary" style="height: 40px; text-decoration: none; font-size: 0.85rem; padding: 0 1rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                <x-ui.icon name="plus" class="w-4 h-4" />
                <span>NUEVO PEDIDO</span>
            </a>

            <a href="{{ url('/caja/clientes/' . $customer->id) }}" class="chip-btn" style="height: 40px; text-decoration: none; font-size: 0.85rem; padding: 0 1rem; display: inline-flex; align-items: center; gap: 0.4rem; color: var(--info-text);">
                <x-ui.icon name="dollar" class="w-4 h-4" />
                <span>IR A COBRANZA</span>
            </a>

            <a href="{{ url('/tazas/clientes/' . $customer->id) }}" class="chip-btn" style="height: 40px; text-decoration: none; font-size: 0.85rem; padding: 0 1rem; display: inline-flex; align-items: center; gap: 0.4rem; color: var(--violet-text);">
                <x-ui.icon name="check" class="w-4 h-4" />
                <span>DEVOLUCIÓN ENVASES</span>
            </a>

            <a href="{{ url('/gestion/clientes/' . $customer->id . '/editar') }}" class="chip-btn" style="height: 40px; text-decoration: none; font-size: 0.85rem; padding: 0 1rem; display: inline-flex; align-items: center;">
                EDITAR
            </a>
        </div>
    </div>

    {{-- Metrics Summary Grid --}}
    <div class="metrics-grid">
        <div class="metric-card">
            <span class="metric-val" style="color: {{ bccomp($debt, '0.00', 2) > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                ${{ number_format((float)$debt, 2) }}
            </span>
            <span class="metric-label">Deuda Pendiente</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--violet-text);">
                @php
                    $totalEnv = 0;
                    foreach($containerSummary as $cs) { $totalEnv += $cs['balance']; }
                @endphp
                {{ $totalEnv }}
            </span>
            <span class="metric-label">Envases Afuera</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--info-text);">
                {{ $recentOrders->count() }}
            </span>
            <span class="metric-label">Pedidos Recientes</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--text-main);">
                {{ $recentPayments->count() }}
            </span>
            <span class="metric-label">Pagos Registrados</span>
        </div>
    </div>

    {{-- Customer Profile Card --}}
    <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
        <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Información de Contacto</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.875rem;">
            <div>
                <span style="color: var(--text-muted);">Teléfono:</span>
                <strong style="color: var(--text-main); display: block; margin-top: 2px;">{{ $customer->phone ?? 'Sin registrar' }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Dirección:</span>
                <strong style="color: var(--text-main); display: block; margin-top: 2px;">{{ $customer->address ?? 'Sin registrar' }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Referencia:</span>
                <strong style="color: var(--text-main); display: block; margin-top: 2px;">{{ $customer->address_reference ?? '—' }}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Estado:</span>
                <div style="margin-top: 2px;">
                    @if($customer->active)
                        <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">Activo</span>
                    @else
                        <span class="badge" style="background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                    @endif
                </div>
            </div>
        </div>
        @if($customer->notes)
            <div style="font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 0.75rem;">
                Notas: "{{ $customer->notes }}"
            </div>
        @endif
    </div>

    {{-- Recent Orders & Recent Payments Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
        {{-- Recent Orders --}}
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Pedidos Recientes</h3>
            <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                @forelse($recentOrders as $ord)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">
                                Comanda #{{ $ord->id }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                {{ $ord->created_at->format('d/m/Y H:i') }} • {{ $ord->status->label() }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; font-size: 0.95rem; color: var(--primary);">
                                ${{ number_format((float)$ord->total, 2) }}
                            </div>
                            <a href="{{ url('/caja/pedidos/' . $ord->id) }}" style="font-size: 0.75rem; color: var(--info-text); text-decoration: none;">
                                Ver Cobro &rarr;
                            </a>
                        </div>
                    </div>
                @empty
                    <div style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1rem;">
                        No hay pedidos recientes.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Payments --}}
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Historial de Pagos</h3>
            <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                @forelse($recentPayments as $pay)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">
                                Pago #${{ $pay->id }} ({{ $pay->method->label() }})
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                {{ $pay->paid_at->format('d/m/Y H:i') }} por {{ $pay->creator?->name }}
                            </div>
                        </div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--info-text);">
                            +${{ number_format((float)$pay->amount, 2) }}
                        </div>
                    </div>
                @empty
                    <div style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1rem;">
                        No hay pagos registrados.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
