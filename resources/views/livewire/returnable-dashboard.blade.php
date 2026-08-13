<div wire:poll.15s style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="check" class="w-5 h-5" />
                </div>
                Control de Envases Retornables
            </h1>
            <div class="page-header-subtitle">Gestión de tazas, vasos y envases pendientes</div>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('tazas.por-recoger') }}" class="chip-btn" style="text-decoration: none; padding: 0.5rem 1rem;">
                📦 Ver Clientes por Recoger ({{ $debtorCustomersCount }})
            </a>
        </div>
    </div>

    {{-- Metrics Grid --}}
    <div class="metrics-grid">
        <div class="metric-card">
            <span class="metric-val" style="color: var(--warning-text);">{{ $totalOutstandingUnits }}</span>
            <span class="metric-label">Envases Fuera (Unidades)</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--info-text);">{{ $debtorCustomersCount }}</span>
            <span class="metric-label">Clientes con Envases</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--primary);">{{ $todayRecovered }}</span>
            <span class="metric-label">Recuperados Hoy</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--violet-text);">{{ $todayOut }}</span>
            <span class="metric-label">Salidas Hoy</span>
        </div>
    </div>

    {{-- Customer Search --}}
    <div class="card" style="padding: 1.35rem; display: flex; flex-direction: column; gap: 1rem;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
            Buscar Cliente para Devolución / Salida
        </h2>

        <div style="position: relative;">
            <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                <x-ui.icon name="search" class="w-5 h-5" />
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="searchQuery"
                class="form-input pos-search-input"
                placeholder="Buscar cliente por nombre o teléfono..."
                autofocus
            >
        </div>

        @if(strlen(trim($searchQuery)) >= 2)
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                @inject('returnableService', 'App\Services\ReturnableService')
                @forelse($searchResults as $cust)
                    @php
                        $balances = $returnableService->getCustomerBalances($cust);
                        $total = $returnableService->getCustomerTotalOutstanding($cust);
                    @endphp
                    <a href="{{ route('tazas.cliente', $cust->id) }}" class="card" style="padding: 1rem; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
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
                            <div style="font-weight: 800; font-size: 1.05rem; color: {{ $total > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                                Pendientes: {{ $total }}
                            </div>
                            <div style="font-size: 0.775rem; color: var(--text-muted);">
                                @foreach($balances as $b)
                                    @if($b['outstanding'] > 0)
                                        {{ $b['outstanding'] }} {{ $b['type']->name }} &nbsp;
                                    @endif
                                @endforeach
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

    {{-- Quick Link to Pending List --}}
    <div class="card" style="padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div style="font-weight: 800; font-size: 1rem; color: var(--text-main);">Ver Listado Completo por Recoger</div>
            <div style="font-size: 0.825rem; color: var(--text-muted);">Clientes con envases pendientes ordenados por cantidad</div>
        </div>
        <a href="{{ route('tazas.por-recoger') }}" class="btn-primary" style="text-decoration: none; padding: 0.6rem 1.15rem; font-size: 0.875rem;">
            Ver Lista →
        </a>
    </div>
</div>
