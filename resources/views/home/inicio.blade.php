<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Pedidos Negocio</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #0f1e36 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --primary: #f59e0b; /* Amber */
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --card-hover: rgba(255, 255, 255, 0.04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .dashboard-container {
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .header-section {
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }

        .user-info h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .user-info p {
            font-size: 0.875rem;
            color: var(--text-muted);
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .role-badge {
            background: rgba(245, 158, 11, 0.15);
            color: var(--primary);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #ef4444;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.1);
            transform: translateY(-1px);
        }

        .modules-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 480px) {
            .modules-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .module-card {
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 100% 0%, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .module-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.3);
            background: var(--card-hover);
        }

        .module-icon {
            font-size: 2rem;
            line-height: 1;
        }

        .module-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .module-description {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .badge-upcoming {
            background: rgba(148, 163, 184, 0.1);
            color: var(--text-muted);
            border: 1px solid rgba(148, 163, 184, 0.2);
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            align-self: flex-start;
            font-weight: 500;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header-section">
            <div class="user-info">
                <h1>Hola, {{ $user->name }}</h1>
                <p>
                    Roles:
                    @foreach($user->roles as $role)
                        <span class="role-badge">{{ $role->name }}</span>
                    @endforeach
                </p>
            </div>
            
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Cerrar Sesión</button>
            </form>
        </div>

        <div class="modules-section">
            {{-- Pedidos Module --}}
            @if($user->hasRole('pedidos') || $user->hasRole('admin'))
                <a href="{{ url('/pedidos/nuevo') }}" class="module-card">
                    <span class="module-icon">📝</span>
                    <span class="module-title">Nueva Orden</span>
                    <span class="module-description">Registrar y gestionar pedidos de clientes.</span>
                    <span class="badge-upcoming">Próximamente</span>
                </a>
            @endif

            {{-- Cocina Module --}}
            @if($user->hasRole('cocina') || $user->hasRole('admin'))
                <a href="{{ url('/cocina') }}" class="module-card">
                    <span class="module-icon">🍳</span>
                    <span class="module-title">Cocina</span>
                    <span class="module-description">Ver comandas y actualizar estado de preparación.</span>
                    <span class="badge-upcoming">Próximamente</span>
                </a>
            @endif

            {{-- Reparto Module --}}
            @if($user->hasRole('reparto') || $user->hasRole('admin'))
                <a href="{{ url('/reparto') }}" class="module-card">
                    <span class="module-icon">🛵</span>
                    <span class="module-title">Reparto</span>
                    <span class="module-description">Monitorear entregas y despachos a domicilio.</span>
                    <span class="badge-upcoming">Próximamente</span>
                </a>
            @endif

            {{-- Caja Module --}}
            @if($user->hasRole('caja') || $user->hasRole('admin'))
                <a href="{{ url('/caja') }}" class="module-card">
                    <span class="module-icon">💵</span>
                    <span class="module-title">Cobranza</span>
                    <span class="module-description">Cerrar cuentas, procesar pagos y emitir recibos.</span>
                    <span class="badge-upcoming">Próximamente</span>
                </a>
            @endif

            {{-- Admin Module --}}
            @if($user->hasRole('admin'))
                <a href="{{ url('/admin') }}" class="module-card" style="border-color: rgba(245, 158, 11, 0.25);">
                    <span class="module-icon">⚙️</span>
                    <span class="module-title" style="color: var(--primary);">Administración</span>
                    <span class="module-description">Gestionar usuarios, roles y configuraciones.</span>
                </a>
            @endif
        </div>
    </div>
</body>
</html>
