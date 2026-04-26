<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $datos['titulo'] ?? NOMBRESITIO; ?></title>
    <link rel="stylesheet" href="<?php echo RUTA_PUBLIC; ?>/css/style.css">
</head>
<body>

<nav>
  <div class="logo">
    <div class="logo-icon mono">&gt;_</div>
    Prompt<span>Vault</span>
  </div>
  <div class="nav-links">
    <div class="nav-link active">Feed</div>
    <div class="nav-link">Explorar</div>
    <div class="nav-link">Colecciones</div>
    <div class="nav-link">Rankings</div>
  </div>
  <div class="nav-right">
    <div class="btn-ghost" onclick="openModal('auth')">Iniciar sesión</div>
    <div class="btn-primary" onclick="openModal('newprompt')">+ Nuevo Prompt</div>
    <div class="avatar" onclick="openModal('profile')" title="Ver perfil">AV</div>
  </div>
</nav>