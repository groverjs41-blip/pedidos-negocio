<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pedidos Negocio' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #0f1e36 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --primary: #f59e0b; /* Amber */
            --primary-hover: #d97706;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --card-hover: rgba(255, 255, 255, 0.04);
            --danger: #ef4444;
            --success: #10b981;
            --info: #3b82f6;
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
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
        }

        .nav-brand-logo {
            font-size: 1.5rem;
        }

        .nav-brand-text {
            font-weight: 700;
            font-size: 1.2rem;
            background: linear-gradient(to right, #f59e0b, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: var(--text-main);
        }

        .role-badge {
            background: rgba(245, 158, 11, 0.15);
            color: var(--primary);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #ef4444;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-family: inherit;
            font-weight: 500;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        /* Glassmorphic layout parts */
        .glass-panel {
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
        }

        /* Offline Warning styles */
        .offline-banner {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Notification Toast Alert */
        .toast-alert {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #1e293b;
            border-left: 4px solid var(--primary);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .toast-title {
            font-weight: 600;
            color: var(--text-main);
        }

        .toast-body {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar">
        <a href="{{ url('/inicio') }}" class="nav-brand">
            <span class="nav-brand-logo">🍔</span>
            <span class="nav-brand-text">Pedidos Negocio</span>
        </a>
        <div class="nav-actions">
            @auth
                <a href="{{ url('/pedidos') }}" class="nav-link">Pedidos</a>
                <span class="role-badge">{{ auth()->user()->roles->first()?->name ?? 'Usuario' }}</span>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout">Salir</button>
                </form>
            @endauth
        </div>
    </nav>

    <div class="container">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
