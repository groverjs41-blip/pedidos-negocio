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
<body x-data="{ collapsed: localStorage.getItem('sidebar_collapsed') === 'true' }" x-init="$watch('collapsed', value => localStorage.setItem('sidebar_collapsed', value))">
    @auth
        <div class="connection-status" id="connectionStatusIndicator">
            <span class="dot online"></span> <span class="status-text">En línea</span>
        </div>

        <div class="app-layout" :class="{ 'sidebar-collapsed': collapsed }">
            {{-- DESKTOP SIDEBAR --}}
            <aside class="sidebar" :class="{ 'collapsed': collapsed }">
                <a href="{{ url('/inicio') }}" class="sidebar-brand">
                    <div class="brand-icon-wrap">
                        <x-ui.icon name="bag" class="w-5 h-5" />
                    </div>
                    <span class="brand-title" x-show="!collapsed">PEDIDOS <span>NEGOCIO</span></span>
                </a>

                <nav class="sidebar-nav">
                    <span class="nav-section-header" x-show="!collapsed">PRINCIPAL</span>

                    <a href="{{ url('/inicio') }}" class="nav-item {{ request()->is('inicio') ? 'active' : '' }}" :title="collapsed ? 'Inicio' : ''">
                        <x-ui.icon name="home" />
                        <span class="nav-label" x-show="!collapsed">Inicio</span>
                    </a>

                    @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                        <span class="nav-section-header" x-show="!collapsed">OPERACIÓN</span>

                        <a href="{{ url('/pedidos/nuevo') }}" class="nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}" :title="collapsed ? 'Nuevo Pedido' : ''">
                            <x-ui.icon name="plus" />
                            <span class="nav-label" x-show="!collapsed">Nuevo Pedido</span>
                        </a>
                        <a href="{{ url('/pedidos') }}" class="nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}" :title="collapsed ? 'Pedidos' : ''">
                            <x-ui.icon name="list" />
                            <span class="nav-label" x-show="!collapsed">Pedidos</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('cocina') || auth()->user()->hasRole('admin'))
                        @php
                            $kitchenCount = \App\Models\Order::whereIn('status', [\App\Enums\OrderStatus::NEW, \App\Enums\OrderStatus::PREPARING])->count();
                        @endphp
                        <a href="{{ url('/cocina') }}" class="nav-item {{ request()->is('cocina') ? 'active' : '' }}" :title="collapsed ? 'Cocina (' . $kitchenCount . ')' : ''">
                            <x-ui.icon name="chef" />
                            <span class="nav-label" x-show="!collapsed">Cocina</span>
                            @if($kitchenCount > 0)
                                <span class="nav-badge warning" x-show="!collapsed">{{ $kitchenCount }}</span>
                            @endif
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('reparto') || auth()->user()->hasRole('admin'))
                        @php
                            $deliveryCount = \App\Models\Order::where('status', \App\Enums\OrderStatus::READY)->count();
                        @endphp
                        <a href="{{ url('/reparto') }}" class="nav-item {{ request()->is('reparto') ? 'active' : '' }}" :title="collapsed ? 'Reparto (' . $deliveryCount . ' LISTOS)' : ''">
                            <x-ui.icon name="truck" />
                            <span class="nav-label" x-show="!collapsed">Reparto</span>
                            @if($deliveryCount > 0)
                                <span class="nav-badge success" x-show="!collapsed">{{ $deliveryCount }} LISTOS</span>
                            @endif
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <span class="nav-section-header" x-show="!collapsed">GESTIÓN</span>
                        <a href="{{ url('/gestion') }}" class="nav-item {{ request()->is('gestion') ? 'active' : '' }}" :title="collapsed ? 'Panel Gestión' : ''">
                            <x-ui.icon name="gear" />
                            <span class="nav-label" x-show="!collapsed">Panel Gestión</span>
                        </a>
                        <a href="{{ url('/gestion/productos') }}" class="nav-item {{ request()->is('gestion/productos*') ? 'active' : '' }}" :title="collapsed ? 'Productos' : ''">
                            <x-ui.icon name="bag" />
                            <span class="nav-label" x-show="!collapsed">Productos</span>
                        </a>
                        <a href="{{ url('/gestion/categorias') }}" class="nav-item {{ request()->is('gestion/categorias*') ? 'active' : '' }}" :title="collapsed ? 'Categorías' : ''">
                            <x-ui.icon name="list" />
                            <span class="nav-label" x-show="!collapsed">Categorías</span>
                        </a>
                        <a href="{{ url('/gestion/clientes') }}" class="nav-item {{ request()->is('gestion/clientes*') ? 'active' : '' }}" :title="collapsed ? 'Clientes' : ''">
                            <x-ui.icon name="user" />
                            <span class="nav-label" x-show="!collapsed">Clientes</span>
                        </a>
                        <a href="{{ url('/gestion/usuarios') }}" class="nav-item {{ request()->is('gestion/usuarios*') ? 'active' : '' }}" :title="collapsed ? 'Usuarios' : ''">
                            <x-ui.icon name="user" />
                            <span class="nav-label" x-show="!collapsed">Usuarios</span>
                        </a>
                        <a href="{{ url('/gestion/envases') }}" class="nav-item {{ request()->is('gestion/envases*') ? 'active' : '' }}" :title="collapsed ? 'Envases' : ''">
                            <x-ui.icon name="check" />
                            <span class="nav-label" x-show="!collapsed">Envases</span>
                        </a>
                        <a href="{{ url('/gestion/configuracion') }}" class="nav-item {{ request()->is('gestion/configuracion*') ? 'active' : '' }}" :title="collapsed ? 'Configuración' : ''">
                            <x-ui.icon name="gear" />
                            <span class="nav-label" x-show="!collapsed">Configuración</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('reparto'))
                        <span class="nav-section-header" x-show="!collapsed">FINANZAS</span>
                        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                            <a href="{{ url('/caja') }}" class="nav-item {{ request()->is('caja*') ? 'active' : '' }}" :title="collapsed ? 'Cobranza' : ''">
                                <x-ui.icon name="wallet" />
                                <span class="nav-label" x-show="!collapsed">Cobranza</span>
                            </a>
                        @endif

                        <a href="{{ url('/tazas') }}" class="nav-item {{ request()->is('tazas*') ? 'active' : '' }}" :title="collapsed ? 'Tazas / Envases' : ''">
                            <x-ui.icon name="check" />
                            <span class="nav-label" x-show="!collapsed">Tazas / Envases</span>
                        </a>

                        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                            <a href="{{ url('/cierre') }}" class="nav-item {{ request()->is('cierre*') ? 'active' : '' }}" :title="collapsed ? 'Cierre Diario' : ''">
                                <x-ui.icon name="wallet" />
                                <span class="nav-label" x-show="!collapsed">Cierre Diario</span>
                            </a>
                        @endif
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <span class="nav-section-header" x-show="!collapsed">SISTEMA</span>
                        <a href="{{ url('/admin') }}" class="nav-item" :title="collapsed ? 'Auditoría Avanzada' : ''" style="color: var(--text-muted);">
                            <x-ui.icon name="gear" />
                            <span class="nav-label" x-show="!collapsed">Auditoría Avanzada</span>
                        </a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <div class="user-profile">
                        <div class="user-avatar" title="{{ auth()->user()->name }}">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="user-details" x-show="!collapsed">
                            <div class="profile-name">{{ auth()->user()->name }}</div>
                            <div class="profile-role">{{ auth()->user()->roles()->first()?->name }}</div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;" x-show="!collapsed">
                        @csrf
                        <button type="submit" class="btn-logout-sidebar">Cerrar Sesión</button>
                    </form>
                </div>
            </aside>

            {{-- MAIN CONTENT WRAPPER --}}
            <div class="main-wrapper" :class="{ 'collapsed': collapsed }">
                {{-- TOPBAR STICKY INSIDE MAIN WRAPPER --}}
                <header class="topbar-unified">
                    <div class="topbar-left">
                        <button type="button" @click="collapsed = !collapsed" class="btn-toggle-sidebar" title="Colapsar / Expandir Menú">
                            <x-ui.icon name="sidebar-left" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="topbar-actions">
                        {{-- ATENDER AHORA OPERATIONAL ACCESS --}}
                        <livewire:operational-attention />

                        {{-- SOUND TOGGLE --}}
                        <button type="button" id="soundToggleBtn" onclick="window.soundEngine.toggleMute()" class="p-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-all flex items-center justify-center focus:outline-none" title="Alternar Sonido">
                            <x-ui.icon name="volume" class="w-5 h-5" />
                        </button>

                        {{-- NOTIFICATION BELL --}}
                        <livewire:notification-center />

                        <div class="connection-status-inline">
                            <span class="dot online"></span> <span class="status-text">En línea</span>
                        </div>

                        <div class="user-profile-inline">
                            <div class="user-avatar-sm" title="{{ auth()->user()->name }}">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="user-name-sm">{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                </header>

                <main class="content-area page-fade-in">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Toast Container for Operative Notifications --}}
        <div id="toastNotificationContainer" class="toast-container"></div>
    @else
        <main class="content-area page-fade-in">
            {{ $slot }}
        </main>
    @endauth

    @livewireScripts
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if(session()->has('success'))
                window.showOperationalToast({ type: 'success', title: 'Éxito', message: @json(session('success')) });
            @endif
            @if(session()->has('error'))
                window.showOperationalToast({ type: 'error', title: 'Error', message: @json(session('error')) });
            @endif
            @if(session()->has('warning'))
                window.showOperationalToast({ type: 'warning', title: 'Atención', message: @json(session('warning')) });
            @endif
            @if(session()->has('info'))
                window.showOperationalToast({ type: 'info', title: 'Información', message: @json(session('info')) });
            @endif
        });
    </script>
</body>
</html>
