<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Pedidos Negocio</title>
    <meta name="description" content="Accede a Pedidos Negocio para gestionar pedidos, cocina y repartos de forma rápida y eficiente.">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="login-wrapper">
    {{-- LEFT PANEL: Brand / Marketing (Desktop Only) --}}
    <div class="login-brand-panel">
        <div class="login-brand-logo">
            <div class="login-brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
            </div>
            <span class="login-brand-name">Pedidos Negocio</span>
        </div>

        <h1 class="login-brand-tagline">
            Gestiona tu negocio<br>
            <span>de forma inteligente.</span>
        </h1>

        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-check">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="login-feature-text">Pedidos en tiempo real con cocina y reparto</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-check">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="login-feature-text">Interfaz optimizada para móvil y tablet</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-check">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="login-feature-text">Control de roles y acceso por usuario</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-check">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="login-feature-text">Panel administrativo completo con Filament</span>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL: Login Form --}}
    <div class="login-form-panel">
        <div class="login-card">
            {{-- Mobile-only brand header --}}
            <div class="login-mobile-brand">
                <div class="login-brand-icon" style="width: 36px; height: 36px; border-radius: 10px;">
                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#FFFFFF"/></svg>
                </div>
                <span style="font-weight: 700; font-size: 1.15rem; color: var(--text-main);">Pedidos Negocio</span>
            </div>

            <div class="login-header">
                <div class="login-header-logo">
                    <span>Iniciar <span class="highlight">Sesión</span></span>
                </div>
                <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 0;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" id="loginForm" style="display: flex; flex-direction: column; gap: 1.15rem;">
                @csrf
                <div class="login-form-group">
                    <label for="email" class="login-label">Correo electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="login-input"
                        placeholder="correo@ejemplo.com"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                    >
                </div>

                <div class="login-form-group">
                    <label for="password" class="login-label">Contraseña</label>
                    <div class="login-input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" id="togglePasswordBtn" class="password-toggle-btn">Mostrar</button>
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
