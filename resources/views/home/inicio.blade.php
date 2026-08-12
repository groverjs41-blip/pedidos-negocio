<x-layouts.app title="Inicio - Pedidos Negocio">
    <div style="display: flex; flex-direction: column; gap: 1.75rem;">
        {{-- Header Greeting --}}
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.02em;">
                ¡Hola, {{ $user->name }}!
            </h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                Así va la operación de tu negocio hoy.
            </p>
        </div>

        {{-- Metrics Summary Grid --}}
        <div class="metrics-grid">
            <div class="metric-card">
                <span class="metric-val" style="color: var(--info-text);">
                    {{ \App\Models\Order::whereDate('created_at', today())->count() }}
                </span>
                <span class="metric-label">Pedidos Hoy</span>
            </div>

            <div class="metric-card">
                <span class="metric-val" style="color: var(--warning-text);">
                    {{ \App\Models\Order::where('status', \App\Enums\OrderStatus::PREPARING)->count() }}
                </span>
                <span class="metric-label">En Cocina</span>
            </div>

            <div class="metric-card">
                <span class="metric-val" style="color: var(--primary);">
                    {{ \App\Models\Order::where('status', \App\Enums\OrderStatus::READY)->count() }}
                </span>
                <span class="metric-label">Listos</span>
            </div>

            <div class="metric-card">
                <span class="metric-val" style="color: var(--violet-text);">
                    {{ \App\Models\Order::where('status', \App\Enums\OrderStatus::DELIVERING)->count() }}
                </span>
                <span class="metric-label">En Reparto</span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div>
            <h2 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.85rem;">
                Acciones Rápidas
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                @if($user->hasRole('pedidos') || $user->hasRole('admin'))
                    <a href="{{ url('/pedidos/nuevo') }}" class="card" style="padding: 1.25rem; text-decoration: none; display: flex; align-items: center; gap: 1rem;">
                        <div class="header-icon-wrap mint" style="width: 44px; height: 44px; border-radius: 14px;">
                            <x-ui.icon name="plus" class="w-6 h-6" />
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">Nuevo Pedido</div>
                            <div style="font-size: 0.775rem; color: var(--text-muted);">Registrar venta en POS</div>
                        </div>
                    </a>

                    <a href="{{ url('/pedidos') }}" class="card" style="padding: 1.25rem; text-decoration: none; display: flex; align-items: center; gap: 1rem;">
                        <div class="header-icon-wrap blue" style="width: 44px; height: 44px; border-radius: 14px;">
                            <x-ui.icon name="list" class="w-6 h-6" />
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">Ver Pedidos</div>
                            <div style="font-size: 0.775rem; color: var(--text-muted);">Historial y seguimiento</div>
                        </div>
                    </a>
                @endif

                @if($user->hasRole('cocina') || $user->hasRole('admin'))
                    <a href="{{ url('/cocina') }}" class="card" style="padding: 1.25rem; text-decoration: none; display: flex; align-items: center; gap: 1rem;">
                        <div class="header-icon-wrap warning" style="width: 44px; height: 44px; border-radius: 14px;">
                            <x-ui.icon name="chef" class="w-6 h-6" />
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">Cocina KDS</div>
                            <div style="font-size: 0.775rem; color: var(--text-muted);">Pantalla de preparación</div>
                        </div>
                    </a>
                @endif

                @if($user->hasRole('reparto') || $user->hasRole('admin'))
                    <a href="{{ url('/reparto') }}" class="card" style="padding: 1.25rem; text-decoration: none; display: flex; align-items: center; gap: 1rem;">
                        <div class="header-icon-wrap violet" style="width: 44px; height: 44px; border-radius: 14px;">
                            <x-ui.icon name="truck" class="w-6 h-6" />
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">Panel Reparto</div>
                            <div style="font-size: 0.775rem; color: var(--text-muted);">Gestión de entregas</div>
                        </div>
                    </a>
                @endif

                @if($user->hasRole('admin'))
                    <a href="{{ url('/admin') }}" class="card" style="padding: 1.25rem; text-decoration: none; display: flex; align-items: center; gap: 1rem;">
                        <div class="header-icon-wrap mint" style="width: 44px; height: 44px; border-radius: 14px;">
                            <x-ui.icon name="gear" class="w-6 h-6" />
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">Administración</div>
                            <div style="font-size: 0.775rem; color: var(--text-muted);">Panel de Filament</div>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
