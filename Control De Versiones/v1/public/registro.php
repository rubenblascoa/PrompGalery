<?php
// Vista de registro
?>
 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - PromptVault</title>
    <link rel="stylesheet" href="<?php echo RUTA_PUBLIC; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo RUTA_PUBLIC; ?>css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="logo-icon">◆</div>
                <h1>PromptVault</h1>
            </div>
            <h2>Crea tu Cuenta</h2>
            <p class="auth-subtitle">Únete a la comunidad de prompts</p>
 
            <?php if (isset($datos['error'])): ?>
                <div class="alert alert-error">
                    <?php echo escapar($datos['error']); ?>
                </div>
            <?php endif; ?>
 
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario</label>
                    <input class="form-input" type="text" name="nombre" required placeholder="Tu nombre">
                </div>
 
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input class="form-input" type="email" name="email" required placeholder="tu@email.com">
                </div>
 
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input class="form-input" type="password" name="contraseña" required placeholder="••••••••" minlength="6">
                </div>
 
                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input class="form-input" type="password" name="contraseña_conf" required placeholder="••••••••" minlength="6">
                </div>
 
                <button type="submit" class="btn-primary" style="width:100%; padding:12px; font-size:14px;">
                    Crear Cuenta
                </button>
            </form>
 
            <div style="text-align:center; margin-top:20px;">
                <p style="color:var(--text3); font-size:13px;">
                    ¿Ya tienes cuenta? 
                    <a href="<?php echo RUTA_URL; ?>usuarios/login" style="color:var(--accent); text-decoration:none;">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>
 
    <script src="<?php echo RUTA_PUBLIC; ?>js/main.js"></script>
</body>
</html>
 