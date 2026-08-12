<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pedidos Negocio' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    </div>
                    <span class="brand-text">Pedidos Negocio</span>
                </a>

                <nav class="sidebar-nav">
                    <span class="nav-section-header">OPERACIÓN</span>

                    <a href="{{ url('/inicio') }}" class="nav-item {{ request()->is('inicio') ? 'active' : '' }}">
                        <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></span>
                        Inicio
                    </a>

                    @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                        <a href="{{ url('/pedidos/nuevo') }}" class="nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                            <span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg></span>
                            Nuevo Pedido
                        </a>
                        <a href="{{ url('/pedidos') }}" class="nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}">
                            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></span>
                            Pedidos
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('cocina') || auth()->user()->hasRole('admin'))
                        <span class="nav-section-header">PRODUCCIÓN</span>
                        <a href="{{ url('/cocina') }}" class="nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle><line x1="5.4" y1="2" x2="18.6" y2="2" stroke-width="2"></line></svg></span>
                            Cocina
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('reparto') || auth()->user()->hasRole('admin'))
                        <span class="nav-section-header">LOGÍSTICA</span>
                        <a href="{{ url('/reparto') }}" class="nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                            <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                            Reparto
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                        <span class="nav-section-header">FINANZAS</span>
                        <a href="{{ url('/caja') }}" class="nav-item {{ request()->is('caja') ? 'active' : '' }}">
                            <span class="nav-icon"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></span>
                            Cobranza
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <span class="nav-section-header">SISTEMA</span>
                        <a href="{{ url('/admin') }}" class="nav-item">
                            <span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></span>
                            Administración
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

            {{-- MAIN CONTAINER --}}
            <div class="main-wrapper">
                {{-- TOP HEADER FOR MOBILE --}}
                <header class="topbar">
                    <div class="mobile-logo-header">
                        <div class="brand-icon" style="width: 28px; height: 28px; border-radius: 6px;">
                            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px;"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#FFFFFF"/></svg>
                        </div>
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

            {{-- MOBILE BOTTOM NAVIGATION --}}
            <nav class="mobile-bottom-nav">
                <a href="{{ url('/inicio') }}" class="mobile-nav-item {{ request()->is('inicio') ? 'active' : '' }}">
                    <span class="mobile-nav-icon"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></span>
                    <span class="mobile-nav-label">Inicio</span>
                </a>

                @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                    <a href="{{ url('/pedidos/nuevo') }}" class="mobile-nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                        <span class="mobile-nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg></span>
                        <span class="mobile-nav-label">Nuevo</span>
                    </a>
                    <a href="{{ url('/pedidos') }}" class="mobile-nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}">
                        <span class="mobile-nav-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></span>
                        <span class="mobile-nav-label">Pedidos</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole('cocina') && !auth()->user()->hasRole('admin'))
                    <a href="{{ url('/cocina') }}" class="mobile-nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                        <span class="mobile-nav-icon"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg></span>
                        <span class="mobile-nav-label">Cocina</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole('reparto') && !auth()->user()->hasRole('admin'))
                    <a href="{{ url('/reparto') }}" class="mobile-nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                        <span class="mobile-nav-icon"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                        <span class="mobile-nav-label">Reparto</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole('admin'))
                    <a href="{{ url('/cocina') }}" class="mobile-nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                        <span class="mobile-nav-icon"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg></span>
                        <span class="mobile-nav-label">Cocina</span>
                    </a>
                    <a href="{{ url('/reparto') }}" class="mobile-nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                        <span class="mobile-nav-icon"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
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
