<div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap warning">
                    <x-ui.icon name="user" class="w-5 h-5" />
                </div>
                Gestión de Clientes
            </h1>
            <div class="page-header-subtitle">
                Directorio de clientes, saldos pendientes y estado de envases.
            </div>
        </div>

        <a href="{{ url('/gestion/clientes/nuevo') }}" class="btn-primary" style="height: 42px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            <x-ui.icon name="plus" class="w-4 h-4" />
            <span>NUEVO CLIENTE</span>
        </a>
    </div>

    {{-- Filters Bar --}}
    <div class="card" style="padding: 1rem; display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: center;">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por nombre o teléfono..."
            class="form-input"
            style="flex: 1; min-width: 220px; height: 40px;"
        >

        <select wire:model.live="activeFilter" class="form-input" style="width: 150px; height: 40px;">
            <option value="">Todos los clientes</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
        </select>
    </div>

    {{-- Customers List Table --}}
    <div class="card" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 0.85rem 1rem;">Cliente</th>
                    <th style="padding: 0.85rem 1rem;">Teléfono</th>
                    <th style="padding: 0.85rem 1rem;">Dirección</th>
                    <th style="padding: 0.85rem 1rem;">Deuda Pendiente</th>
                    <th style="padding: 0.85rem 1rem;">Envases Pendientes</th>
                    <th style="padding: 0.85rem 1rem;">Estado</th>
                    <th style="padding: 0.85rem 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    @php
                        $bal = $balances[$c->id] ?? '0.00';
                        $contList = $containers[$c->id] ?? [];
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border); opacity: {{ $c->active ? '1' : '0.6' }};">
                        <td style="padding: 0.85rem 1rem; font-weight: 800; color: var(--text-main);">
                            <a href="{{ url('/gestion/clientes/' . $c->id) }}" style="color: var(--text-main); text-decoration: none;">
                                {{ $c->name }}
                            </a>
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                            {{ $c->phone ?? '—' }}
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                            {{ Str::limit($c->address ?? '—', 25) }}
                        </td>
                        <td style="padding: 0.85rem 1rem; font-weight: 700; color: {{ bccomp($bal, '0.00', 2) > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                            ${{ number_format((float)$bal, 2) }}
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            @if(count($contList) > 0)
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @foreach($contList as $cont)
                                        <span class="badge" style="background: rgba(183, 148, 244, 0.15); color: var(--violet-text); font-size: 0.75rem;">
                                            {{ $cont['returnable_type']->name }}: {{ $cont['balance'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.75rem;">0 envases</span>
                            @endif
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            @if($c->active)
                                <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">Activo</span>
                            @else
                                <span class="badge" style="background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                            @endif
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                <a href="{{ url('/gestion/clientes/' . $c->id) }}" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem; text-decoration: none;">
                                    VER 👁️
                                </a>
                                <a href="{{ url('/gestion/clientes/' . $c->id . '/editar') }}" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem; text-decoration: none;">
                                    Editar
                                </a>
                                <button type="button" wire:click="toggleActive({{ $c->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem;">
                                    {{ $c->active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center;">
                            <x-ui.empty-state
                                title="No hay clientes registrados"
                                description="Comience creando clientes para gestionar sus pedidos y cobranza."
                                icon="user"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $customers->links() }}
    </div>
</div>
