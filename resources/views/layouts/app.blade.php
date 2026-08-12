<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pedidos Negocio' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @auth
        <div class="connection-status" id="connectionStatusIndicator">
            <span class="dot online"></span> <span class="status-text">En línea</span>
        </div>

        <div class="app-layout">
            <!-- DESKTOP SIDEBAR -->
            <aside class="sidebar">
                <a href="{{ url('/inicio') }}" class="sidebar-brand">
                    <span class="brand-logo">🍔</span>
                    <span class="brand-text">Pedidos Negocio</span>
                </a>
                
                <nav class="sidebar-nav">
                    <a href="{{ url('/inicio') }}" class="nav-item {{ request()->is('inicio') ? 'active' : '' }}">
                        <span class="nav-icon">🏠</span> Inicio
                    </a>
                    
                    @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                        <a href="{{ url('/pedidos/nuevo') }}" class="nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                            <span class="nav-icon">📝</span> Nuevo Pedido
                        </a>
                        <a href="{{ url('/pedidos') }}" class="nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}">
                            <span class="nav-icon">📋</span> Pedidos
                        </a>
                    @endif
                    
                    @if(auth()->user()->hasRole('cocina') || auth()->user()->hasRole('admin'))
                        <a href="{{ url('/cocina') }}" class="nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                            <span class="nav-icon">🍳</span> Cocina
                        </a>
                    @endif
                    
                    @if(auth()->user()->hasRole('reparto') || auth()->user()->hasRole('admin'))
                        <a href="{{ url('/reparto') }}" class="nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                            <span class="nav-icon">🛵</span> Reparto
                        </a>
                    @endif
                    
                    @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                        <a href="{{ url('/caja') }}" class="nav-item {{ request()->is('caja') ? 'active' : '' }}">
                            <span class="nav-icon">💵</span> Cobranza
                        </a>
                    @endif
                    
                    @if(auth()->user()->hasRole('admin'))
                        <a href="{{ url('/admin') }}" class="nav-item">
                            <span class="nav-icon">⚙️</span> Administración
                        </a>
                    @endif
                </nav>
                
                <div class="sidebar-footer">
                    <div class="user-profile">
                        <div class="profile-name">{{ auth()->user()->name }}</div>
                        <div class="profile-role">{{ auth()->user()->roles->first()?->name }}</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn-logout-sidebar">Cerrar Sesión</button>
                    </form>
                </div>
            </aside>

            <!-- MAIN CONTAINER -->
            <div class="main-wrapper">
                <!-- TOP HEADER FOR MOBILE -->
                <header class="topbar">
                    <div class="mobile-logo-header">
                        <span class="brand-logo">🍔</span>
                        <span class="brand-text">Pedidos Negocio</span>
                    </div>
                    <div class="topbar-actions">
                        <div class="connection-status-inline">
                            <span class="dot online"></span> <span class="status-text">En línea</span>
                        </div>
                        <span class="user-name-inline">{{ auth()->user()->name }}</span>
                    </div>
                </header>

                <main class="content-area">
                    {{ $slot }}
                </main>
            </div>
            
            <!-- MOBILE BOTTOM NAVIGATION -->
            <nav class="mobile-bottom-nav">
                <a href="{{ url('/inicio') }}" class="mobile-nav-item {{ request()->is('inicio') ? 'active' : '' }}">
                    <span class="mobile-nav-icon">🏠</span>
                    <span class="mobile-nav-label">Inicio</span>
                </a>
                
                @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                    <a href="{{ url('/pedidos/nuevo') }}" class="mobile-nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                        <span class="mobile-nav-icon">📝</span>
                        <span class="mobile-nav-label">Nuevo</span>
                    </a>
                    <a href="{{ url('/pedidos') }}" class="mobile-nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}">
                        <span class="mobile-nav-icon">📋</span>
                        <span class="mobile-nav-label">Pedidos</span>
                    </a>
                @endif
                
                @if(auth()->user()->hasRole('cocina') && !auth()->user()->hasRole('admin'))
                    <a href="{{ url('/cocina') }}" class="mobile-nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                        <span class="mobile-nav-icon">🍳</span>
                        <span class="mobile-nav-label">Cocina</span>
                    </a>
                @endif
                
                @if(auth()->user()->hasRole('reparto') && !auth()->user()->hasRole('admin'))
                    <a href="{{ url('/reparto') }}" class="mobile-nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                        <span class="mobile-nav-icon">🛵</span>
                        <span class="mobile-nav-label">Reparto</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ url('/cocina') }}" class="mobile-nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                        <span class="mobile-nav-icon">🍳</span>
                        <span class="mobile-nav-label">Cocina</span>
                    </a>
                    <a href="{{ url('/reparto') }}" class="mobile-nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                        <span class="mobile-nav-icon">🛵</span>
                        <span class="mobile-nav-label">Reparto</span>
                    </a>
                @endif
            </nav>
        </div>
    @else
        {{ $slot }}
    @endauth

    @livewireScripts
    <script>
        // Simple offline state detection to update indicators
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
        
        // Run once on load
        document.addEventListener('DOMContentLoaded', updateConnectionStatus);
    </script>
</body>
</html>
