<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Pedidos Negocio</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #0c1626 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --primary: #f59e0b; /* Amber */
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
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
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .placeholder-card {
            width: 100%;
            max-width: 440px;
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.25rem;
        }

        .icon {
            font-size: 4rem;
            margin-bottom: 0.5rem;
            animation: pulse 2s infinite ease-in-out;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.08);
            }
        }

        .title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .description {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .badge-upcoming {
            background: rgba(245, 158, 11, 0.1);
            color: var(--primary);
            border: 1px solid rgba(245, 158, 11, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-back {
            margin-top: 1rem;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="placeholder-card">
        <span class="icon">🚧</span>
        <h1 class="title">{{ $title }}</h1>
        <span class="badge-upcoming">Próximamente</span>
        <p class="description">Estamos preparando este módulo. Estará disponible en la siguiente fase de desarrollo.</p>
        <a href="{{ url('/inicio') }}" class="btn-back">Volver al Inicio</a>
    </div>
</body>
</html>
