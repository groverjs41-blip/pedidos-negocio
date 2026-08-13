<div style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap violet">
                    <x-ui.icon name="user" class="w-5 h-5" />
                </div>
                Editar Usuario: {{ $user->name }}
            </h1>
            <div class="page-header-subtitle">
                Modifique los datos, contraseña y roles asignados.
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
            <label class="form-label">Nombre Completo *</label>
            <input type="text" wire:model="name" class="form-input" required>
            @error('name') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Correo Electrónico *</label>
            <input type="email" wire:model="email" class="form-input" required>
            @error('email') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <label class="form-label">Nueva Contraseña (Opcional)</label>
            <input type="password" wire:model="password" class="form-input" placeholder="Déjala vacía si no deseas cambiarla.">
            <span style="font-size: 0.75rem; color: var(--text-muted);">Déjala vacía si no deseas cambiarla.</span>
            @error('password') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
            <label class="form-label">Roles Asignados * (Selección Múltiple)</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
                @foreach($roles as $role)
                    <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--bg-surface); padding: 0.6rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <input type="checkbox" value="{{ $role->id }}" id="role_{{ $role->id }}" wire:model="selectedRoles">
                        <label for="role_{{ $role->id }}" style="font-size: 0.875rem; font-weight: 700; color: var(--text-main); cursor: pointer;">
                            {{ $role->name }}
                        </label>
                    </div>
                @endforeach
            </div>
            @error('selectedRoles') <span style="color: var(--danger-text); font-size: 0.775rem;">{{ $message }}</span> @enderror
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="userActiveEdit" wire:model="active">
            <label for="userActiveEdit" style="font-weight: 700; color: var(--text-main); font-size: 0.875rem;">Usuario Activo</label>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.85rem; margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
            <a href="{{ url('/gestion/usuarios') }}" class="chip-btn" style="padding: 0.75rem 1.25rem; text-decoration: none; font-weight: 700;">
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
