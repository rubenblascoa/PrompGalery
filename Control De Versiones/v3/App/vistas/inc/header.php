<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PromptVault - La comunidad de prompts de IA. Descubre, comparte y vota los mejores prompts para Claude, GPT-4 y más.">
    <meta name="robots" content="index, follow">
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
        <button class="nav-link" id="nl-inicio" onclick="goPage('inicio')">Feed</button>
        <button class="nav-link" id="nl-explorar" onclick="goPage('explorar')">Explorar</button>
        <button class="nav-link" id="nl-colecciones" onclick="goPage('colecciones')">Colecciones</button>
        <button class="nav-link" id="nl-rankings" onclick="goPage('rankings')">Rankings</button>
        <button class="nav-link" id="nl-tester" onclick="goPage('tester')">⚡ Tester</button>
    </div>

    <div class="nav-right">
        <?php if ($u): ?>
            <button class="icon-btn" id="notif-btn" onclick="toggleNotifications()" title="Notificaciones">
                🔔
                <span class="notif-badge" id="notif-count">3</span>
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
