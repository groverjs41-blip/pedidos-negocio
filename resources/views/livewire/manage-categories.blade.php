<div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap blue">
                    <x-ui.icon name="list" class="w-5 h-5" />
                </div>
                Gestión de Categorías
            </h1>
            <div class="page-header-subtitle">
                Organice el menú de productos por grupos de categorías.
            </div>
        </div>

        <button type="button" wire:click="openCreateModal" class="btn-primary" style="height: 42px; font-size: 0.875rem; padding: 0 1.25rem;">
            + NUEVA CATEGORÍA
        </button>
    </div>

    {{-- Categories List Table --}}
    <div class="card" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 0.85rem 1rem;">Orden</th>
                    <th style="padding: 0.85rem 1rem;">Nombre Categoría</th>
                    <th style="padding: 0.85rem 1rem;">Productos</th>
                    <th style="padding: 0.85rem 1rem;">Estado</th>
                    <th style="padding: 0.85rem 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr style="border-bottom: 1px solid var(--border); opacity: {{ $cat->active ? '1' : '0.6' }};">
                        <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--text-muted);">
                            #{{ $cat->sort_order }}
                        </td>
                        <td style="padding: 0.85rem 1rem; font-weight: 800; color: var(--text-main);">
                            {{ $cat->name }}
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                            {{ $cat->products_count }} productos
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            @if($cat->active)
                                <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">Activa</span>
                            @else
                                <span class="badge" style="background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactiva</span>
                            @endif
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                <button type="button" wire:click="openEditModal({{ $cat->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem;">
                                    Editar
                                </button>
                                <button type="button" wire:click="toggleActive({{ $cat->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem;">
                                    {{ $cat->active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center;">
                            <x-ui.empty-state
                                title="No hay categorías registradas"
                                description="Comience creando categorías para agrupar sus productos."
                                icon="list"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Category Modal --}}
    @if($showModal)
        <div class="modal-overlay" wire:click.self="closeModal">
            <div class="modal-content" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">
                        {{ $editingCategory ? 'Editar Categoría' : 'Nueva Categoría' }}
                    </h3>
                    <button type="button" wire:click="closeModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <form wire:submit.prevent="save" style="display: flex; flex-direction: column; gap: 1.15rem; margin-top: 0.5rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Nombre de Categoría *</label>
                        <input type="text" wire:model="name" class="form-input" placeholder="Ej. Bebidas Calientes" required>
                        @error('name') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Orden de Visualización *</label>
                        <input type="number" wire:model="sortOrder" class="form-input" required>
                        @error('sortOrder') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="catActive" wire:model="active">
                        <label for="catActive" style="font-weight: 700; color: var(--text-main); font-size: 0.875rem;">Categoría Activa</label>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn-primary" style="height: 44px; width: 100%; margin-top: 0.5rem;">
                        <span wire:loading wire:target="save" class="spinner"></span>
                        <span wire:loading.remove wire:target="save">{{ $editingCategory ? 'GUARDAR CAMBIOS' : 'CREAR CATEGORÍA' }}</span>
                        <span wire:loading wire:target="save">GUARDANDO...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
