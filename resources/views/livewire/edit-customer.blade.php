<div style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap warning">
                    <x-ui.icon name="user" class="w-5 h-5" />
                </div>
                Editar Cliente: {{ $customer->name }}
            </h1>
            <div class="page-header-subtitle">
                Modifique la información de contacto y preferencias.
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
            <label class="form-label">Nombre del Cliente *</label>
            <input type="text" wire:model="name" class="form-input" required>
            @error('name') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Teléfono (Opcional)</label>
            <input type="text" wire:model="phone" class="form-input">
            @error('phone') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Dirección (Opcional)</label>
            <textarea wire:model="address" rows="2" class="form-input" style="resize: none;"></textarea>
            @error('address') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Referencia de Dirección (Opcional)</label>
            <input type="text" wire:model="addressReference" class="form-input">
            @error('addressReference') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Notas u Observaciones (Opcional)</label>
            <textarea wire:model="notes" rows="2" class="form-input" style="resize: none;"></textarea>
            @error('notes') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="customerActiveEdit" wire:model="active">
            <label for="customerActiveEdit" style="font-weight: 700; color: var(--text-main); font-size: 0.875rem;">Cliente Activo</label>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.85rem; margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
            <a href="{{ url('/gestion/clientes/' . $customer->id) }}" class="chip-btn" style="padding: 0.75rem 1.25rem; text-decoration: none; font-weight: 700;">
                CANCELAR
            </a>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="btn-primary"
                style="height: 44px; padding: 0 1.5rem;"
            >
                <span wire:loading wire:target="save">GUARDANDO...</span>
                <span wire:loading.remove wire:target="save">GUARDAR CAMBIOS</span>
            </button>
        </div>
    </form>
</div>
