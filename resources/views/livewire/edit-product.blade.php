<div style="max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="bag" class="w-5 h-5" />
                </div>
                Editar Producto: {{ $product->name }}
            </h1>
            <div class="page-header-subtitle">
                Modifique la información, precios y envases asignados.
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
            <input type="text" wire:model="name" class="form-input" required>
            @error('name') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Categoría *</label>
                <select wire:model="categoryId" class="form-input" required>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('categoryId') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Precio de Venta ($) *</label>
                <input type="number" step="0.01" wire:model="price" class="form-input" required>
                @error('price') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                <label class="form-label">Costo Aproximado ($) *</label>
                <input type="number" step="0.01" wire:model="estimatedCost" class="form-input" required>
                @error('estimatedCost') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Imagen del Producto (Opcional)</label>
            @if($product->image_url && !$image)
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Imagen actual cargada</span>
                </div>
            @endif
            <input type="file" wire:model="image" class="form-input" accept="image/*">
            @error('image') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Notas u Observaciones (Opcional)</label>
            <textarea wire:model="notes" rows="2" class="form-input" style="resize: none;"></textarea>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="activeProductEdit" wire:model="active">
            <label for="activeProductEdit" style="font-weight: 700; color: var(--text-main); font-size: 0.875rem;">Producto Activo</label>
        </div>

        {{-- ENVASES RETORNABLES REQUERIDOS --}}
        <div style="border-top: 1px solid var(--border); padding-top: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main);">ENVASES RETORNABLES REQUERIDOS</h3>
                    <div style="font-size: 0.775rem; color: var(--text-muted);">Defina qué envases deben entregarse con este producto.</div>
                </div>
                <button type="button" wire:click="addRequirement" class="chip-btn" style="padding: 0.4rem 0.85rem;">
                    + AGREGAR ENVASE
                </button>
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
                <span wire:loading wire:target="save">GUARDANDO...</span>
                <span wire:loading.remove wire:target="save">GUARDAR CAMBIOS</span>
            </button>
        </div>
    </form>
</div>
