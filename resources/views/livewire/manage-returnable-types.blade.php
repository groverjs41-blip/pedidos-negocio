<div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">
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
                    <x-ui.icon name="check" class="w-5 h-5" />
                </div>
                Gestión de Tipos de Envases Retornables
            </h1>
            <div class="page-header-subtitle">
                Catálogo de tazas, vasos, jarras o termos sujetas a devolución.
            </div>
        </div>

        <button type="button" wire:click="openCreateModal" class="btn-primary" style="height: 42px; font-size: 0.875rem; padding: 0 1.25rem;">
            + NUEVO TIPO DE ENVASE
        </button>
    </div>

    {{-- Returnable Types List Table --}}
    <div class="card" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 0.85rem 1rem;">Orden</th>
                    <th style="padding: 0.85rem 1rem;">Nombre del Envase</th>
                    <th style="padding: 0.85rem 1rem;">Estado</th>
                    <th style="padding: 0.85rem 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $t)
                    <tr style="border-bottom: 1px solid var(--border); opacity: {{ $t->active ? '1' : '0.6' }};">
                        <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--text-muted);">
                            #{{ $t->sort_order }}
                        </td>
                        <td style="padding: 0.85rem 1rem; font-weight: 800; color: var(--text-main);">
                            {{ $t->name }}
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            @if($t->active)
                                <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">Activo</span>
                            @else
                                <span class="badge" style="background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                            @endif
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                <button type="button" wire:click="openEditModal({{ $t->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem;">
                                    Editar
                                </button>
                                <button type="button" wire:click="toggleActive({{ $t->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem;">
                                    {{ $t->active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding: 2rem; text-align: center;">
                            <x-ui.empty-state
                                title="No hay tipos de envases registrados"
                                description="Comience creando tipos de envases como Taza, Vaso o Jarra."
                                icon="check"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Type Modal --}}
    @if($showModal)
        <div class="modal-overlay" wire:click.self="closeModal">
            <div class="modal-content" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">
                        {{ $editingType ? 'Editar Tipo de Envase' : 'Nuevo Tipo de Envase' }}
                    </h3>
                    <button type="button" wire:click="closeModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                @if($errorMessage)
                    <div class="alert alert-danger">
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="save" style="display: flex; flex-direction: column; gap: 1.15rem; margin-top: 0.5rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Nombre del Envase *</label>
                        <input type="text" wire:model="name" class="form-input" placeholder="Ej. Taza Té 10oz" required>
                        @error('name') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Orden de Visualización *</label>
                        <input type="number" wire:model="sortOrder" class="form-input" required>
                        @error('sortOrder') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="typeActive" wire:model="active">
                        <label for="typeActive" style="font-weight: 700; color: var(--text-main); font-size: 0.875rem;">Envase Activo</label>
                    </div>

                    <button type="submit" class="btn-primary" style="height: 44px; width: 100%; margin-top: 0.5rem;">
                        {{ $editingType ? 'GUARDAR CAMBIOS' : 'CREAR ENVASE' }}
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
