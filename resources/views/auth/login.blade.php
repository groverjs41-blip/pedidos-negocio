<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Pedidos Negocio</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-header-logo">
                <span>🍔</span>
                <span>PEDIDOS NEGOCIO</span>
            </div>
            <p class="login-subtitle">Gestión rápida de pedidos y entregas</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 0;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" id="loginForm" style="display: flex; flex-direction: column; gap: 1.25rem;">
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
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); cursor: pointer; user-select: none;">
                    <input type="checkbox" name="remember" id="remember" style="accent-color: var(--primary); cursor: pointer;" {{ old('remember') ? 'checked' : '' }}>
                    <span>Recordarme</span>
                </label>
            </div>

            <button type="submit" class="btn-login-submit" id="submitBtn">INICIAR SESIÓN</button>
        </form>
    </div>

    <script>
        // Password visibility toggle
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

        // Double submit prevention
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');

        loginForm.addEventListener('submit', function(e) {
            // Check HTML5 browser validation
            if (!loginForm.checkValidity()) {
                return;
            }
            
            // To prevent double click:
            if (submitBtn.classList.contains('loading')) {
                e.preventDefault();
                return;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Some browsers require a tick before disabling to let the submit action propagate
            setTimeout(() => {
                submitBtn.disabled = true;
            }, 1);
            
            submitBtn.innerHTML = '<span class="spinner"></span> INICIANDO SESIÓN...';
        });
    </script>
</body>
</html>
