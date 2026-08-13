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
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="bag" class="w-5 h-5" />
                </div>
                Gestión de Productos
            </h1>
            <div class="page-header-subtitle">
                Administre el catálogo de productos, sus precios y envases requeridos.
            </div>
        </div>

        <a href="{{ url('/gestion/productos/nuevo') }}" class="btn-primary" style="height: 42px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            <x-ui.icon name="plus" class="w-4 h-4" />
            <span>NUEVO PRODUCTO</span>
        </a>
    </div>

    {{-- Filters Bar --}}
    <div class="card" style="padding: 1rem; display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: center;">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por nombre..."
            class="form-input"
            style="flex: 1; min-width: 200px; height: 40px;"
        >

        <select wire:model.live="categoryId" class="form-input" style="width: 180px; height: 40px;">
            <option value="">Todas las categorías</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="activeFilter" class="form-input" style="width: 140px; height: 40px;">
            <option value="">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
        </select>
    </div>

    {{-- Products List Table --}}
    <div class="card" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 0.85rem 1rem;">Producto</th>
                    <th style="padding: 0.85rem 1rem;">Categoría</th>
                    <th style="padding: 0.85rem 1rem;">Precio</th>
                    <th style="padding: 0.85rem 1rem;">Costo Aprox.</th>
                    <th style="padding: 0.85rem 1rem;">Envases Requeridos</th>
                    <th style="padding: 0.85rem 1rem;">Estado</th>
                    <th style="padding: 0.85rem 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                    <tr style="border-bottom: 1px solid var(--border); opacity: {{ $p->active ? '1' : '0.6' }};">
                        <td style="padding: 0.85rem 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                @if($p->image_url)
                                    <img src="{{ asset('storage/' . $p->image_url) }}" alt="{{ $p->name }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                @else
                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--bg-surface); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                        ☕
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main);">{{ $p->name }}</div>
                                    @if($p->notes)
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ Str::limit($p->notes, 30) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                            {{ $p->category?->name }}
                        </td>
                        <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--primary);">
                            @money($p->price)
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                            @money($p->estimated_cost)
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            @if($p->returnableRequirements->count() > 0)
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @foreach($p->returnableRequirements as $req)
                                        <span class="badge" style="background: rgba(183, 148, 244, 0.15); color: var(--violet-text); font-size: 0.75rem;">
                                            {{ $req->returnableType?->name }} x{{ $req->quantity }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.75rem;">Ninguno</span>
                            @endif
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            @if($p->active)
                                <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">Activo</span>
                            @else
                                <span class="badge" style="background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                            @endif
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                <a href="{{ url('/gestion/productos/' . $p->id . '/editar') }}" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem; text-decoration: none;">
                                    Editar
                                </a>
                                <button type="button" wire:click="toggleActive({{ $p->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem;">
                                    {{ $p->active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center;">
                            <x-ui.empty-state
                                title="No hay productos registrados"
                                description="Comience creando productos para el menú de su negocio."
                                icon="bag"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $products->links() }}
    </div>
</div>
