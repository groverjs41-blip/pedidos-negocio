<div style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="gear" class="w-5 h-5" />
                </div>
                Panel de Gestión del Negocio
            </h1>
            <div class="page-header-subtitle">
                Administración centralizada de catálogo, clientes, usuarios y catálogo de envases.
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.25rem;">
        {{-- Productos --}}
        <a href="{{ url('/gestion/productos') }}" class="card" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="header-icon-wrap mint" style="width: 48px; height: 48px; border-radius: 14px;">
                    <x-ui.icon name="bag" class="w-6 h-6" />
                </div>
                <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">
                    {{ \App\Models\Product::count() }} Prod.
                </span>
            </div>
            <div>
                <h3 style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Productos</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Administrar precios, fotos y envases requeridos.</p>
            </div>
        </a>

        {{-- Categorías --}}
        <a href="{{ url('/gestion/categorias') }}" class="card" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="header-icon-wrap blue" style="width: 48px; height: 48px; border-radius: 14px;">
                    <x-ui.icon name="list" class="w-6 h-6" />
                </div>
                <span class="badge" style="background: rgba(66, 153, 225, 0.15); color: var(--info-text);">
                    {{ \App\Models\Category::count() }} Cat.
                </span>
            </div>
            <div>
                <h3 style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Categorías</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Organización y orden del menú del negocio.</p>
            </div>
        </a>

        {{-- Clientes --}}
        <a href="{{ url('/gestion/clientes') }}" class="card" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="header-icon-wrap warning" style="width: 48px; height: 48px; border-radius: 14px;">
                    <x-ui.icon name="user" class="w-6 h-6" />
                </div>
                <span class="badge" style="background: rgba(255, 183, 77, 0.15); color: var(--warning-text);">
                    {{ \App\Models\Customer::count() }} Cli.
                </span>
            </div>
            <div>
                <h3 style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Clientes</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Directorio, saldos, deuda y 360° del cliente.</p>
            </div>
        </a>

        {{-- Usuarios --}}
        <a href="{{ url('/gestion/usuarios') }}" class="card" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="header-icon-wrap violet" style="width: 48px; height: 48px; border-radius: 14px;">
                    <x-ui.icon name="user" class="w-6 h-6" />
                </div>
                <span class="badge" style="background: rgba(183, 148, 244, 0.15); color: var(--violet-text);">
                    {{ \App\Models\User::count() }} Usu.
                </span>
            </div>
            <div>
                <h3 style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Usuarios y Roles</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Gestión de accesos de personal y roles.</p>
            </div>
        </a>

        {{-- Envases --}}
        <a href="{{ url('/gestion/envases') }}" class="card" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="header-icon-wrap mint" style="width: 48px; height: 48px; border-radius: 14px;">
                    <x-ui.icon name="check" class="w-6 h-6" />
                </div>
                <span class="badge" style="background: rgba(39, 230, 164, 0.15); color: var(--primary);">
                    {{ \App\Models\ReturnableType::count() }} Tipos
                </span>
            </div>
            <div>
                <h3 style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Tipos de Envases</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Catálogo de tazas, vasos y envases retornables.</p>
            </div>
        </a>

        {{-- Auditoría Avanzada (Filament) --}}
        <a href="{{ url('/admin') }}" class="card" style="padding: 1.5rem; text-decoration: none; display: flex; flex-direction: column; gap: 0.85rem; border-color: rgba(255,255,255,0.08);">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="header-icon-wrap" style="width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,0.05); color: var(--text-muted);">
                    <x-ui.icon name="gear" class="w-6 h-6" />
                </div>
                <span class="badge" style="background: rgba(255, 255, 255, 0.08); color: var(--text-muted);">
                    BACKOFFICE
                </span>
            </div>
            <div>
                <h3 style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Auditoría Avanzada</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Panel técnico de logs, pagos y auditoría Filament.</p>
            </div>
        </a>
    </div>
</div>
