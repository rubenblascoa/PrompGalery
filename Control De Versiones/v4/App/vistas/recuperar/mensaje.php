<?php require_once RUTA_APP . '/vistas/inc/header.php'; ?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:40px 16px">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:48px 40px;max-width:480px;width:100%;text-align:center">
    <?php if (($datos['tipo'] ?? '') === 'exito'): ?>
      <div style="font-size:56px;margin-bottom:16px">✅</div>
      <h1 style="font-size:22px;margin:0 0 12px;color:var(--text1)"><?php echo htmlspecialchars($datos['titulo']); ?></h1>
      <p style="color:var(--text2);margin:0 0 28px;line-height:1.6"><?php echo htmlspecialchars($datos['mensaje']); ?></p>
      <a href="<?php echo RUTA_URL; ?>" class="btn-primary" style="text-decoration:none;display:inline-block;padding:12px 28px">Ir al inicio</a>
    <?php else: ?>
      <div style="font-size:56px;margin-bottom:16px">❌</div>
      <h1 style="font-size:22px;margin:0 0 12px;color:var(--text1)"><?php echo htmlspecialchars($datos['titulo']); ?></h1>
      <p style="color:var(--text2);margin:0 0 28px;line-height:1.6"><?php echo htmlspecialchars($datos['mensaje']); ?></p>
      <a href="<?php echo RUTA_URL; ?>recuperar" class="btn-primary" style="text-decoration:none;display:inline-block;padding:12px 28px">Solicitar nuevo enlace</a>
    <?php endif; ?>
  </div>
</div>
<?php require_once RUTA_APP . '/vistas/inc/footer.php'; ?>
