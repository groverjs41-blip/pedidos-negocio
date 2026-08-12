<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Pedidos Negocio</title>
    <meta name="description" content="Accede a Pedidos Negocio para gestionar pedidos, cocina y repartos en tiempo real.">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="login-wrapper">
    {{-- LEFT PANEL: Brand & Features (Desktop 50/50) --}}
    <div class="login-brand-panel">
        <div class="login-brand-logo">
            <div class="login-brand-icon">
                <x-ui.icon name="bag" class="w-6 h-6" />
            </div>
            <span class="login-brand-name">PEDIDOS <span>NEGOCIO</span></span>
        </div>

        <h1 class="login-brand-tagline">
            Gestiona tus pedidos<br>
            <span>sin perder el ritmo.</span>
        </h1>

        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem; max-width: 420px; line-height: 1.5;">
            Pedidos, cocina y reparto conectados en tiempo real para acelerar la operación de tu negocio.
        </p>

        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-check">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="login-feature-text">Pedidos en tiempo real sin recargar</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-check">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="login-feature-text">Cocina organizada con control de demoras</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-check">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="login-feature-text">Entregas y despachos controlados</span>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL: Login Form --}}
    <div class="login-form-panel">
        <div class="login-card">
            <div class="login-header">
                <h2 class="login-header-title">Bienvenido de nuevo</h2>
                <p class="login-subtitle">Ingresa para comenzar tu jornada.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 0;">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" id="loginForm" style="display: flex; flex-direction: column; gap: 1.25rem;">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="correo@ejemplo.com"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                    >
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                    <label for="password" class="form-label">Contraseña</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" id="togglePasswordBtn" style="position: absolute; right: 14px; background: transparent; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.8rem; font-weight: 600; padding: 4px;">Mostrar</button>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.825rem; color: var(--text-muted); cursor: pointer; user-select: none;">
                        <input type="checkbox" name="remember" id="remember" style="accent-color: var(--primary); cursor: pointer;" {{ old('remember') ? 'checked' : '' }}>
                        <span>Recordarme</span>
                    </label>
                </div>

                <button type="submit" class="btn-login-submit" id="submitBtn">INICIAR SESIÓN</button>
            </form>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');

        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = 'Ocultar';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = 'Mostrar';
            }
        });

        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');

        loginForm.addEventListener('submit', function(e) {
            if (!loginForm.checkValidity()) return;
            if (submitBtn.classList.contains('loading')) {
                e.preventDefault();
                return;
            }
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            setTimeout(() => { submitBtn.disabled = true; }, 1);
            submitBtn.innerHTML = '<span class="spinner"></span> INICIANDO SESIÓN...';
        });
    </script>
</body>
</html>
