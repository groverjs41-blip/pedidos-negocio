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
        @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
            <a href="{{ url('/pedidos/nuevo') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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

            <a href="{{ url('/pedidos') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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
            <a href="{{ url('/cocina') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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
            <a href="{{ url('/reparto') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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
            <a href="{{ url('/caja') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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

            <a href="{{ url('/cierre') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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
            <a href="{{ url('/tazas') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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
            <a href="{{ url('/gestion') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
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

            <a href="{{ url('/admin') }}" class="card" style="padding: 1rem 1.25rem; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap" style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.05); color: var(--text-muted);">
                        <x-ui.icon name="gear" class="w-5 h-5" />
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Auditoría Avanzada</div>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Backoffice Filament</div>
                    </div>
                </div>
                <span style="color: var(--text-muted);">&rarr;</span>
            </a>
        @endif
    </div>
</div>
