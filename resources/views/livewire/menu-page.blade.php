<div style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem;">
    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="gear" class="w-5 h-5" />
                </div>
                Menú General y Módulos
            </h1>
            <div class="page-header-subtitle">
                Acceso a todos los módulos permitidos para su usuario.
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        {{-- Quick Preferences (Theme / Sound for Mobile) --}}
        <div class="card" style="padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div class="header-icon-wrap violet" style="width: 40px; height: 40px; border-radius: 10px;">
                    <x-ui.icon name="gear" class="w-5 h-5" />
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Preferencias de Sistema</div>
                    <div style="font-size: 0.775rem; color: var(--text-muted);">Modo Claro/Oscuro y Audio de avisos</div>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button type="button" onclick="toggleTheme()" class="chip-btn" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
                    🌙/☀️ Tema
                </button>
                <button type="button" onclick="window.soundEngine.toggleMute()" class="chip-btn" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
                    🔊 Audio
                </button>
            </div>
        </div>

        @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
            <a href="{{ url('/pedidos/nuevo') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap mint" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="plus" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Nuevo Pedido</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Toma de pedidos POS</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>

            <a href="{{ url('/pedidos') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap blue" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="list" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Lista de Pedidos</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Seguimiento de comandas</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>
        @endif

        @if(auth()->user()->hasRole('cocina') || auth()->user()->hasRole('admin'))
            <a href="{{ url('/cocina') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap warning" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="chef" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Pantalla de Cocina</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">KDS de preparación</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>
        @endif

        @if(auth()->user()->hasRole('reparto') || auth()->user()->hasRole('admin'))
            <a href="{{ url('/reparto') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap violet" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="truck" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Panel de Reparto</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Gestión de entregas</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>
        @endif

        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
            <a href="{{ url('/caja') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap mint" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="dollar" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Cobranza y Caja</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Abonos y pagos de clientes</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>

            <a href="{{ url('/cierre') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap warning" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="dollar" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Cierre Diario</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Resumen e historial de cierres</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>
        @endif

        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('reparto') || auth()->user()->hasRole('admin'))
            <a href="{{ url('/tazas') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap violet" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="check" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Envases Retornables</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Tazas y vasos pendientes</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>
        @endif

        @if(auth()->user()->hasRole('admin'))
            <a href="{{ url('/gestion') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap mint" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="gear" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Panel de Gestión</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Productos, categorías, clientes, usuarios</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>

            <a href="{{ url('/gestion/configuracion') }}" wire:navigate class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap violet" style="width: 40px; height: 40px; border-radius: 10px;">
                        <x-ui.icon name="gear" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Configuración General</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Moneda, sonidos, negocio y zona horaria</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>
        @endif

        {{-- Cerrar Sesión Card --}}
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.15); color: #EF4444; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                        🚪
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Cerrar sesión</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Usuario: {{ auth()->user()->name }}</div>
                    </div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('¿Deseas cerrar la sesión?');" style="margin: 0; width: 100%;">
                @csrf
                <button type="submit" class="btn-primary" style="width: 100%; height: 44px; font-weight: 800; font-size: 0.9rem; background: #DC2626; color: #FFFFFF; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; gap: 0.5rem; border: none; cursor: pointer;">
                    🚪 CERRAR SESIÓN
                </button>
            </form>
        </div>
    </div>
</div>
