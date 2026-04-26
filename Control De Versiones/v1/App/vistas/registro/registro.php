<?php require RUTA_APP . '/vistas/inc/header.php'; ?>


<div class="modal" id="modal-auth" style="display:none;max-width:420px;position:relative">
    <div class="modal-close" onclick="closeModal({target:document.getElementById('modal-overlay')})">✕</div>
    
    <div id="auth-login-section">
        <div class="modal-title" style="text-align:center">Bienvenido de nuevo</div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-input" type="email" id="login-email" placeholder="tu@email.com">
        </div>
        <div class="form-group">
            <label class="form-label">Contraseña</label>
            <input class="form-input" type="password" id="login-pass" placeholder="••••••••">
        </div>
        <button class="btn-primary" style="width:100%;padding:12px" onclick="loginReal()">Iniciar sesión</button>
        <p style="text-align:center;font-size:12px;margin-top:16px">
            ¿No tienes cuenta? <span class="active-link" style="color:var(--accent2);cursor:pointer" onclick="toggleAuthMode('register')">Regístrate</span>
        </p>
    </div>

    <div id="auth-register-section" style="display:none">
        <div class="modal-title" style="text-align:center">Crea tu cuenta</div>
        <form id="form-registro">
            <div class="form-group">
                <label class="form-label">Nombre de usuario</label>
                <input class="form-input" name="nombre" type="text" placeholder="Ej: AlexCoder">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" name="email" type="email" placeholder="tu@email.com">
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input class="form-input" name="contraseña" type="password" placeholder="Mínimo 6 caracteres">
            </div>
        </form>
        <button class="btn-primary" style="width:100%;padding:12px" onclick="registrarReal()">Crear cuenta</button>
        <p style="text-align:center;font-size:12px;margin-top:16px">
            ¿Ya tienes cuenta? <span style="color:var(--accent2);cursor:pointer" onclick="toggleAuthMode('login')">Inicia sesión</span>
        </p>
    </div>
</div>

<script>
function toggleAuthMode(mode) {
    document.getElementById('auth-login-section').style.display = mode === 'login' ? 'block' : 'none';
    document.getElementById('auth-register-section').style.display = mode === 'register' ? 'block' : 'none';
}
</script>