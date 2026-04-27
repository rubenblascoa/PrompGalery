<?php require_once RUTA_APP . '/vistas/inc/header.php'; ?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:40px 16px">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:48px 40px;max-width:440px;width:100%">
    <div style="text-align:center;margin-bottom:32px">
      <div style="font-size:40px">🔐</div>
      <h1 style="font-size:22px;margin:12px 0 8px;color:var(--text1)">Nueva contraseña</h1>
      <p style="color:var(--text2);font-size:14px;margin:0">Elige una contraseña segura de al menos 8 caracteres.</p>
    </div>
    <div id="reset-msg" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px"></div>
    <div style="margin-bottom:16px">
      <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px">Nueva contraseña</label>
      <input type="password" id="nueva-pass" class="form-input" placeholder="Mínimo 8 caracteres" style="width:100%;box-sizing:border-box">
    </div>
    <div style="margin-bottom:20px">
      <label style="display:block;font-size:13px;color:var(--text2);margin-bottom:6px">Confirmar contraseña</label>
      <input type="password" id="confirmar-pass" class="form-input" placeholder="Repite la contraseña" style="width:100%;box-sizing:border-box">
    </div>
    <button class="btn-primary" style="width:100%;padding:12px" onclick="guardarNuevaPass()">Guardar contraseña</button>
  </div>
</div>
<script>
const RESET_TOKEN = '<?php echo htmlspecialchars($datos['token'], ENT_QUOTES); ?>';
async function guardarNuevaPass() {
  const nueva     = document.getElementById('nueva-pass').value;
  const confirmar = document.getElementById('confirmar-pass').value;
  const msg       = document.getElementById('reset-msg');
  if (nueva.length < 8) { showMsg('Mínimo 8 caracteres', 'error'); return; }
  if (nueva !== confirmar) { showMsg('Las contraseñas no coinciden', 'error'); return; }
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const fd   = new FormData();
  fd.append('csrf_token', csrf);
  fd.append('nueva_pass', nueva);
  fd.append('confirmar_pass', confirmar);
  try {
    const r = await fetch(RUTA_URL + 'recuperar/nueva/' + RESET_TOKEN, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) { showMsg(d.mensaje + ' Redirigiendo...', 'success'); setTimeout(() => window.location.href = RUTA_URL, 2000); }
    else       showMsg(d.error || 'Error', 'error');
  } catch { showMsg('Error de conexión', 'error'); }
  function showMsg(t, tipo) {
    msg.textContent = t; msg.style.display = 'block';
    msg.style.background = tipo === 'success' ? 'rgba(61,220,132,.15)' : 'rgba(255,107,107,.15)';
    msg.style.color = tipo === 'success' ? '#3DDC84' : '#FF6B6B';
  }
}
</script>
<?php require_once RUTA_APP . '/vistas/inc/footer.php'; ?>
