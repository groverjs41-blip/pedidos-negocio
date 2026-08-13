<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pedidos Negocio' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @auth
        <div class="connection-status" id="connectionStatusIndicator">
            <span class="dot online"></span> <span class="status-text">En línea</span>
        </div>

        <div class="app-layout">
            {{-- DESKTOP SIDEBAR --}}
            <aside class="sidebar">
                <a href="{{ url('/inicio') }}" class="sidebar-brand">
                    <div class="brand-icon-wrap">
                        <x-ui.icon name="bag" class="w-5 h-5" />
                    </div>
                    <span class="brand-title">PEDIDOS <span>NEGOCIO</span></span>
                </a>

                <nav class="sidebar-nav">
                    <span class="nav-section-header">PRINCIPAL</span>

                    <a href="{{ url('/inicio') }}" class="nav-item {{ request()->is('inicio') ? 'active' : '' }}">
                        <x-ui.icon name="home" />
                        Inicio
                    </a>

                    @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                        <span class="nav-section-header">OPERACIÓN</span>

                        <a href="{{ url('/pedidos/nuevo') }}" class="nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                            <x-ui.icon name="plus" />
                            Nuevo Pedido
                        </a>
                        <a href="{{ url('/pedidos') }}" class="nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}">
                            <x-ui.icon name="list" />
                            Pedidos
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('cocina') || auth()->user()->hasRole('admin'))
                        <a href="{{ url('/cocina') }}" class="nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                            <x-ui.icon name="chef" />
                            Cocina
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('reparto') || auth()->user()->hasRole('admin'))
                        <a href="{{ url('/reparto') }}" class="nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                            <x-ui.icon name="truck" />
                            Reparto
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <span class="nav-section-header">GESTIÓN</span>
                        <a href="{{ url('/gestion') }}" class="nav-item {{ request()->is('gestion') ? 'active' : '' }}">
                            <x-ui.icon name="gear" />
                            Panel Gestión
                        </a>
                        <a href="{{ url('/gestion/productos') }}" class="nav-item {{ request()->is('gestion/productos*') ? 'active' : '' }}">
                            <x-ui.icon name="bag" />
                            Productos
                        </a>
                        <a href="{{ url('/gestion/categorias') }}" class="nav-item {{ request()->is('gestion/categorias*') ? 'active' : '' }}">
                            <x-ui.icon name="list" />
                            Categorías
                        </a>
                        <a href="{{ url('/gestion/clientes') }}" class="nav-item {{ request()->is('gestion/clientes*') ? 'active' : '' }}">
                            <x-ui.icon name="user" />
                            Clientes
                        </a>
                        <a href="{{ url('/gestion/usuarios') }}" class="nav-item {{ request()->is('gestion/usuarios*') ? 'active' : '' }}">
                            <x-ui.icon name="user" />
                            Usuarios
                        </a>
                        <a href="{{ url('/gestion/envases') }}" class="nav-item {{ request()->is('gestion/envases*') ? 'active' : '' }}">
                            <x-ui.icon name="check" />
                            Envases
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('reparto'))
                        <span class="nav-section-header">FINANZAS</span>
                        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                            <a href="{{ url('/caja') }}" class="nav-item {{ request()->is('caja*') ? 'active' : '' }}">
                                <x-ui.icon name="dollar" />
                                Cobranza
                            </a>
                        @endif

                        <a href="{{ url('/tazas') }}" class="nav-item {{ request()->is('tazas*') ? 'active' : '' }}">
                            <x-ui.icon name="check" />
                            Tazas / Envases
                        </a>

                        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                            <a href="{{ url('/cierre') }}" class="nav-item {{ request()->is('cierre*') ? 'active' : '' }}">
                                <x-ui.icon name="dollar" />
                                Cierre Diario
                            </a>
                        @endif
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <span class="nav-section-header">SISTEMA</span>
                        <a href="{{ url('/admin') }}" class="nav-item" style="color: var(--text-muted);">
                            <x-ui.icon name="gear" />
                            Auditoría Avanzada
                        </a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <div class="user-profile">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="user-details">
                            <div class="profile-name">{{ auth()->user()->name }}</div>
                            <div class="profile-role">{{ auth()->user()->roles->first()?->name }}</div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn-logout-sidebar">Cerrar Sesión</button>
                    </form>
                </div>
            </aside>

            {{-- MAIN CONTENT WRAPPER --}}
            <div class="main-wrapper">
                {{-- MOBILE TOPBAR --}}
                <header class="topbar">
                    <div class="mobile-logo-header">
                        <div class="brand-icon-wrap" style="width: 30px; height: 30px; border-radius: 8px;">
                            <x-ui.icon name="bag" class="w-4 h-4" />
                        </div>
                        <span class="brand-title" style="font-size: 0.95rem;">PEDIDOS <span>NEGOCIO</span></span>
                    </div>
                    <div class="topbar-actions">
                        <div class="connection-status-inline">
                            <span class="dot online"></span> <span class="status-text">En línea</span>
                        </div>
                        <div class="user-avatar" style="width: 28px; height: 28px; font-size: 0.75rem;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                </header>

                <main class="content-area page-fade-in">
                    {{ $slot }}
                </main>
            </div>

            {{-- MOBILE BOTTOM NAV BAR (Max 5 items) --}}
            <nav class="mobile-bottom-nav">
                <a href="{{ url('/inicio') }}" class="mobile-nav-item {{ request()->is('inicio') ? 'active' : '' }}">
                    <x-ui.icon name="home" />
                    <span class="mobile-nav-label">Inicio</span>
                </a>

                @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                    <a href="{{ url('/pedidos/nuevo') }}" class="mobile-nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                        <x-ui.icon name="plus" />
                        <span class="mobile-nav-label">Nuevo</span>
                    </a>
                    <a href="{{ url('/pedidos') }}" class="mobile-nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}">
                        <x-ui.icon name="list" />
                        <span class="mobile-nav-label">Pedidos</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole('cocina') || auth()->user()->hasRole('admin'))
                    <a href="{{ url('/cocina') }}" class="mobile-nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                        <x-ui.icon name="chef" />
                        <span class="mobile-nav-label">Cocina</span>
                    </a>
                @endif

                <a href="{{ url('/menu') }}" class="mobile-nav-item {{ request()->is('menu') || request()->is('gestion*') ? 'active' : '' }}">
                    <x-ui.icon name="gear" />
                    <span class="mobile-nav-label">Más</span>
                </a>
            </nav>
        </div>
    @else
        {{ $slot }}
    @endauth

    @livewireScripts
    <script>
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);

        function updateConnectionStatus() {
            const indicators = document.querySelectorAll('.connection-status, .connection-status-inline');
            indicators.forEach(indicator => {
                const dot = indicator.querySelector('.dot');
                const text = indicator.querySelector('.status-text');
                if (navigator.onLine) {
                    dot.className = 'dot online';
                    text.textContent = 'En línea';
                } else {
                    dot.className = 'dot offline';
                    text.textContent = 'Sin conexión';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', updateConnectionStatus);
    </script>
</body>
</html>
