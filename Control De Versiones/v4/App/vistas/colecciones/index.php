<?php require RUTA_APP . '/vistas/inc/header.php'; ?>
<div class="layout" style="padding-top:24px">
  <aside class="sidebar">
    <div class="sidebar-section">Navegar</div>
    <div class="sidebar-item" onclick="window.location.href='<?php echo RUTA_URL; ?>'"><span class="icon">🏠</span> Inicio</div>
    <div class="sidebar-item" onclick="window.location.href='<?php echo RUTA_URL; ?>explorar'"><span class="icon">🔥</span> Explorar</div>
    <div class="sidebar-item active"><span class="icon">📁</span> Colecciones</div>
  </aside>

  <main class="main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
      <h1 style="font-size:22px;font-weight:700;margin:0;color:var(--text1)">📁 Mis Colecciones</h1>
      <?php if ($datos['usuario']): ?>
      <button class="btn-primary" onclick="abrirCrearColeccion()">+ Nueva colección</button>
      <?php endif; ?>
    </div>

    <?php if (!$datos['usuario']): ?>
    <div class="empty-state">
      <div class="empty-icon">🔒</div>
      <div class="empty-title">Inicia sesión para ver tus colecciones</div>
      <div class="empty-sub">Las colecciones te permiten organizar tus prompts favoritos</div>
    </div>
    <?php elseif (empty($datos['colecciones'])): ?>
    <div class="empty-state">
      <div class="empty-icon">📂</div>
      <div class="empty-title">Sin colecciones todavía</div>
      <div class="empty-sub">Crea tu primera colección para organizar tus prompts favoritos</div>
      <button class="btn-primary" style="margin-top:16px" onclick="abrirCrearColeccion()">+ Crear colección</button>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
      <?php foreach ($datos['colecciones'] as $col): ?>
      <div class="prompt-card" onclick="verColeccion('<?php echo htmlspecialchars($col->id); ?>')"
           style="cursor:pointer">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
          <div style="font-size:32px"><?php echo $col->privada ? '🔒' : '📂'; ?></div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;color:var(--text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?php echo htmlspecialchars($col->nombre); ?>
            </div>
            <div style="font-size:12px;color:var(--text3)"><?php echo $col->num_prompts; ?> prompts</div>
          </div>
          <button onclick="event.stopPropagation();eliminarColeccion('<?php echo htmlspecialchars($col->id); ?>','<?php echo htmlspecialchars($col->nombre); ?>')"
                  style="background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px;padding:4px" title="Eliminar">🗑️</button>
        </div>
        <?php if ($col->descripcion): ?>
        <p style="font-size:13px;color:var(--text2);margin:0"><?php echo htmlspecialchars(truncar($col->descripcion, 80)); ?></p>
        <?php endif; ?>
        <div style="margin-top:12px;font-size:11px;color:var(--text3)"><?php echo $col->privada ? '🔒 Privada' : '🌐 Pública'; ?> · <?php echo timeAgo($col->fecha_creacion); ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<!-- Modal crear colección -->
<div id="modal-crear-col" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;padding:16px">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:32px;max-width:440px;width:100%">
    <h2 style="margin:0 0 20px;font-size:18px;color:var(--text1)">Nueva Colección</h2>
    <div style="margin-bottom:14px">
      <label style="font-size:13px;color:var(--text2);display:block;margin-bottom:6px">Nombre *</label>
      <input id="col-nombre" type="text" class="form-input" placeholder="Mi colección" maxlength="100" style="width:100%;box-sizing:border-box">
    </div>
    <div style="margin-bottom:14px">
      <label style="font-size:13px;color:var(--text2);display:block;margin-bottom:6px">Descripción</label>
      <textarea id="col-desc" class="form-input" placeholder="Descripción opcional..." maxlength="300" rows="3" style="width:100%;box-sizing:border-box;resize:none"></textarea>
    </div>
    <label style="display:flex;align-items:center;gap:8px;margin-bottom:20px;cursor:pointer;font-size:14px;color:var(--text2)">
      <input type="checkbox" id="col-privada"> Colección privada
    </label>
    <div style="display:flex;gap:10px">
      <button class="btn-ghost" style="flex:1" onclick="cerrarCrearColeccion()">Cancelar</button>
      <button class="btn-primary" style="flex:1" onclick="guardarColeccion()">Crear</button>
    </div>
  </div>
</div>

<!-- Modal ver colección -->
<div id="modal-ver-col" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.6);align-items:flex-start;justify-content:center;padding:24px;overflow-y:auto">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:32px;max-width:680px;width:100%">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2 id="ver-col-titulo" style="margin:0;font-size:18px;color:var(--text1)">Colección</h2>
      <button onclick="cerrarVerColeccion()" style="background:none;border:none;color:var(--text2);cursor:pointer;font-size:20px">✕</button>
    </div>
    <div id="ver-col-content" style="min-height:100px;display:flex;align-items:center;justify-content:center;color:var(--text3)">Cargando...</div>
  </div>
</div>

<script>
window.PV_USER   = <?php echo json_encode($datos['usuario']); ?>;
window.PV_CSRF   = document.querySelector('meta[name="csrf-token"]')?.content;
window.PV_PERFIL = null;

function abrirCrearColeccion() {
  const m = document.getElementById('modal-crear-col');
  m.style.display = 'flex';
}
function cerrarCrearColeccion() {
  document.getElementById('modal-crear-col').style.display = 'none';
}

async function guardarColeccion() {
  const nombre = document.getElementById('col-nombre').value.trim();
  if (!nombre) { showToast('El nombre es obligatorio', 'error'); return; }
  const fd = new FormData();
  fd.append('csrf_token', window.PV_CSRF);
  fd.append('nombre', nombre);
  fd.append('descripcion', document.getElementById('col-desc').value);
  if (document.getElementById('col-privada').checked) fd.append('privada', '1');
  try {
    const r = await fetch(RUTA_URL + 'colecciones/crear', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) { showToast(d.mensaje, 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(d.error || 'Error', 'error');
  } catch { showToast('Error de conexión', 'error'); }
}

async function verColeccion(id) {
  const m = document.getElementById('modal-ver-col');
  const content = document.getElementById('ver-col-content');
  content.innerHTML = '<div style="text-align:center;padding:32px;color:var(--text3)">Cargando...</div>';
  m.style.display = 'flex';
  try {
    const r = await fetch(RUTA_URL + 'colecciones/ver/' + id);
    const d = await r.json();
    if (d.coleccion) {
      document.getElementById('ver-col-titulo').textContent = d.coleccion.nombre;
      if (!d.prompts || !d.prompts.length) {
        content.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)">Esta colección está vacía</div>';
      } else {
        content.innerHTML = '<div style="display:flex;flex-direction:column;gap:10px">' +
          d.prompts.map(p => `<div class="prompt-card" style="margin:0">
            <div style="font-weight:600;color:var(--text1);margin-bottom:6px">${escHtml(p.titulo)}</div>
            <div style="font-size:13px;color:var(--text2)">${escHtml((p.contenido||'').substring(0,120))}...</div>
          </div>`).join('') + '</div>';
      }
    } else content.innerHTML = '<div style="color:#FF6B6B;text-align:center;padding:32px">' + (d.error || 'Error') + '</div>';
  } catch { content.innerHTML = '<div style="color:#FF6B6B;text-align:center;padding:32px">Error al cargar</div>'; }
}
function cerrarVerColeccion() { document.getElementById('modal-ver-col').style.display = 'none'; }

async function eliminarColeccion(id, nombre) {
  if (!confirm('¿Eliminar la colección "' + nombre + '"? Esta acción no se puede deshacer.')) return;
  const fd = new FormData();
  fd.append('csrf_token', window.PV_CSRF);
  const r = await fetch(RUTA_URL + 'colecciones/eliminar/' + id, { method: 'POST', body: fd });
  const d = await r.json();
  if (d.ok) { showToast('Colección eliminada', 'success'); setTimeout(() => location.reload(), 800); }
  else showToast(d.error || 'Error', 'error');
}

function escHtml(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

function showToast(msg, tipo) {
  const tc = document.getElementById('toast-container');
  if (!tc) return;
  const t = document.createElement('div');
  t.className = 'toast toast-' + (tipo || 'info'); t.textContent = msg;
  tc.appendChild(t);
  setTimeout(() => t.classList.add('show'), 10);
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3500);
}
</script>
<?php require RUTA_APP . '/vistas/inc/footer.php'; ?>
