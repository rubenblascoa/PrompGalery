<?php require_once RUTA_APP . '/vistas/inc/header.php'; ?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:40px 16px">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:48px 40px;max-width:440px;width:100%">
    <div style="text-align:center;margin-bottom:32px">
      <div style="font-size:40px">🔑</div>
      <h1 style="font-size:22px;margin:12px 0 8px;color:var(--text1)">Recuperar contraseña</h1>
      <p style="color:var(--text2);font-size:14px;margin:0">Introduce tu email y te enviaremos un enlace para restablecer tu contraseña.</p>
    </div>
    <div id="recuperar-msg" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px"></div>
    <div style="margin-bottom:16px">
      <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px">Email</label>
      <input type="email" id="recuperar-email" class="form-input" placeholder="tu@email.com" style="width:100%;box-sizing:border-box">
    </div>
    <button class="btn-primary" style="width:100%;padding:12px" onclick="enviarRecuperar()">Enviar enlace</button>
    <div style="text-align:center;margin-top:20px">
      <a href="<?php echo RUTA_URL; ?>" style="color:var(--accent);text-decoration:none;font-size:14px">← Volver al inicio</a>
    </div>
  </div>
</div>
<script>
async function enviarRecuperar() {
  const email = document.getElementById('recuperar-email').value.trim();
  const msg   = document.getElementById('recuperar-msg');
  if (!email) { showMsg('Introduce tu email', 'error'); return; }
  const csrf  = document.querySelector('meta[name="csrf-token"]').content;
  const fd    = new FormData();
  fd.append('csrf_token', csrf);
  fd.append('email', email);
  try {
    const r = await fetch(RUTA_URL + 'recuperar/enviar', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) showMsg(d.mensaje, 'success');
    else      showMsg(d.error || 'Error', 'error');
  } catch { showMsg('Error de conexión', 'error'); }
  function showMsg(t, tipo) {
    msg.textContent = t;
    msg.style.display = 'block';
    msg.style.background = tipo === 'success' ? 'rgba(61,220,132,.15)' : 'rgba(255,107,107,.15)';
    msg.style.color = tipo === 'success' ? '#3DDC84' : '#FF6B6B';
  }
}
</script>
<?php require_once RUTA_APP . '/vistas/inc/footer.php'; ?>
