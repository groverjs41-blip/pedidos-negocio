<x-layouts.app title="Inicio - Pedidos Negocio">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- Welcome Header --}}
        <div>
            <h1 class="dashboard-greeting">Hola, {{ $user->name }}</h1>
            <div class="dashboard-roles">
                @foreach($user->roles as $role)
                    <span class="role-badge">{{ $role->name }}</span>
                @endforeach
            </div>
        </div>

        {{-- Module Cards --}}
        <div class="dashboard-modules">
            @if($user->hasRole('pedidos') || $user->hasRole('admin'))
                <a href="{{ url('/pedidos/nuevo') }}" class="module-card">
                    <div class="module-icon amber">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    </div>
                    <span class="module-title">Nueva Orden</span>
                    <span class="module-description">Registrar y gestionar pedidos de clientes.</span>
                </a>

                <a href="{{ url('/pedidos') }}" class="module-card">
                    <div class="module-icon blue">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    <span class="module-title">Pedidos</span>
                    <span class="module-description">Ver el historial de pedidos del día.</span>
                </a>
            @endif

            @if($user->hasRole('cocina') || $user->hasRole('admin'))
                <a href="{{ url('/cocina') }}" class="module-card">
                    <div class="module-icon green">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle><line x1="5.4" y1="2" x2="18.6" y2="2" stroke-width="2"></line></svg>
                    </div>
                    <span class="module-title">Cocina</span>
                    <span class="module-description">Ver comandas y actualizar estado de preparación.</span>
                </a>
            @endif

            @if($user->hasRole('reparto') || $user->hasRole('admin'))
                <a href="{{ url('/reparto') }}" class="module-card">
                    <div class="module-icon violet">
                        <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    </div>
                    <span class="module-title">Reparto</span>
                    <span class="module-description">Monitorear entregas y despachos a domicilio.</span>
                </a>
            @endif

            @if($user->hasRole('caja') || $user->hasRole('admin'))
                <a href="{{ url('/caja') }}" class="module-card">
                    <div class="module-icon slate">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <span class="module-title">Cobranza</span>
                    <span class="module-description">Cerrar cuentas, procesar pagos y emitir recibos.</span>
                </a>
            @endif

            @if($user->hasRole('admin'))
                <a href="{{ url('/admin') }}" class="module-card" style="border-color: rgba(245, 158, 11, 0.2);">
                    <div class="module-icon amber">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    </div>
                    <span class="module-title" style="color: var(--primary-hover);">Administración</span>
                    <span class="module-description">Gestionar usuarios, roles y configuraciones.</span>
                </a>
            @endif
        </div>
    </div>
</x-layouts.app>
