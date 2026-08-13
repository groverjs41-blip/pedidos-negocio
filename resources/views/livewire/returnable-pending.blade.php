<div wire:poll.15s style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">
                <a href="{{ route('tazas.dashboard') }}" style="color: var(--primary); text-decoration: none;">← Volver al Panel de Envases</a>
            </div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="check" class="w-5 h-5" />
                </div>
                Clientes con Envases por Recoger
            </h1>
            <div class="page-header-subtitle">Listado de clientes con saldos pendientes de retorno</div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @forelse($customers as $cust)
            @php
                $balances = $returnableService->getCustomerBalances($cust);
                $total = $returnableService->getCustomerTotalOutstanding($cust);
            @endphp
            <div class="card stagger-item" style="padding: 1.15rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <div style="font-weight: 800; font-size: 1.05rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                        {{ $cust->name }}
                        @if(!$cust->active)
                            <span style="font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                        @endif
                    </div>
                    <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 4px;">
                        @if($cust->phone) 📞 {{ $cust->phone }} @endif
                        @if($cust->address) • 📍 {{ $cust->address }} @endif
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.4rem; flex-wrap: wrap;">
                        @foreach($balances as $b)
                            @if($b['outstanding'] > 0)
                                <span class="chip-btn" style="padding: 2px 8px; font-size: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border);">
                                    {{ $b['outstanding'] }} {{ $b['type']->name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div style="text-align: right;">
                        <div style="font-size: 1.2rem; font-weight: 800; color: var(--warning-text);">
                            {{ $total }} unidades
                        </div>
                    </div>

                    <a href="{{ route('tazas.cliente', $cust->id) }}" class="btn-primary" style="text-decoration: none; height: 42px; font-size: 0.85rem; padding: 0 1rem;">
                        VER CLIENTE →
                    </a>
                </div>
            </div>
        @empty
            <x-ui.empty-state
                title="Sin envases por recoger"
                description="No hay clientes con saldos de envases pendientes en este momento."
                icon="check"
            />
        @endforelse
    </div>
</div>
