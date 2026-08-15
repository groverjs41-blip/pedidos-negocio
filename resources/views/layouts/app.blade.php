<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title ?? 'Pedidos Negocio' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @if(config('app.debug'))
    <script>
        window.findHorizontalOverflow = function () {
            return [...document.querySelectorAll('*')]
                .filter(el => {
                    const r = el.getBoundingClientRect();
                    return r.right > document.documentElement.clientWidth + 1 || r.left < -1;
                })
                .map(el => ({
                    tag: el.tagName,
                    class: el.className,
                    id: el.id,
                    rect: el.getBoundingClientRect().toJSON()
                }));
        };
    </script>
    @endif

    {{-- Blocking Pre-render Script for Theme & Sidebar State to Eliminate Flash of Unstyled Content (FOUC) --}}
    <script>
        (function() {
            window.applySavedTheme = function () {
                const theme = localStorage.getItem('theme') || 'dark';
                const html = document.documentElement;

                html.classList.toggle('dark', theme === 'dark');
                html.classList.toggle('light', theme === 'light');

                if (document.body && window.Alpine) {
                    try {
                        const data = Alpine.$data(document.body);
                        if (data) {
                            data.currentTheme = theme;
                        }
                    } catch (e) {}
                }

                return theme;
            };

            var isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            if (isCollapsed) {
                document.documentElement.classList.add('sidebar-collapsed-preload');
            }

            window.applySavedTheme();

            // Observe attribute changes on <html> to instantly catch Livewire SPA DOM morphing
            var themeObserver = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].attributeName === 'class') {
                        window.applySavedTheme();
                        break;
                    }
                }
            });
            themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
    
    <style>
        /* Instant Preload CSS to prevent Flash of Unstyled Content (FOUC) */
        html.sidebar-collapsed-preload .sidebar { width: 72px !important; }
        html.sidebar-collapsed-preload .main-wrapper { margin-left: 72px !important; }
        html.sidebar-collapsed-preload .nav-label,
        html.sidebar-collapsed-preload .nav-section-header,
        html.sidebar-collapsed-preload .brand-title,
        html.sidebar-collapsed-preload .user-details,
        html.sidebar-collapsed-preload .btn-logout-sidebar,
        html.sidebar-collapsed-preload .nav-badge { display: none !important; }
        html.sidebar-collapsed-preload .sidebar-brand,
        html.sidebar-collapsed-preload .user-profile { justify-content: center !important; }
        html.sidebar-collapsed-preload .nav-item { justify-content: center !important; padding: 0.75rem 0 !important; width: 48px !important; height: 48px !important; margin: 0 auto !important; }
        
        .no-transitions *, .no-transitions { transition: none !important; }
    </style>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="no-transitions" x-data="{ collapsed: localStorage.getItem('sidebar_collapsed') === 'true', currentTheme: localStorage.getItem('theme') || 'dark', showOperacionModal: false }" x-init="$watch('collapsed', value => localStorage.setItem('sidebar_collapsed', value))">
    {{-- GLOBAL TOP LOADING PROGRESS BAR FOR MOBILE, TABLET & DESKTOP --}}
    <div id="globalLoadingBar" class="global-loading-bar"></div>
    @if(auth()->check())
        @php
            $showLoginSplash = session()->pull('show_login_splash', false);
        @endphp

        @if($showLoginSplash)
            @php
                $splashSettings = app(\App\Services\BusinessSettingsService::class)->getSettings();
                $splashBusinessName = $splashSettings->business_name ?? 'Pedidos Negocio';
            @endphp
            <div id="appLoginSplash" class="app-login-splash" aria-hidden="true">
                <div class="splash-content">
                    <div class="splash-logo-wrap">
                        <div class="splash-icon-glow"></div>
                        <div class="splash-icon">
                            <x-ui.icon name="bag" class="w-10 h-10" />
                        </div>
                    </div>

                    <h1 class="splash-title">{{ $splashBusinessName }}</h1>
                    <p class="splash-subtitle">Preparando tu jornada</p>

                    <div class="splash-loading-line">
                        <span></span>
                    </div>
                </div>
            </div>
            <script>
                (function () {
                    const splash = document.getElementById('appLoginSplash');
                    if (!splash) return;

                    window.setTimeout(function () {
                        splash.classList.add('splash-exit');
                    }, 1250);

                    window.setTimeout(function () {
                        splash.remove();
                    }, 1750);
                })();
            </script>
        @endif
        <div class="app-layout" :class="{ 'sidebar-collapsed': collapsed }">
            {{-- DESKTOP SIDEBAR (>=1024px) --}}
            <aside class="sidebar hidden lg:flex" :class="{ 'collapsed': collapsed }">
                <a href="{{ url('/inicio') }}" class="sidebar-brand">
                    <div class="brand-icon-wrap">
                        <x-ui.icon name="bag" class="w-5 h-5" />
                    </div>
                    <span class="brand-title" x-show="!collapsed">PEDIDOS <span>NEGOCIO</span></span>
                </a>

                <nav class="sidebar-nav">
                    <span class="nav-section-header" x-show="!collapsed">PRINCIPAL</span>

                    <a href="{{ url('/inicio') }}" wire:navigate.hover class="nav-item {{ request()->is('inicio') ? 'active' : '' }}" :title="collapsed ? 'Inicio' : ''">
                        <x-ui.icon name="home" />
                        <span class="nav-label" x-show="!collapsed">Inicio</span>
                    </a>

                    @if(auth()->user()->hasRole('pedidos') || auth()->user()->hasRole('admin'))
                        <span class="nav-section-header" x-show="!collapsed">OPERACIÓN</span>

                        <a href="{{ url('/pedidos/nuevo') }}" wire:navigate class="nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}" :title="collapsed ? 'Nuevo Pedido' : ''">
                            <x-ui.icon name="plus" />
                            <span class="nav-label" x-show="!collapsed">Nuevo Pedido</span>
                        </a>
                        <a href="{{ url('/pedidos') }}" wire:navigate class="nav-item {{ request()->is('pedidos') || request()->is('pedidos/*/editar') ? 'active' : '' }}" :title="collapsed ? 'Pedidos' : ''">
                            <x-ui.icon name="list" />
                            <span class="nav-label" x-show="!collapsed">Pedidos</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('cocina') || auth()->user()->hasRole('admin'))
                        @php
                            $kitchenCount = \App\Models\Order::whereIn('status', [\App\Enums\OrderStatus::NEW, \App\Enums\OrderStatus::PREPARING])->count();
                        @endphp
                        <a href="{{ url('/cocina') }}" wire:navigate class="nav-item {{ request()->is('cocina') ? 'active' : '' }}" :title="collapsed ? 'Cocina ({{ $kitchenCount }})' : ''">
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
                        <a href="{{ url('/reparto') }}" wire:navigate class="nav-item {{ request()->is('reparto') ? 'active' : '' }}" :title="collapsed ? 'Reparto ({{ $deliveryCount }} LISTOS)' : ''">
                            <x-ui.icon name="truck" />
                            <span class="nav-label" x-show="!collapsed">Reparto</span>
                            @if($deliveryCount > 0)
                                <span class="nav-badge success" x-show="!collapsed">{{ $deliveryCount }} LISTOS</span>
                            @endif
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('admin'))
                        <span class="nav-section-header" x-show="!collapsed">GESTIÓN</span>
                        <a href="{{ url('/gestion') }}" wire:navigate class="nav-item {{ request()->is('gestion') ? 'active' : '' }}" :title="collapsed ? 'Panel Gestión' : ''">
                            <x-ui.icon name="gear" />
                            <span class="nav-label" x-show="!collapsed">Panel Gestión</span>
                        </a>
                        <a href="{{ url('/gestion/productos') }}" wire:navigate class="nav-item {{ request()->is('gestion/productos*') ? 'active' : '' }}" :title="collapsed ? 'Productos' : ''">
                            <x-ui.icon name="bag" />
                            <span class="nav-label" x-show="!collapsed">Productos</span>
                        </a>
                        <a href="{{ url('/gestion/categorias') }}" wire:navigate class="nav-item {{ request()->is('gestion/categorias*') ? 'active' : '' }}" :title="collapsed ? 'Categorías' : ''">
                            <x-ui.icon name="list" />
                            <span class="nav-label" x-show="!collapsed">Categorías</span>
                        </a>
                        <a href="{{ url('/gestion/clientes') }}" wire:navigate class="nav-item {{ request()->is('gestion/clientes*') ? 'active' : '' }}" :title="collapsed ? 'Clientes' : ''">
                            <x-ui.icon name="user" />
                            <span class="nav-label" x-show="!collapsed">Clientes</span>
                        </a>
                        <a href="{{ url('/gestion/usuarios') }}" wire:navigate class="nav-item {{ request()->is('gestion/usuarios*') ? 'active' : '' }}" :title="collapsed ? 'Usuarios' : ''">
                            <x-ui.icon name="user" />
                            <span class="nav-label" x-show="!collapsed">Usuarios</span>
                        </a>
                        <a href="{{ url('/gestion/envases') }}" wire:navigate class="nav-item {{ request()->is('gestion/envases*') ? 'active' : '' }}" :title="collapsed ? 'Envases' : ''">
                            <x-ui.icon name="check" />
                            <span class="nav-label" x-show="!collapsed">Envases</span>
                        </a>
                        <a href="{{ url('/gestion/configuracion') }}" wire:navigate class="nav-item {{ request()->is('gestion/configuracion*') ? 'active' : '' }}" :title="collapsed ? 'Configuración' : ''">
                            <x-ui.icon name="gear" />
                            <span class="nav-label" x-show="!collapsed">Configuración</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('reparto'))
                        <span class="nav-section-header" x-show="!collapsed">FINANZAS</span>
                        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                            <a href="{{ url('/caja') }}" wire:navigate class="nav-item {{ request()->is('caja*') ? 'active' : '' }}" :title="collapsed ? 'Cobranza' : ''">
                                <x-ui.icon name="wallet" />
                                <span class="nav-label" x-show="!collapsed">Cobranza</span>
                            </a>
                        @endif

                        <a href="{{ url('/tazas') }}" wire:navigate class="nav-item {{ request()->is('tazas*') ? 'active' : '' }}" :title="collapsed ? 'Tazas / Envases' : ''">
                            <x-ui.icon name="check" />
                            <span class="nav-label" x-show="!collapsed">Tazas / Envases</span>
                        </a>

                        @if(auth()->user()->hasRole('caja') || auth()->user()->hasRole('admin'))
                            <a href="{{ url('/cierre') }}" wire:navigate class="nav-item {{ request()->is('cierre*') ? 'active' : '' }}" :title="collapsed ? 'Cierre Diario' : ''">
                                <x-ui.icon name="wallet" />
                                <span class="nav-label" x-show="!collapsed">Cierre Diario</span>
                            </a>
                        @endif
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
                        <button type="button" @click="collapsed = !collapsed" class="btn-toggle-sidebar hidden lg:flex" title="Colapsar / Expandir Menú">
                            <x-ui.icon name="sidebar-left" class="w-5 h-5" />
                        </button>
                        <a href="{{ url('/inicio') }}" wire:navigate class="mobile-logo-brand lg:hidden">
                            <div class="brand-icon-wrap" style="width: 32px; height: 32px;">
                                <x-ui.icon name="bag" class="w-4 h-4" />
                            </div>
                            <span class="font-extrabold text-sm text-slate-100 tracking-tight">PEDIDOS <span style="color: var(--primary);">NEGOCIO</span></span>
                        </a>
                    </div>

                    <div class="topbar-actions">
                        {{-- ATENDER AHORA OPERATIONAL ACCESS --}}
                        <livewire:operational-attention />

                        {{-- THEME TOGGLE BUTTON (Desktop only, mobile in /menu) --}}
                        <button type="button" id="themeToggleBtn" onclick="toggleTheme()" class="p-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-all hidden lg:flex items-center justify-center focus:outline-none" title="Alternar Modo Claro / Oscuro">
                            <span x-show="currentTheme === 'dark'" style="display: flex; align-items: center;">
                                <x-ui.icon name="sun" class="w-5 h-5 text-amber-400" />
                            </span>
                            <span x-show="currentTheme !== 'dark'" style="display: flex; align-items: center;">
                                <x-ui.icon name="moon" class="w-5 h-5 text-indigo-400" />
                            </span>
                        </button>

                        {{-- SOUND TOGGLE (Desktop only, mobile in /menu) --}}
                        <button type="button" id="soundToggleBtn" onclick="window.soundEngine.toggleMute()" class="p-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-all hidden lg:flex items-center justify-center focus:outline-none" title="Alternar Sonido">
                            <x-ui.icon name="volume" class="w-5 h-5" />
                        </button>

                        {{-- NOTIFICATION BELL --}}
                        <livewire:notification-center />

                        <div class="connection-status-inline" id="connectionStatusIndicator">
                            <span class="dot online" title="Realtime conectado"></span> <span class="status-text hidden sm:inline">En línea</span>
                        </div>

                        <div class="user-profile-inline">
                            <div class="user-avatar-sm" title="{{ auth()->user()->name }}">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="user-name-sm hidden sm:inline">{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                </header>

                <main class="content-area page-fade-in">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- MOBILE & TABLET BOTTOM NAVIGATION (<=1023px) --}}
        <nav class="mobile-bottom-nav">
            <a href="{{ url('/inicio') }}" wire:navigate.hover class="mobile-nav-item {{ request()->is('inicio') ? 'active' : '' }}">
                <x-ui.icon name="home" class="w-5 h-5" />
                <span>Inicio</span>
            </a>

            @if(auth()->user()->hasRole('admin'))
                <a href="{{ url('/pedidos/nuevo') }}" wire:navigate class="mobile-nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                    <x-ui.icon name="plus" class="w-5 h-5" />
                    <span>Nuevo</span>
                </a>
                <a href="{{ url('/pedidos') }}" wire:navigate class="mobile-nav-item {{ request()->is('pedidos') ? 'active' : '' }}">
                    <x-ui.icon name="list" class="w-5 h-5" />
                    <span>Pedidos</span>
                </a>
                <button type="button" @click="showOperacionModal = true" class="mobile-nav-item {{ request()->is('cocina') || request()->is('reparto') ? 'active' : '' }}">
                    <x-ui.icon name="chef" class="w-5 h-5" />
                    <span>Operación</span>
                </button>
            @elseif(auth()->user()->hasRole('pedidos'))
                <a href="{{ url('/pedidos/nuevo') }}" wire:navigate class="mobile-nav-item {{ request()->is('pedidos/nuevo') ? 'active' : '' }}">
                    <x-ui.icon name="plus" class="w-5 h-5" />
                    <span>Nuevo</span>
                </a>
                <a href="{{ url('/pedidos') }}" wire:navigate class="mobile-nav-item {{ request()->is('pedidos') ? 'active' : '' }}">
                    <x-ui.icon name="list" class="w-5 h-5" />
                    <span>Pedidos</span>
                </a>
            @elseif(auth()->user()->hasRole('cocina'))
                <a href="{{ url('/cocina') }}" wire:navigate class="mobile-nav-item {{ request()->is('cocina') ? 'active' : '' }}">
                    <x-ui.icon name="chef" class="w-5 h-5" />
                    <span>Cocina</span>
                </a>
            @elseif(auth()->user()->hasRole('reparto'))
                <a href="{{ url('/reparto') }}" wire:navigate class="mobile-nav-item {{ request()->is('reparto') ? 'active' : '' }}">
                    <x-ui.icon name="truck" class="w-5 h-5" />
                    <span>Reparto</span>
                </a>
            @elseif(auth()->user()->hasRole('caja'))
                <a href="{{ url('/caja') }}" wire:navigate class="mobile-nav-item {{ request()->is('caja*') ? 'active' : '' }}">
                    <x-ui.icon name="wallet" class="w-5 h-5" />
                    <span>Caja</span>
                </a>
            @endif

            <a href="{{ url('/menu') }}" wire:navigate class="mobile-nav-item {{ request()->is('menu') ? 'active' : '' }}">
                <x-ui.icon name="dots" class="w-5 h-5" />
                <span>Más</span>
            </a>

            {{-- OPERACIÓN BOTTOM SHEET FOR ADMIN --}}
            <template x-teleport="body">
                <div x-show="showOperacionModal" style="display: none; position: fixed; inset: 0; z-index: 999999;" x-on:keydown.escape.window="showOperacionModal = false">
                    <div x-show="showOperacionModal" x-transition.opacity x-on:click="showOperacionModal = false" style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"></div>
                    <div x-show="showOperacionModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="transform translate-y-0" x-transition:leave-end="transform translate-y-full" style="position: fixed; bottom: calc(64px + env(safe-area-inset-bottom)); left: 12px; right: 12px; background: var(--bg-card, #0F172A); border: 1px solid var(--border, rgba(255,255,255,0.12)); border-radius: 18px; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; box-shadow: 0 20px 40px rgba(0,0,0,0.6); z-index: 1000000;">
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-muted, #94A3B8); letter-spacing: 0.05em; margin-bottom: 0.25rem;">ACCESOS OPERATIVOS</div>
                        <a href="{{ url('/cocina') }}" wire:navigate @click="showOperacionModal = false" class="card" style="padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--text-main, #F8FAFC); font-weight: 700;">
                            <x-ui.icon name="chef" class="w-5 h-5 text-amber-400" />
                            <span>Cocina KDS</span>
                        </a>
                        <a href="{{ url('/reparto') }}" wire:navigate @click="showOperacionModal = false" class="card" style="padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--text-main, #F8FAFC); font-weight: 700;">
                            <x-ui.icon name="truck" class="w-5 h-5 text-emerald-400" />
                            <span>Panel de Reparto</span>
                        </a>
                    </div>
                </div>
            </template>
        </nav>

        {{-- Toast Container for Operative Notifications --}}
        <div id="toastNotificationContainer" class="toast-container"></div>
    @else
        <main class="content-area page-fade-in">
            {{ $slot }}
        </main>
    @endif

    @livewireScripts
    
    <script>
        @auth
            window.PedidosUser = {
                id: {{ auth()->id() }},
                roles: @json(auth()->user()->roles->pluck('slug')->toArray())
            };
            window.currentUser = window.PedidosUser;
        @else
            window.PedidosUser = { id: null, roles: [] };
            window.currentUser = null;
        @endauth

        @php
            $bSettings = app(\App\Services\BusinessSettingsService::class)->getSettings();
        @endphp
        window.PedidosSoundSettings = {
            soundEnabled: {{ $bSettings->notification_sound_enabled ? 'true' : 'false' }},
            volume: {{ (float) ($bSettings->notification_volume / 100) }},
            kitchenEnabled: {{ $bSettings->kitchen_sound_enabled ? 'true' : 'false' }},
            deliveryEnabled: {{ $bSettings->delivery_sound_enabled ? 'true' : 'false' }}
        };

        window.toggleTheme = function () {
            const current = localStorage.getItem('theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', next);
            if (window.applySavedTheme) window.applySavedTheme();
        };

        ['DOMContentLoaded', 'livewire:navigated', 'pageshow'].forEach(function(evt) {
            document.addEventListener(evt, function() {
                if (window.applySavedTheme) window.applySavedTheme();
            });
        });

        document.addEventListener('livewire:init', () => {
            const bar = document.getElementById('globalLoadingBar');
            let timer = null;

            if (window.Livewire) {
                window.Livewire.hook('commit', ({ respond, succeed, fail }) => {
                    if (bar) {
                        bar.style.width = '35%';
                        bar.classList.add('active');
                        if (timer) clearInterval(timer);
                        timer = setInterval(() => {
                            let current = parseFloat(bar.style.width) || 35;
                            if (current < 85) {
                                bar.style.width = (current + 8) + '%';
                            }
                        }, 80);
                    }

                    succeed(() => {
                        if (bar) {
                            if (timer) clearInterval(timer);
                            bar.style.width = '100%';
                            setTimeout(() => {
                                bar.classList.remove('active');
                                setTimeout(() => { bar.style.width = '0%'; }, 250);
                            }, 120);
                        }
                    });

                    fail(() => {
                        if (bar) {
                            if (timer) clearInterval(timer);
                            bar.style.width = '100%';
                            bar.style.background = '#EF5350';
                            setTimeout(() => {
                                bar.classList.remove('active');
                                setTimeout(() => {
                                    bar.style.width = '0%';
                                    bar.style.background = '';
                                }, 250);
                            }, 200);
                        }
                    });
                });
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.documentElement.classList.remove('sidebar-collapsed-preload');
                document.body.classList.remove('no-transitions');
            }, 50);

            @if(session()->has('success'))
                window.showOperationalToast({ type: 'success', title: 'Éxito', message: @json(session('success')) });
            @endif
            @if(session()->has('error'))
                window.showOperationalToast({ type: 'error', title: 'Error', message: @json(session('error')) });
            @endif
            @if(session()->has('warning'))
                window.showOperationalToast({ type: 'Atención', message: @json(session('warning')) });
            @endif
            @if(session()->has('info'))
                window.showOperationalToast({ type: 'info', title: 'Información', message: @json(session('info')) });
            @endif
        });
    </script>
</body>
</html>
