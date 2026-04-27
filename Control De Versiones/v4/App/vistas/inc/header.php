<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($datos['meta_desc'] ?? 'PromptVault - La comunidad de prompts de IA. Descubre, comparte y vota los mejores prompts para Claude, GPT-4 y más.', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Social -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="PromptVault">
    <meta property="og:title"       content="<?php echo htmlspecialchars($datos['titulo'] ?? NOMBRESITIO, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($datos['meta_desc'] ?? 'La comunidad de prompts de IA. Descubre, comparte y vota los mejores prompts.', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars(rtrim(SITE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/public/img/og-image.png">
    <meta property="og:url"         content="<?php echo htmlspecialchars(rtrim(SITE_URL, '/') . ($_SERVER['REQUEST_URI'] ?? '/'), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card"       content="summary_large_image">
    <meta name="twitter:title"      content="<?php echo htmlspecialchars($datos['titulo'] ?? NOMBRESITIO, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image"      content="<?php echo htmlspecialchars(rtrim(SITE_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/public/img/og-image.png">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo RUTA_PUBLIC; ?>img/favicon.svg">
    <link rel="alternate icon"            href="<?php echo RUTA_PUBLIC; ?>img/favicon.ico">
    <link rel="apple-touch-icon"          href="<?php echo RUTA_PUBLIC; ?>img/favicon-180.png">

    <title><?php echo htmlspecialchars($datos['titulo'] ?? NOMBRESITIO, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo RUTA_PUBLIC; ?>css/style.css?v=<?php echo VERSION; ?>">
    <meta name="csrf-token" content="<?php echo generarCSRF(); ?>">
    <script>const RUTA_URL = '<?php echo RUTA_URL; ?>';</script>
    <?php if (isset($datos['extra_head'])) echo $datos['extra_head']; ?>
</head>
<body>

<?php $u = $datos['usuario'] ?? null; ?>

<nav id="main-nav">
    <a href="<?php echo RUTA_URL; ?>" class="logo">
        <div class="logo-icon mono">&gt;_</div>
        Prompt<span>Vault</span>
    </a>

    <div class="nav-search-wrap">
        <span class="nav-search-icon">🔍</span>
        <input type="text" id="nav-search-input" class="nav-search-input"
               placeholder="Buscar prompts, tags, usuarios... (⌘K)"
               oninput="handleNavSearch(this.value)"
               autocomplete="off">
        <div id="nav-search-results" class="nav-search-results"></div>
    </div>

    <div class="nav-links">
        <button class="nav-link" id="nl-inicio"      onclick="goPage('inicio')">Feed</button>
        <a      class="nav-link" href="<?php echo RUTA_URL; ?>explorar">Explorar</a>
        <a      class="nav-link" href="<?php echo RUTA_URL; ?>colecciones">Colecciones</a>
        <button class="nav-link" id="nl-rankings"    onclick="goPage('rankings')">Rankings</button>
        <button class="nav-link" id="nl-tester"      onclick="goPage('tester')">⚡ Tester</button>
    </div>

    <div class="nav-right">
        <?php if ($u): ?>
            <!-- Verificación de email: banner si no verificado -->
            <?php if (!$u['verificado']): ?>
            <button class="btn-ghost" style="font-size:12px;padding:6px 12px;color:#FFB547;border-color:#FFB547"
                    onclick="reenviarVerificacion()" title="Tu email no está verificado">⚠️ Verificar email</button>
            <?php endif; ?>
            <button class="icon-btn" id="notif-btn" onclick="toggleNotifications()" title="Notificaciones">
                🔔
                <span class="notif-badge" id="notif-count" style="display:none">0</span>
            </button>
            <button class="btn-primary" onclick="openModal('newprompt')">+ Nuevo Prompt</button>
            <div class="nav-avatar-wrap" onclick="toggleUserMenu()">
                <?php if ($u['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($u['avatar'], ENT_QUOTES, 'UTF-8'); ?>"
                         class="nav-avatar" alt="<?php echo htmlspecialchars($u['nombre']); ?>">
                <?php else: ?>
                    <div class="nav-avatar" style="background:var(--accent)">
                        <?php echo strtoupper(substr($u['nombre'], 0, 2)); ?>
                    </div>
                <?php endif; ?>
                <div class="user-menu" id="user-menu">
                    <div class="user-menu-header">
                        <div class="um-name"><?php echo htmlspecialchars($u['nombre']); ?></div>
                        <?php if ($u['verificado']): ?><span class="verified-icon">✓</span><?php endif; ?>
                    </div>
                    <div class="user-menu-item" onclick="goPage('miperfil')">👤 Mi perfil</div>
                    <div class="user-menu-item" onclick="goPage('guardados')">🔖 Guardados</div>
                    <a href="<?php echo RUTA_URL; ?>colecciones" class="user-menu-item">📁 Colecciones</a>
                    <div class="user-menu-item" onclick="openModal('editperfil')">⚙️ Configuración</div>
                    <div class="user-menu-divider"></div>
                    <a href="<?php echo RUTA_URL; ?>auth/logout" class="user-menu-item danger">🚪 Cerrar sesión</a>
                </div>
            </div>
        <?php else: ?>
            <button class="btn-ghost" onclick="openModal('auth')">Iniciar sesión</button>
            <button class="btn-primary" onclick="openModal('newprompt')">+ Nuevo Prompt</button>
        <?php endif; ?>
    </div>

    <button class="hamburger" onclick="toggleMobileMenu()" id="hamburger">☰</button>
</nav>

<!-- Notificaciones -->
<div class="notif-panel" id="notif-panel">
    <div class="notif-header-row">
        <div class="notif-header-title">Notificaciones</div>
        <div class="notif-mark-all" onclick="markAllRead()">Marcar todo como leído</div>
    </div>
    <div id="notif-list-items">
        <div style="text-align:center;padding:24px;color:var(--text3);font-size:13px">
            Abre las notificaciones para cargarlas
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>

<?php if ($u): ?>
<script>
// Cargar conteo real de notificaciones al inicio
(function loadNotifCount() {
    fetch(RUTA_URL + 'perfil/notif_count', { credentials: 'same-origin' })
        .then(r => r.ok ? r.json() : null)
        .then(d => {
            if (!d) return;
            const badge = document.getElementById('notif-count');
            if (!badge) return;
            const count = parseInt(d.count) || 0;
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(() => {});
})();

async function reenviarVerificacion() {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const fd   = new FormData();
    fd.append('csrf_token', csrf);
    try {
        const r = await fetch(RUTA_URL + 'auth/reenviar_verificacion', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) showToast(d.mensaje, 'success');
        else      showToast(d.error || 'Error', 'error');
    } catch { showToast('Error de conexión', 'error'); }
}
</script>
<?php endif; ?>
