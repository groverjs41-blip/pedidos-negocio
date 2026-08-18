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

        {{-- PWA Install Option Card --}}
        <div id="pwaInstallCard" class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.85rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="header-icon-wrap violet" style="width: 40px; height: 40px; border-radius: 10px; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">
                        📱
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Instalar Aplicación</div>
                        <div id="pwaCardSubtitle" style="font-size: 0.775rem; color: var(--text-muted);">
                            Acceso rápido desde pantalla de inicio
                        </div>
                    </div>
                </div>

                <div id="pwaActionWrap">
                    <button type="button" id="pwaInstallBtn" onclick="triggerPwaInstall()" class="chip-btn active" style="padding: 0.5rem 0.9rem; font-weight: 800; font-size: 0.8rem; background: var(--primary); color: var(--primary-text); border: none; cursor: pointer;">
                        📱 INSTALAR
                    </button>
                </div>
            </div>

            <div id="pwaCardNotice" style="display: none; font-size: 0.775rem; color: var(--text-muted); font-style: italic; background: var(--bg-surface); padding: 0.55rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                Usa la aplicación unos segundos y vuelve a intentarlo.
            </div>

            @if(config('app.debug'))
                <div id="pwaDebugInfo" style="font-size: 0.725rem; font-family: monospace; color: var(--text-muted); background: var(--bg-surface); padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border); margin-top: 0.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
                    <div style="font-weight: 700; color: var(--warning);">🛠 PWA Diagnóstico (Debug)</div>
                    <div>Secure Context (HTTPS/localhost): <span id="debugSecure" style="font-weight:700;">-</span></div>
                    <div>Service Worker: <span id="debugSw" style="font-weight:700;">-</span></div>
                    <div>Standalone: <span id="debugStandalone" style="font-weight:700;">-</span></div>
                    <div>Install Prompt: <span id="debugPrompt" style="font-weight:700;">-</span></div>
                    <div>Online: <span id="debugOnline" style="font-weight:700;">-</span></div>
                </div>
            @endif
        </div>

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

    {{-- iOS Safari PWA Instruction Modal --}}
    <div id="iosPwaModal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 1.25rem;">
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); max-width: 400px; width: 100%; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; box-shadow: var(--shadow-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📱 INSTALAR PEDIDOS NEGOCIO</span>
                </h3>
                <button type="button" onclick="closeIosPwaModal()" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.9rem; color: var(--text-main);">
                <div style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <span style="font-size: 1.3rem;">1️⃣</span>
                    <span>Toca el botón <strong>Compartir</strong> <span style="font-size: 1.1rem;">⎋</span> en Safari.</span>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <span style="font-size: 1.3rem;">2️⃣</span>
                    <span>Selecciona <strong>"Añadir a pantalla de inicio"</strong> <span style="font-size: 1.1rem;">➕</span>.</span>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <span style="font-size: 1.3rem;">3️⃣</span>
                    <span>Confirma tocando <strong>"Añadir"</strong> arriba a la derecha.</span>
                </div>
            </div>

            <button type="button" onclick="closeIosPwaModal()" class="btn-primary" style="width: 100%; height: 44px; font-weight: 800; border-radius: var(--radius-md);">
                ENTENDIDO
            </button>
        </div>
    </div>

    <script>
        function renderPwaInstallState() {
            const card = document.getElementById('pwaInstallCard');
            const subtitle = document.getElementById('pwaCardSubtitle');
            const actionWrap = document.getElementById('pwaActionWrap');
            const notice = document.getElementById('pwaCardNotice');
            if (!card) return;

            const state = window.detectPwaState ? window.detectPwaState() : 'WAITING';

            // 1. INSTALLED: Hide card completely
            if (state === 'INSTALLED') {
                card.style.display = 'none';
                return;
            }

            card.style.display = 'flex';
            if (notice) notice.style.display = 'none';

            if (state === 'READY') {
                if (subtitle) subtitle.textContent = 'La aplicación está lista para instalarse.';
                if (actionWrap) {
                    actionWrap.innerHTML = `
                        <button type="button" onclick="triggerPwaInstall()" class="chip-btn active" style="padding: 0.5rem 0.9rem; font-weight: 800; font-size: 0.8rem; background: var(--primary); color: var(--primary-text); border: none; cursor: pointer;">
                            📱 INSTALAR
                        </button>
                    `;
                }
            } else if (state === 'WAITING') {
                if (subtitle) subtitle.textContent = 'Preparando instalación...';
                if (notice) notice.style.display = 'block';
                if (actionWrap) {
                    actionWrap.innerHTML = `
                        <button type="button" disabled class="chip-btn" style="padding: 0.5rem 0.9rem; font-weight: 700; font-size: 0.775rem; background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border); opacity: 0.7; cursor: not-allowed;">
                            INSTALACIÓN AÚN NO DISPONIBLE
                        </button>
                    `;
                }
            } else if (state === 'IOS') {
                if (subtitle) subtitle.textContent = 'Instalación en pantalla de inicio de iOS.';
                if (actionWrap) {
                    actionWrap.innerHTML = `
                        <button type="button" onclick="triggerPwaInstall()" class="chip-btn active" style="padding: 0.5rem 0.9rem; font-weight: 800; font-size: 0.8rem; background: var(--primary); color: var(--primary-text); border: none; cursor: pointer;">
                            📱 VER CÓMO INSTALAR
                        </button>
                    `;
                }
            } else if (state === 'UNSUPPORTED') {
                if (subtitle) subtitle.textContent = 'Instalación no disponible automáticamente en este navegador.';
                if (actionWrap) {
                    actionWrap.innerHTML = `
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">No compatible</span>
                    `;
                }
            }

            // Update debug diagnostic box if present
            const debugSecure = document.getElementById('debugSecure');
            const debugSw = document.getElementById('debugSw');
            const debugStandalone = document.getElementById('debugStandalone');
            const debugPrompt = document.getElementById('debugPrompt');
            const debugOnline = document.getElementById('debugOnline');

            if (debugSecure) debugSecure.textContent = window.isSecureContext ? 'Sí' : 'No';
            if (debugSw) debugSw.textContent = ('serviceWorker' in navigator) ? 'Sí' : 'No';
            if (debugStandalone) debugStandalone.textContent = (state === 'INSTALLED') ? 'Sí' : 'No';
            if (debugPrompt) debugPrompt.textContent = window.deferredPwaPrompt ? 'Disponible' : 'Esperando...';
            if (debugOnline) debugOnline.textContent = navigator.onLine ? 'Sí' : 'No';
        }

        function triggerPwaInstall() {
            const isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent);

            if (window.deferredPwaPrompt) {
                window.deferredPwaPrompt.prompt();
                window.deferredPwaPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        const card = document.getElementById('pwaInstallCard');
                        if (card) card.style.display = 'none';
                    }
                    window.deferredPwaPrompt = null;
                    renderPwaInstallState();
                });
            } else if (isIos) {
                const modal = document.getElementById('iosPwaModal');
                if (modal) modal.style.display = 'flex';
            }
        }

        function closeIosPwaModal() {
            const modal = document.getElementById('iosPwaModal');
            if (modal) modal.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', renderPwaInstallState);
        window.addEventListener('pwa-state-changed', renderPwaInstallState);
    </script>
</div>
