<div style="max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="plus" class="w-5 h-5" />
                </div>
                Nuevo Producto
            </h1>
            <div class="page-header-subtitle">
                Complete la información para registrar un nuevo producto en el catálogo.
            </div>
        </div>
    </div>

    @if($errorMessage)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <form wire:submit.prevent="save" class="card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Nombre del Producto *</label>
            <input type="text" wire:model="name" class="form-input" placeholder="Ej. Café Americano 12oz" required>
            @error('name') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="form-label">Categoría *</label>
                    <button type="button" x-on:click="$dispatch('open-modal', 'quick-category-modal')" class="chip-btn" style="padding: 2px 8px; font-size: 0.75rem;">
                        + Nueva
                    </button>
                </div>
                <select wire:model="categoryId" class="form-input" required>
                    <option value="">Seleccione una categoría</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('categoryId') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Precio de Venta *</label>
                <input type="number" step="0.01" wire:model="price" class="form-input" placeholder="0.00" required>
                @error('price') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Costo Aproximado *</label>
                <input type="number" step="0.01" wire:model="estimatedCost" class="form-input" placeholder="0.00" required>
                @error('estimatedCost') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Imagen del Producto (Opcional)</label>
            <input type="file" wire:model="image" class="form-input" accept="image/*">
            @error('image') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Notas u Observaciones (Opcional)</label>
            <textarea wire:model="notes" rows="2" class="form-input" placeholder="Descripción breve del producto..." style="resize: none;"></textarea>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="activeProduct" wire:model="active">
            <label for="activeProduct" style="font-weight: 700; color: var(--text-main); font-size: 0.875rem;">Producto Activo</label>
        </div>

        {{-- ENVASES RETORNABLES REQUERIDOS --}}
        <div style="border-top: 1px solid var(--border); padding-top: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main);">ENVASES RETORNABLES REQUERIDOS</h3>
                    <div style="font-size: 0.775rem; color: var(--text-muted);">Defina qué envases deben entregarse con este producto.</div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" x-on:click="$dispatch('open-modal', 'quick-returnable-modal')" class="chip-btn" style="padding: 0.4rem 0.85rem;">
                        + NUEVO TIPO ENVASE
                    </button>
                    <button type="button" wire:click="addRequirement" class="chip-btn" style="padding: 0.4rem 0.85rem;">
                        + AGREGAR ENVASE
                    </button>
                </div>
            </div>

            @foreach($requirements as $index => $req)
                <div style="display: flex; align-items: center; gap: 0.75rem; background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <select wire:model="requirements.{{ $index }}.returnable_type_id" class="form-input" style="flex: 1; height: 38px;">
                        @foreach($returnableTypes as $rt)
                            <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                        @endforeach
                    </select>

                    <input type="number" min="1" wire:model="requirements.{{ $index }}.quantity" class="form-input" style="width: 90px; height: 38px;" placeholder="Cant.">

                    <button type="button" wire:click="removeRequirement({{ $index }})" style="background: transparent; border: none; color: var(--danger-text); font-size: 1.25rem; cursor: pointer;">
                        &times;
                    </button>
                </div>
            @endforeach
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.85rem; margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
            <a href="{{ url('/gestion/productos') }}" class="chip-btn" style="padding: 0.75rem 1.25rem; text-decoration: none; font-weight: 700;">
                CANCELAR
            </a>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save, image"
                class="btn-primary"
                style="height: 44px; padding: 0 1.5rem;"
            >
                <span wire:loading wire:target="save">PROCESANDO...</span>
                <span wire:loading.remove wire:target="save">GUARDAR PRODUCTO</span>
            </button>
        </div>
    </form>

    {{-- MODAL CATEGORÍA RÁPIDA --}}
    <x-ui.modal name="quick-category-modal" title="Nueva Categoría Rápida" maxWidth="md">
        <form wire:submit.prevent="createQuickCategory" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Nombre de Categoría *</label>
                <input type="text" wire:model="quickCategoryName" class="form-input" placeholder="Ej. Bebidas Calientes" required>
                @error('quickCategoryName') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Orden de Visualización</label>
                <input type="number" wire:model="quickCategorySortOrder" class="form-input" placeholder="0">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" x-on:click="$dispatch('close-modal', 'quick-category-modal')" class="chip-btn" style="padding: 0.5rem 1rem;">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" style="height: 38px; padding: 0 1.25rem;">
                    Guardar Categoría
                </button>
            </div>
        </form>
    </x-ui.modal>

    {{-- MODAL ENVASE RÁPIDO --}}
    <x-ui.modal name="quick-returnable-modal" title="Nuevo Tipo de Envase" maxWidth="md">
        <form wire:submit.prevent="createQuickReturnableType" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Nombre del Envase *</label>
                <input type="text" wire:model="quickReturnableName" class="form-input" placeholder="Ej. Garrafón 20L" required>
                @error('quickReturnableName') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" x-on:click="$dispatch('close-modal', 'quick-returnable-modal')" class="chip-btn" style="padding: 0.5rem 1rem;">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" style="height: 38px; padding: 0 1.25rem;">
                    Guardar Envase
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
