<div style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="alert alert-danger">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap violet">
                    <x-ui.icon name="user" class="w-5 h-5" />
                </div>
                Gestión de Usuarios y Roles
            </h1>
            <div class="page-header-subtitle">
                Administre el acceso del personal y la asignación de roles operativos.
            </div>
        </div>

        <a href="{{ url('/gestion/usuarios/nuevo') }}" class="btn-primary" style="height: 42px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            <x-ui.icon name="plus" class="w-4 h-4" />
            <span>NUEVO USUARIO</span>
        </a>
    </div>

    {{-- Users List Table --}}
    <div class="card" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 0.85rem 1rem;">Usuario</th>
                    <th style="padding: 0.85rem 1rem;">Correo Electrónico</th>
                    <th style="padding: 0.85rem 1rem;">Roles Asignados</th>
                    <th style="padding: 0.85rem 1rem;">Estado</th>
                    <th style="padding: 0.85rem 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr style="border-bottom: 1px solid var(--border); opacity: {{ $u->active ? '1' : '0.6' }};">
                        <td style="padding: 0.85rem 1rem; font-weight: 800; color: var(--text-main);">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <span>{{ $u->name }}</span>
                            </div>
                        </td>
                        <td style="padding: 0.85rem 1rem; color: var(--text-muted);">
                            {{ $u->email }}
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @foreach($u->roles as $r)
                                    <span class="badge" style="background: rgba(183, 148, 244, 0.15); color: var(--violet-text); font-size: 0.75rem;">
                                        {{ $r->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td style="padding: 0.85rem 1rem;">
                            @if($u->active)
                                <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">Activo</span>
                            @else
                                <span class="badge" style="background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                            @endif
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                <a href="{{ url('/gestion/usuarios/' . $u->id . '/editar') }}" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem; text-decoration: none;">
                                    Editar
                                </a>
                                <button type="button" wire:click="toggleActive({{ $u->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.8rem;">
                                    {{ $u->active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center;">
                            <x-ui.empty-state
                                title="No hay usuarios registrados"
                                description="Comience creando usuarios para su equipo."
                                icon="user"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
