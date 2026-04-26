// ═══════════════════════════════════════════════════════════
// PROMPTVAULT — main.js
// ═══════════════════════════════════════════════════════════

const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

// ═══════════════════════════════════════════════════════════
// NAVIGATION (SPA)
// ═══════════════════════════════════════════════════════════
function goPage(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  const el = document.getElementById('page-' + page);
  if (el) el.classList.add('active');
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  const nl = document.getElementById('nl-' + page);
  if (nl) nl.classList.add('active');
  closeNotifications();
  closeUserMenu();

  if (page === 'explorar')    initExplore();
  if (page === 'colecciones') buildCollections();
  if (page === 'rankings')    buildLeaderboard();
  if (page === 'miperfil')    loadMyProfile();
  if (page === 'guardados')   loadGuardados();
  window.scrollTo(0, 0);
}

function sidebarActive(el) {
  el.closest('.sidebar').querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
}

function filterByCategory(cat) {
  window.location.href = RUTA_URL + '?cat=' + cat;
}

function cambiarPagina(p) {
  const url = new URL(window.location.href);
  url.searchParams.set('p', p);
  window.location.href = url.toString();
}

function sortFeed(btn, mode) {
  document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const url = new URL(window.location.href);
  url.searchParams.set('orden', mode);
  url.searchParams.delete('p');
  window.location.href = url.toString();
}

// ═══════════════════════════════════════════════════════════
// MODALS
// ═══════════════════════════════════════════════════════════
function openModal(type) {
  const overlay = document.getElementById('modal-overlay');
  document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
  const target = document.getElementById('modal-' + type);
  if (target) target.style.display = 'block';
  overlay.classList.add('open');

  // Pre-fill edit profile fields from server data
  if (type === 'editperfil' && window.PV_USER) {
    // Fetch current profile data to pre-fill
    fetch(RUTA_URL + 'perfil/stats')
      .then(r => r.json())
      .catch(() => ({}));
    // Pre-fill from PHP-injected data if available
    const bioEl = document.getElementById('edit-bio');
    const ciudadEl = document.getElementById('edit-ciudad');
    const webEl = document.getElementById('edit-web');
    const previewEl = document.getElementById('avatar-preview-img');
    const statusEl = document.getElementById('avatar-upload-status');

    if (window.PV_PERFIL) {
      if (bioEl)    { bioEl.value = window.PV_PERFIL.bio || ''; updateCharCount(bioEl, 'edit-bio-count', 300); }
      if (ciudadEl) ciudadEl.value = window.PV_PERFIL.ciudad || '';
      if (webEl)    webEl.value = window.PV_PERFIL.sitio_web || '';
    }
    if (previewEl && window.PV_USER.avatar) previewEl.src = window.PV_USER.avatar;
    if (statusEl) statusEl.textContent = '';
  }
}

function closeModal(e) {
  if (e && e.target !== document.getElementById('modal-overlay')) return;
  document.getElementById('modal-overlay').classList.remove('open');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal({ target: document.getElementById('modal-overlay') });
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault();
    document.getElementById('nav-search-input')?.focus();
  }
  if ((e.metaKey || e.ctrlKey) && e.key === 'n') {
    e.preventDefault();
    openModal('newprompt');
  }
});

// ═══════════════════════════════════════════════════════════
// USER MENU
// ═══════════════════════════════════════════════════════════
function toggleUserMenu() {
  document.querySelector('.nav-avatar-wrap')?.classList.toggle('open');
}
function closeUserMenu() {
  document.querySelector('.nav-avatar-wrap')?.classList.remove('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.nav-avatar-wrap')) closeUserMenu();
});

// ═══════════════════════════════════════════════════════════
// AUTH
// ═══════════════════════════════════════════════════════════
function switchAuthTab(tab, el) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('auth-login').style.display = tab === 'login' ? 'block' : 'none';
  document.getElementById('auth-registro').style.display = tab === 'registro' ? 'block' : 'none';
}

async function doLogin() {
  const email = document.getElementById('login-email').value.trim();
  const pass  = document.getElementById('login-pass').value;
  const errEl = document.getElementById('login-error');
  errEl.style.display = 'none';

  if (!email || !pass) { showError(errEl, 'Rellena todos los campos'); return; }

  const fd = new FormData();
  fd.append('email', email);
  fd.append('password', pass);
  fd.append('csrf_token', CSRF());

  try {
    const r = await fetch(RUTA_URL + 'auth/login', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('✓ ' + d.mensaje, 'success');
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showError(errEl, d.error || 'Error al iniciar sesión');
    }
  } catch {
    showError(errEl, 'Error de conexión');
  }
}

async function doRegistro() {
  const nombre  = document.getElementById('reg-nombre').value.trim();
  const email   = document.getElementById('reg-email').value.trim();
  const pass    = document.getElementById('reg-pass').value;
  const confirm = document.getElementById('reg-pass-confirm').value;
  const errEl   = document.getElementById('reg-error');
  errEl.style.display = 'none';

  if (!nombre || !email || !pass || !confirm) { showError(errEl, 'Rellena todos los campos'); return; }

  const fd = new FormData();
  fd.append('nombre', nombre);
  fd.append('email', email);
  fd.append('password', pass);
  fd.append('password_confirm', confirm);
  fd.append('csrf_token', CSRF());

  try {
    const r = await fetch(RUTA_URL + 'auth/registro', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('🚀 ' + d.mensaje, 'success');
      setTimeout(() => switchAuthTab('login', document.querySelectorAll('.auth-tab')[0]), 1200);
    } else {
      showError(errEl, d.error || 'Error en el registro');
    }
  } catch {
    showError(errEl, 'Error de conexión');
  }
}

function showError(el, msg) {
  el.textContent = msg;
  el.style.display = 'block';
}

function togglePass(id, btn) {
  const inp = document.getElementById(id);
  if (!inp) return;
  if (inp.type === 'password') { inp.type = 'text'; btn.textContent = '🙈'; }
  else { inp.type = 'password'; btn.textContent = '👁'; }
}

function validateUsername(inp) {
  const hint = document.getElementById('reg-nombre-hint');
  const v = inp.value;
  if (!v) { hint.textContent = 'Solo letras, números y guiones bajos'; hint.style.color = ''; return; }
  if (/^[a-zA-Z0-9_]+$/.test(v) && v.length >= 3) {
    hint.textContent = '✓ Disponible (pendiente de verificar)';
    hint.style.color = 'var(--green)';
  } else {
    hint.textContent = 'Solo letras, números y guiones bajos (mín. 3)';
    hint.style.color = 'var(--coral)';
  }
}

function updatePassStrength(inp) {
  const container = inp.id === 'reg-pass' ? document.getElementById('pass-strength') :
                    inp.id === 'nueva-pass' ? document.getElementById('pass-strength-2') : null;
  if (!container) return;
  const v = inp.value;
  let strength = 0;
  if (v.length >= 8)  strength++;
  if (/[A-Z]/.test(v)) strength++;
  if (/[0-9]/.test(v)) strength++;
  if (/[^a-zA-Z0-9]/.test(v)) strength++;

  const colors = ['var(--coral)', 'var(--amber)', 'var(--amber)', 'var(--green)', 'var(--green)'];
  const widths = ['0%', '25%', '50%', '75%', '100%'];
  container.innerHTML = `<div class="pass-strength-bar" style="width:${widths[strength]};background:${colors[strength]}"></div>`;
}

// ═══════════════════════════════════════════════════════════
// NUEVO PROMPT
// ═══════════════════════════════════════════════════════════
async function publicarPrompt() {
  const titulo    = document.getElementById('np-titulo').value.trim();
  const contenido = document.getElementById('np-contenido').value.trim();
  const categoria = document.getElementById('np-categoria').value;
  const modelo    = document.getElementById('np-modelo').value;
  const tags      = document.getElementById('np-tags').value;

  if (!titulo || titulo.length < 5) { showToast('⚠️ El título debe tener al menos 5 caracteres', 'error'); return; }
  if (!contenido || contenido.length < 20) { showToast('⚠️ El prompt debe tener al menos 20 caracteres', 'error'); return; }
  if (!categoria) { showToast('⚠️ Selecciona una categoría', 'error'); return; }

  if (!window.PV_USER) { openModal('auth'); showToast('⚠️ Inicia sesión para publicar', 'error'); return; }

  const fd = new FormData();
  fd.append('titulo',     titulo);
  fd.append('contenido',  contenido);
  fd.append('categoria',  categoria);
  fd.append('modelo',     modelo);
  fd.append('tags',       tags);
  fd.append('csrf_token', CSRF());

  try {
    const r = await fetch(RUTA_URL + 'prompts/crear', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('🚀 ' + d.mensaje, 'success');
      closeModal({ target: document.getElementById('modal-overlay') });
      setTimeout(() => window.location.reload(), 1200);
    } else {
      showToast('❌ ' + (d.error || 'Error al publicar'), 'error');
    }
  } catch {
    showToast('Error de conexión', 'error');
  }
}

function updateCharCount(el, countId, max) {
  const cc = document.getElementById(countId);
  if (cc) cc.textContent = el.value.length.toLocaleString() + ' / ' + max.toLocaleString();
}

// Contador título
document.addEventListener('DOMContentLoaded', () => {
  const titulo = document.getElementById('np-titulo');
  if (titulo) {
    titulo.addEventListener('input', () => {
      const hint = document.getElementById('np-titulo-hint');
      if (hint) hint.textContent = titulo.value.length + ' / 200';
    });
  }
});

// ═══════════════════════════════════════════════════════════
// VER DETALLE PROMPT (AJAX)
// ═══════════════════════════════════════════════════════════
async function verDetalle(id) {
  openModal('view');
  const container = document.getElementById('view-contenido');
  container.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text3)">
    <div class="typing-indicator" style="justify-content:center">
      <div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>
    </div>
    <div style="margin-top:14px;font-size:13px">Cargando prompt...</div>
  </div>`;

  try {
    const r = await fetch(RUTA_URL + 'prompts/detalle/' + id);
    const d = await r.json();
    if (d.error) { container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--coral)">❌ ' + d.error + '</div>'; return; }

    const p = d.prompt;
    const catClass = getCatClass(p.categoria);
    const miVoto = d.mi_voto;
    const esFav = d.es_favorito;
    const esPropio = window.PV_USER && window.PV_USER.id === p.usuario_id;

    container.innerHTML = `
      <div class="view-header">
        <div class="view-vote-col">
          <button class="view-vote-btn ${miVoto === 'positivo' ? 'voted' : ''}"
                  onclick="voteInModal(this,'${p.id}','positivo')" title="Votar positivo">▲</button>
          <div class="view-vote-count" id="vvc-${p.id}">${p.votos_pos}</div>
          <button class="view-vote-btn down ${miVoto === 'negativo' ? 'voted-down' : ''}"
                  onclick="voteInModal(this,'${p.id}','negativo')" title="Votar negativo">▼</button>
        </div>
        <div style="flex:1;min-width:0">
          <div class="modal-title" style="margin-bottom:6px;padding-right:40px">${escHtml(p.titulo)}</div>
          <div class="card-meta">
            <span class="user-chip-name">@${escHtml(p.usuario_nombre)}</span>
            ${p.verificado ? '<span class="verified-icon">✓</span>' : ''}
            <div class="dot"></div>
            <span class="time">${timeAgo(p.fecha_creacion)}</span>
            <span class="category-badge ${catClass}">${p.categoria}</span>
          </div>
        </div>
      </div>

      <div class="prompt-actions-row">
        <div class="prompt-stat-chip" onclick="copyFullPrompt('view-prompt-text')">
          🔗 Copiar prompt
        </div>
        <div class="prompt-stat-chip ${esFav ? 'saved' : ''}" id="fav-chip-${p.id}"
             onclick="toggleFavModal('${p.id}',this)">
          ${esFav ? '✅ Guardado' : '🔖 Guardar'}
        </div>
        <div class="prompt-stat-chip">💬 ${p.num_comentarios} comentarios</div>
        <div class="prompt-stat-chip">👁 ${p.num_favoritos} guardados</div>
        ${esPropio ? `
          <div class="prompt-stat-chip" onclick="closeModal({target:document.getElementById('modal-overlay')});editarPrompt(null,'${p.id}')">✏️ Editar</div>
          <div class="prompt-stat-chip" style="color:var(--coral)" onclick="confirmarEliminar(null,'${p.id}')">🗑 Eliminar</div>
        ` : ''}
      </div>

      <div class="prompt-full-box">
        <div class="prompt-full-label">Prompt completo</div>
        <button class="prompt-copy-btn" onclick="copyFullPrompt('view-prompt-text')">📋 Copiar</button>
        <div class="prompt-full-text" id="view-prompt-text">${escHtml(p.contenido)}</div>
      </div>

      ${p.tags ? `<div class="card-tags" style="margin-bottom:16px">
        ${p.tags.split(',').map(t => `<span class="tag">#${escHtml(t.trim())}</span>`).join('')}
      </div>` : ''}

      ${d.relacionados && d.relacionados.length ? `
        <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;font-family:'JetBrains Mono',monospace">
          Prompts relacionados
        </div>
        ${d.relacionados.map(rel => `
          <div class="related-card" onclick="closeModal({target:document.getElementById('modal-overlay')});setTimeout(()=>verDetalle('${rel.id}'),150)">
            <span class="category-badge ${getCatClass(rel.categoria)}" style="flex-shrink:0">${rel.categoria}</span>
            <span class="related-card-title">${escHtml(rel.titulo)}</span>
            <span style="font-size:11px;color:var(--text3);font-family:'JetBrains Mono',monospace;flex-shrink:0">▲${rel.votos_pos}</span>
          </div>
        `).join('')}
        <div style="height:16px"></div>
      ` : ''}

      <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:12px">
        💬 Comentarios (${d.comentarios.length})
      </div>
      <div id="lista-comentarios-${p.id}">
        ${renderComentarios(d.comentarios)}
      </div>
      ${window.PV_USER ? `
        <div style="display:flex;gap:10px;margin-top:14px">
          <input type="hidden" id="cmt-pid" value="${p.id}">
          <input class="form-input" type="text" id="cmt-texto"
                 placeholder="Escribe un comentario..." maxlength="1000"
                 onkeydown="if(event.key==='Enter')enviarComentario()">
          <button class="btn-primary" onclick="enviarComentario()" style="white-space:nowrap;padding:10px 16px">
            Enviar
          </button>
        </div>
      ` : `
        <div style="text-align:center;padding:14px;color:var(--text3);font-size:13px">
          <a href="#" onclick="openModal('auth')" style="color:var(--accent2)">Inicia sesión</a> para comentar
        </div>
      `}
    `;
  } catch (err) {
    container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--coral)">❌ Error al cargar el prompt</div>';
  }
}

function renderComentarios(comentarios) {
  if (!comentarios.length) return '<div style="text-align:center;padding:20px;color:var(--text3);font-size:13px">Sé el primero en comentar 💬</div>';
  return comentarios.map(c => `
    <div class="comment-item">
      <div class="comment-header">
        ${c.avatar
          ? `<img src="${escHtml(c.avatar)}" class="comment-avatar" alt="avatar">`
          : `<div class="comment-avatar" style="background:var(--accent)">${escHtml(c.usuario_nombre.substring(0,2).toUpperCase())}</div>`
        }
        <span class="comment-name">@${escHtml(c.usuario_nombre)}</span>
        ${c.verificado ? '<span class="verified-icon">✓</span>' : ''}
        <span class="comment-time">${timeAgo(c.fecha_creacion)}</span>
      </div>
      <div class="comment-text">${escHtml(c.contenido)}</div>
    </div>
  `).join('');
}

async function enviarComentario() {
  const pid = document.getElementById('cmt-pid')?.value;
  const txt = document.getElementById('cmt-texto')?.value.trim();
  if (!txt || !pid) return;

  const fd = new FormData();
  fd.append('prompt_id', pid);
  fd.append('contenido', txt);
  fd.append('csrf_token', CSRF());

  try {
    const r = await fetch(RUTA_URL + 'prompts/comentar', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      document.getElementById('cmt-texto').value = '';
      document.getElementById('lista-comentarios-' + pid).innerHTML = renderComentarios(d.comentarios);
      showToast('💬 Comentario publicado', 'success');
    } else {
      showToast('❌ ' + (d.error || 'Error'), 'error');
    }
  } catch {
    showToast('Error de conexión', 'error');
  }
}

// ═══════════════════════════════════════════════════════════
// VOTOS
// ═══════════════════════════════════════════════════════════
async function vote(btn, e, promptId, tipo) {
  if (e) e.stopPropagation();
  if (!window.PV_USER) { openModal('auth'); showToast('⚠️ Inicia sesión para votar', 'error'); return; }

  try {
    const r = await fetch(RUTA_URL + 'prompts/votar/' + promptId + '/' + tipo, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF())
    });
    const d = await r.json();
    if (d.ok) {
      const col = btn.closest('.vote-col');
      const countEl = col.querySelector('.vote-count') || document.getElementById('vc-' + promptId);
      const upBtn   = col.querySelector('.vote-btn:not(.down)');
      const downBtn = col.querySelector('.vote-btn.down');
      if (upBtn)   upBtn.classList.remove('voted');
      if (downBtn) downBtn.classList.remove('voted-down');

      if (d.accion !== 'removed') {
        if (tipo === 'positivo' && upBtn) upBtn.classList.add('voted');
        if (tipo === 'negativo' && downBtn) downBtn.classList.add('voted-down');
      }
      if (countEl) countEl.textContent = d.positivos;
    }
  } catch {}
}

async function voteInModal(btn, promptId, tipo) {
  if (!window.PV_USER) { openModal('auth'); showToast('⚠️ Inicia sesión para votar', 'error'); return; }

  try {
    const r = await fetch(RUTA_URL + 'prompts/votar/' + promptId + '/' + tipo, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF())
    });
    const d = await r.json();
    if (d.ok) {
      const modal = btn.closest('.modal');
      const upBtn   = modal.querySelector('.view-vote-btn:not(.down)');
      const downBtn = modal.querySelector('.view-vote-btn.down');
      const countEl = document.getElementById('vvc-' + promptId);
      if (upBtn)   upBtn.classList.remove('voted');
      if (downBtn) downBtn.classList.remove('voted-down');
      if (d.accion !== 'removed') {
        if (tipo === 'positivo' && upBtn) upBtn.classList.add('voted');
        if (tipo === 'negativo' && downBtn) downBtn.classList.add('voted-down');
      }
      if (countEl) countEl.textContent = d.positivos;
    }
  } catch {}
}

// ═══════════════════════════════════════════════════════════
// FAVORITOS
// ═══════════════════════════════════════════════════════════
async function toggleFavorito(e, id, btn) {
  if (e) e.stopPropagation();
  if (!window.PV_USER) { openModal('auth'); showToast('⚠️ Inicia sesión para guardar', 'error'); return; }

  try {
    const r = await fetch(RUTA_URL + 'prompts/favorito/' + id, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF())
    });
    const d = await r.json();
    if (d.ok) {
      const span = btn.querySelector('span');
      if (d.guardado) {
        btn.classList.add('saved');
        if (span) span.textContent = 'Guardado';
        btn.innerHTML = '✅ <span>Guardado</span>';
      } else {
        btn.classList.remove('saved');
        btn.innerHTML = '🔖 <span>Guardar</span>';
      }
      showToast(d.mensaje, 'success');
    }
  } catch {}
}

async function toggleFavModal(id, chip) {
  if (!window.PV_USER) { openModal('auth'); return; }
  try {
    const r = await fetch(RUTA_URL + 'prompts/favorito/' + id, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF())
    });
    const d = await r.json();
    if (d.ok) {
      chip.innerHTML = d.guardado ? '✅ Guardado' : '🔖 Guardar';
      chip.className = 'prompt-stat-chip' + (d.guardado ? ' saved' : '');
      showToast(d.mensaje, 'success');
    }
  } catch {}
}

// ═══════════════════════════════════════════════════════════
// COPIAR PROMPT
// ═══════════════════════════════════════════════════════════
function copyFullPrompt(elId) {
  const el = document.getElementById(elId);
  if (!el) return;
  navigator.clipboard.writeText(el.textContent).then(() => {
    showToast('🔗 Prompt copiado al portapapeles', 'success');
  }).catch(() => {
    const ta = document.createElement('textarea');
    ta.value = el.textContent;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('🔗 Prompt copiado', 'success');
  });
}

function copiarPrompt(e, id) {
  if (e) e.stopPropagation();
  const card = document.querySelector(`[data-id="${id}"] .card-preview p`);
  const text = card ? card.textContent : '';
  navigator.clipboard.writeText(text).then(() => showToast('🔗 Prompt copiado', 'success'));
}

// ═══════════════════════════════════════════════════════════
// EDITAR / ELIMINAR PROMPTS
// ═══════════════════════════════════════════════════════════
async function editarPrompt(e, id) {
  if (e) e.stopPropagation();
  window.location.href = RUTA_URL + 'prompts/editar/' + id;
}

async function confirmarEliminar(e, id) {
  if (e) e.stopPropagation();
  if (!confirm('¿Estás seguro de que quieres eliminar este prompt? Esta acción no se puede deshacer.')) return;

  const fd = new FormData();
  fd.append('csrf_token', CSRF());
  try {
    const r = await fetch(RUTA_URL + 'prompts/eliminar/' + id, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('🗑 Prompt eliminado', 'success');
      const card = document.querySelector(`[data-id="${id}"]`);
      if (card) { card.style.opacity = '0'; setTimeout(() => card.remove(), 300); }
      closeModal({ target: document.getElementById('modal-overlay') });
    } else {
      showToast('❌ ' + (d.error || 'Error al eliminar'), 'error');
    }
  } catch {
    showToast('Error de conexión', 'error');
  }
}

// ═══════════════════════════════════════════════════════════
// BÚSQUEDA EN TIEMPO REAL
// ═══════════════════════════════════════════════════════════
let searchTimeout = null;
let exploreFilter = { cat: '', modelo: '' };

function handleNavSearch(val) {
  const results = document.getElementById('nav-search-results');
  if (!val || val.length < 2) { results.classList.remove('open'); return; }
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    try {
      const r = await fetch(RUTA_URL + 'prompts/buscar?q=' + encodeURIComponent(val));
      const d = await r.json();
      if (!d.resultados.length) {
        results.innerHTML = '<div style="padding:14px;text-align:center;color:var(--text3);font-size:13px">Sin resultados para "' + escHtml(val) + '"</div>';
      } else {
        results.innerHTML = d.resultados.slice(0, 6).map(p => `
          <div class="search-result-item" onclick="results.classList.remove('open');verDetalle('${p.id}')">
            <div class="sri-title">${escHtml(p.titulo)}</div>
            <div class="sri-meta">${p.categoria} · @${escHtml(p.usuario_nombre)} · ▲${p.votos_pos}</div>
          </div>
        `).join('');
      }
      results.classList.add('open');
    } catch {}
  }, 350);
}

document.addEventListener('click', e => {
  if (!e.target.closest('.nav-search-wrap')) {
    document.getElementById('nav-search-results')?.classList.remove('open');
  }
});

function navSearchTag(e, tag) {
  if (e && typeof e === 'object') e.stopPropagation();
  const q = typeof e === 'string' ? e : tag;
  goPage('explorar');
  const inp = document.getElementById('explore-search-input');
  if (inp) { inp.value = q; debounceSearch(q); }
}

// ═══════════════════════════════════════════════════════════
// EXPLORAR
// ═══════════════════════════════════════════════════════════
function initExplore() {
  debounceSearch('');
}

let exploreSearchTimeout = null;
function debounceSearch(val) {
  clearTimeout(exploreSearchTimeout);
  exploreSearchTimeout = setTimeout(() => doExploreSearch(val), 400);
}

async function doExploreSearch(q) {
  const grid = document.getElementById('explore-grid');
  const loading = document.getElementById('explore-search-loading');
  if (loading) loading.style.display = 'inline';

  try {
    const params = new URLSearchParams({ q: q || '' });
    if (exploreFilter.cat)    params.set('cat', exploreFilter.cat);
    if (exploreFilter.modelo) params.set('modelo', exploreFilter.modelo);

    const r = await fetch(RUTA_URL + 'prompts/buscar?' + params);
    const d = await r.json();

    if (loading) loading.style.display = 'none';

    if (!d.resultados || !d.resultados.length) {
      grid.innerHTML = `<div style="text-align:center;padding:60px;color:var(--text3);grid-column:1/-1">
        <div style="font-size:32px;margin-bottom:12px">🔍</div>
        <div>No se encontraron prompts${q ? ' para "' + escHtml(q) + '"' : ''}</div>
      </div>`;
      return;
    }

    grid.innerHTML = d.resultados.map(p => `
      <div class="explore-card" onclick="verDetalle('${p.id}')">
        <div class="card-meta" style="margin-bottom:8px">
          <span class="category-badge ${getCatClass(p.categoria)}">${escHtml(p.categoria)}</span>
          <div class="model-chip" style="margin-left:auto"><div class="model-dot"></div>${escHtml(p.modelo)}</div>
        </div>
        <div class="explore-card-title">${escHtml(p.titulo)}</div>
        <div class="explore-card-preview">${escHtml(p.contenido)}</div>
        ${p.tags ? `<div class="card-tags" style="margin-bottom:10px">${p.tags.split(',').slice(0,4).map(t=>`<span class="tag">#${escHtml(t.trim())}</span>`).join('')}</div>` : ''}
        <div class="explore-card-footer">
          <div class="explore-card-stats">
            <span>▲ ${p.votos_pos}</span>
            <span>💬 ${p.num_comentarios}</span>
          </div>
          <div style="font-size:11px;color:var(--text3)">@${escHtml(p.usuario_nombre)}</div>
        </div>
      </div>
    `).join('');
  } catch {
    if (loading) loading.style.display = 'none';
  }
}

function filterChip(el, cat) {
  el.closest('.filter-row').querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  exploreFilter.cat = cat;
  debounceSearch(document.getElementById('explore-search-input')?.value || '');
}

function filterModelo(el, modelo) {
  el.closest('.filter-row').querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  exploreFilter.modelo = modelo;
  debounceSearch(document.getElementById('explore-search-input')?.value || '');
}

// ═══════════════════════════════════════════════════════════
// PERFIL
// ═══════════════════════════════════════════════════════════
function verPerfil(e, id) {
  if (e && typeof e === 'object') e.stopPropagation();
  window.location.href = RUTA_URL + 'perfil/' + id;
}

async function loadMyProfile() {
  if (!window.PV_USER) return;
  // Cargar stats reales (endpoint dedicado — funciona para cualquier usuario, no solo el top-5)
  loadProfileStats();
  try {
    const r = await fetch(RUTA_URL + 'perfil/mis_prompts');
    const d = await r.json();
    const prompts = d.prompts || [];

    // Render prompts tab
    const content = document.getElementById('profile-tab-content');
    if (content) {
      if (!prompts.length) {
        content.innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div><div class="empty-title">Sin prompts todavía</div><div class="empty-sub">Publica tu primer prompt</div><button class="btn-primary" style="margin-top:16px" onclick="openModal('newprompt')">+ Publicar prompt</button></div>`;
      } else {
        content.innerHTML = '<div style="display:flex;flex-direction:column;gap:12px">' + prompts.map(p => renderPromptCard(p, true)).join('') + '</div>';
      }
    }
  } catch {
    const content = document.getElementById('profile-tab-content');
    if (content) content.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><div class="empty-title">Error al cargar prompts</div></div>';
  }
}

async function profileTab(el, tab) {
  document.querySelectorAll('#profile-tabs .tab').forEach(t => t.classList.remove('active'));
  if (el) el.classList.add('active');
  const content = document.getElementById('profile-tab-content');
  if (!content) return;

  if (tab === 'saved') {
    content.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text3)"><div class="typing-indicator" style="justify-content:center"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>`;
    try {
      const r = await fetch(RUTA_URL + 'perfil/mis_favoritos');
      const d = await r.json();
      if (!d.favoritos || !d.favoritos.length) {
        content.innerHTML = `<div class="empty-state"><div class="empty-icon">🔖</div><div class="empty-title">Prompts guardados</div><div class="empty-sub">Guarda prompts usando 🔖</div></div>`;
      } else {
        content.innerHTML = '<div style="display:flex;flex-direction:column;gap:12px">' + d.favoritos.map(p => renderPromptCard(p, false)).join('') + '</div>';
      }
    } catch {
      content.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><div class="empty-title">Error al cargar</div></div>`;
    }
  } else if (tab === 'collections') {
    content.innerHTML = `<div class="empty-state"><div class="empty-icon">📁</div><div class="empty-title">Mis colecciones</div><div class="empty-sub">Próximamente: crea y gestiona colecciones</div></div>`;
  } else if (tab === 'activity') {
    content.innerHTML = `<div class="empty-state"><div class="empty-icon">📊</div><div class="empty-title">Actividad reciente</div><div class="empty-sub">Próximamente: gráficos de rendimiento</div></div>`;
  } else {
    // prompts — ya cargados por loadMyProfile
  }
}

async function loadGuardados() {
  if (!window.PV_USER) return;
  const grid = document.getElementById('guardados-grid');
  if (!grid) return;
  grid.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><div class="typing-indicator" style="justify-content:center"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div><div style="margin-top:12px">Cargando guardados...</div></div>';
  try {
    const r = await fetch(RUTA_URL + 'perfil/mis_favoritos');
    const d = await r.json();
    if (!d.favoritos || !d.favoritos.length) {
      grid.innerHTML = `<div class="empty-state"><div class="empty-icon">🔖</div><div class="empty-title">Sin guardados</div><div class="empty-sub">Guarda prompts usando el botón 🔖</div></div>`;
      return;
    }
    grid.innerHTML = '<div style="display:flex;flex-direction:column;gap:12px">' + d.favoritos.map(p => renderPromptCard(p, false)).join('') + '</div>';
  } catch {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">⚠️</div><div class="empty-title">Error al cargar guardados</div></div>';
  }
}

// ═══════════════════════════════════════════════════════════
// EDITAR PERFIL
// ═══════════════════════════════════════════════════════════
function switchConfigTab(tab, el) {
  document.querySelectorAll('#config-tabs .tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('config-perfil').style.display = tab === 'perfil' ? 'block' : 'none';
  document.getElementById('config-seguridad').style.display = tab === 'seguridad' ? 'block' : 'none';
}

async function guardarPerfil() {
  const bio    = document.getElementById('edit-bio').value;
  const ciudad = document.getElementById('edit-ciudad').value;
  const web    = document.getElementById('edit-web').value;

  const fd = new FormData();
  fd.append('bio', bio); fd.append('ciudad', ciudad); fd.append('sitio_web', web);
  fd.append('csrf_token', CSRF());

  try {
    const r = await fetch(RUTA_URL + 'perfil/actualizar', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('✓ ' + d.mensaje, 'success');
      closeModal({ target: document.getElementById('modal-overlay') });
    } else {
      showToast('❌ ' + (d.error || 'Error'), 'error');
    }
  } catch { showToast('Error de conexión', 'error'); }
}

async function cambiarPassword() {
  const actual    = document.getElementById('pass-actual').value;
  const nueva     = document.getElementById('nueva-pass').value;
  const confirmar = document.getElementById('confirmar-pass').value;

  if (!actual || !nueva || !confirmar) { showToast('⚠️ Rellena todos los campos', 'error'); return; }
  if (nueva !== confirmar) { showToast('⚠️ Las contraseñas no coinciden', 'error'); return; }

  const fd = new FormData();
  fd.append('pass_actual', actual); fd.append('nueva_pass', nueva); fd.append('confirmar_pass', confirmar);
  fd.append('csrf_token', CSRF());

  try {
    const r = await fetch(RUTA_URL + 'perfil/cambiarpass', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('✓ ' + d.mensaje, 'success');
      closeModal({ target: document.getElementById('modal-overlay') });
    } else {
      showToast('❌ ' + (d.error || 'Error'), 'error');
    }
  } catch { showToast('Error de conexión', 'error'); }
}

// ═══════════════════════════════════════════════════════════
// AVATAR UPLOAD
// ═══════════════════════════════════════════════════════════
function previewAndUploadAvatar(input) {
  const file = input.files[0];
  if (!file) return;

  const statusEl = document.getElementById('avatar-upload-status');
  const previewEl = document.getElementById('avatar-preview-img');

  // Client-side validation
  const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
  if (!allowed.includes(file.type)) {
    statusEl.textContent = '❌ Solo JPEG, PNG, WebP o GIF';
    statusEl.style.color = 'var(--coral)';
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    statusEl.textContent = '❌ El archivo pesa más de 2 MB';
    statusEl.style.color = 'var(--coral)';
    return;
  }

  // Show local preview immediately
  const reader = new FileReader();
  reader.onload = e => { if (previewEl) previewEl.src = e.target.result; };
  reader.readAsDataURL(file);

  // Upload
  statusEl.textContent = '⏳ Subiendo...';
  statusEl.style.color = 'var(--text3)';

  const fd = new FormData();
  fd.append('avatar', file);

  fetch(RUTA_URL + 'perfil/subiravatar', {
    method: 'POST',
    headers: { 'X-CSRF-Token': CSRF() },
    body: fd,
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) {
      statusEl.textContent = '✓ Avatar actualizado';
      statusEl.style.color = 'var(--green)';
      // Update nav avatar
      const navAvatar = document.querySelector('.nav-avatar');
      if (navAvatar && navAvatar.tagName === 'IMG') navAvatar.src = d.avatar_url;
      if (previewEl) previewEl.src = d.avatar_url;
      showToast('✓ Avatar actualizado', 'success');
    } else {
      statusEl.textContent = '❌ ' + (d.error || 'Error al subir');
      statusEl.style.color = 'var(--coral)';
      showToast('❌ ' + (d.error || 'Error al subir'), 'error');
    }
  })
  .catch(() => {
    statusEl.textContent = '❌ Error de conexión';
    statusEl.style.color = 'var(--coral)';
  });
}

// ═══════════════════════════════════════════════════════════
// PROFILE STATS (carga real para cualquier usuario)
// ═══════════════════════════════════════════════════════════
async function loadProfileStats() {
  if (!window.PV_USER) return;
  try {
    const r = await fetch(RUTA_URL + 'perfil/stats');
    const d = await r.json();
    if (d.stats) {
      const s = d.stats;
      const mpP = document.getElementById('mp-prompts');
      const mpS = document.getElementById('mp-seguidores');
      const mpSi = document.getElementById('mp-siguiendo');
      if (mpP)  mpP.textContent  = formatNumberJS(s.num_prompts)    || 0;
      if (mpS)  mpS.textContent  = formatNumberJS(s.num_seguidores) || 0;
      if (mpSi) mpSi.textContent = formatNumberJS(s.num_siguiendo)  || 0;
    }
  } catch { /* silencioso */ }
}

// ═══════════════════════════════════════════════════════════
// SEGUIR USUARIO
// ═══════════════════════════════════════════════════════════
async function toggleSeguir(btn, userId) {
  if (!window.PV_USER) { openModal('auth'); return; }
  const fd = new FormData();
  fd.append('csrf_token', CSRF());
  try {
    const r = await fetch(RUTA_URL + 'perfil/seguir/' + userId, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      btn.textContent = d.siguiendo ? '✓ Siguiendo' : '+ Seguir';
      btn.className = 'follow-btn' + (d.siguiendo ? ' following' : '');
      showToast(d.mensaje, 'success');
    }
  } catch {}
}

// ═══════════════════════════════════════════════════════════
// NOTIFICATIONS (desde BD)
// ═══════════════════════════════════════════════════════════
function toggleNotifications() {
  const panel = document.getElementById('notif-panel');
  panel?.classList.toggle('open');
  if (panel?.classList.contains('open')) {
    document.getElementById('notif-count') && (document.getElementById('notif-count').style.display = 'none');
    loadNotificaciones();
  }
}
function closeNotifications() {
  document.getElementById('notif-panel')?.classList.remove('open');
}
function markAllRead() {
  document.querySelectorAll('.notif-dot').forEach(d => d.style.display = 'none');
  showToast('✓ Todas marcadas como leídas', 'success');
}
document.addEventListener('click', e => {
  if (!e.target.closest('#notif-panel') && !e.target.closest('#notif-btn')) closeNotifications();
});

async function loadNotificaciones() {
  if (!window.PV_USER) return;
  const panel = document.getElementById('notif-panel');
  const list  = panel?.querySelector('.notif-list') || panel;
  if (!panel) return;

  // Buscar o crear contenedor de lista
  let listEl = panel.querySelector('#notif-list-items');
  if (!listEl) {
    listEl = document.createElement('div');
    listEl.id = 'notif-list-items';
    panel.appendChild(listEl);
  }
  listEl.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text3)">Cargando...</div>';

  try {
    const r = await fetch(RUTA_URL + 'perfil/notificaciones');
    const d = await r.json();
    const notifs = d.notificaciones || [];

    if (!notifs.length) {
      listEl.innerHTML = '<div style="text-align:center;padding:24px;color:var(--text3);font-size:13px">Sin notificaciones</div>';
      return;
    }

    const iconMap = { voto: '⬆️', comentario: '💬', seguidor: '👤' };
    const textMap = {
      voto: (n) => `<strong>@${escHtml(n.actor_nombre)}</strong> votó tu prompt "${escHtml((n.ref_titulo||'').substring(0,40))}"`,
      comentario: (n) => `<strong>@${escHtml(n.actor_nombre)}</strong> comentó en "${escHtml((n.ref_titulo||'').substring(0,40))}"`,
      seguidor: (n) => `<strong>@${escHtml(n.actor_nombre)}</strong> empezó a seguirte`,
    };

    listEl.innerHTML = notifs.map(n => `
      <div class="notif-item" onclick="handleNotifClick('${escHtml(n.tipo)}','${escHtml(n.ref_id)}')">
        <div class="notif-avatar" style="background:var(--accent)">${(n.actor_nombre||'?').substring(0,2).toUpperCase()}</div>
        <div class="notif-body">
          <div class="notif-text">${(textMap[n.tipo] || (() => ''))(n)}</div>
          <div class="notif-time">${timeAgo(n.fecha)}</div>
        </div>
      </div>
    `).join('');
  } catch {
    listEl.innerHTML = '<div style="text-align:center;padding:24px;color:var(--coral);font-size:13px">Error al cargar</div>';
  }
}

function handleNotifClick(tipo, refId) {
  if (!refId) return;
  if (tipo === 'voto' || tipo === 'comentario') verDetalle(refId);
  else if (tipo === 'seguidor') window.location.href = RUTA_URL + 'perfil/' + refId;
  closeNotifications();
}

// ═══════════════════════════════════════════════════════════
// RENDER HELPERS (para SPA)
// ═══════════════════════════════════════════════════════════
function formatNumberJS(n) {
  n = parseInt(n) || 0;
  if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
  if (n >= 1000)    return (n/1000).toFixed(1) + 'k';
  return n;
}

function renderPromptCard(p, esPropio = false) {
  const cats = {codigo:'cat-code',escritura:'cat-writing',analisis:'cat-analysis',imagen:'cat-image',chatbot:'cat-chat',razonamiento:'cat-reason'};
  const catClass = cats[(p.categoria||'').toLowerCase()] || 'cat-code';
  const emojis = {codigo:'💻',escritura:'✍️',analisis:'📊',imagen:'🎨',chatbot:'💬',razonamiento:'🧠'};
  const catEmoji = emojis[(p.categoria||'').toLowerCase()] || '⚡';
  const tags = (p.tags || '').split(',').filter(Boolean).slice(0,5).map(t =>
    `<span class="tag">#${escHtml(t.trim())}</span>`).join('');
  const preview = (p.contenido||'').substring(0,180).replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const editBtns = esPropio ? `
    <span class="card-action" onclick="editarPrompt(event,'${p.id}')">✏️ <span>Editar</span></span>
    <span class="card-action danger" onclick="confirmarEliminar(event,'${p.id}')">🗑 <span>Eliminar</span></span>
  ` : '';
  const authorEl = p.usuario_nombre ? `
    <div class="user-chip" onclick="event.stopPropagation();window.location.href='${RUTA_URL}perfil/${p.usuario_id||''}'">
      <img src="${escHtml(p.avatar||'')}" class="user-chip-avatar" alt="" onerror="this.style.display='none'">
      <span class="user-chip-name">@${escHtml(p.usuario_nombre)}</span>
    </div><div class="dot"></div>
  ` : '';
  return `
    <div class="prompt-card" onclick="verDetalle('${p.id}')" data-id="${p.id}">
      <div class="card-top">
        <div class="vote-col">
          <button class="vote-btn" onclick="vote(this,event,'${p.id}','positivo')">▲</button>
          <div class="vote-count" id="vc-${p.id}">${formatNumberJS(p.votos_pos)}</div>
          <button class="vote-btn down" onclick="vote(this,event,'${p.id}','negativo')">▼</button>
        </div>
        <div class="card-body">
          <div class="card-meta">
            ${authorEl}
            <span class="category-badge ${catClass}">${catEmoji} ${(p.categoria||'').charAt(0).toUpperCase()+(p.categoria||'').slice(1)}</span>
            <div class="model-chip" style="margin-left:auto"><div class="model-dot"></div>${escHtml((p.modelo||'').charAt(0).toUpperCase()+(p.modelo||'').slice(1))}</div>
          </div>
          <div class="card-title">${escHtml(p.titulo)}</div>
          <div class="card-preview"><p>${preview}${(p.contenido||'').length>180?'...':''}</p><div class="preview-fade"></div></div>
          ${tags ? `<div class="card-tags">${tags}</div>` : ''}
          <div class="card-footer">
            <span class="card-action" onclick="verDetalle('${p.id}')">💬 <span>${p.num_comentarios||0}</span></span>
            <span class="card-action" onclick="copiarPrompt(event,'${p.id}')">🔗 <span>Copiar</span></span>
            <span class="card-action" onclick="toggleFavorito(event,'${p.id}',this)">🔖 <span>Guardar</span></span>
            ${editBtns}
          </div>
        </div>
      </div>
    </div>`;
}

// ═══════════════════════════════════════════════════════════
// COLLECTIONS (estática)
// ═══════════════════════════════════════════════════════════
const COLLECTIONS_DATA = [
  {emoji:'🚀',name:'Prompts para startups',desc:'Los mejores prompts para founders: pitch decks, análisis de competencia, estrategia de producto.',count:47,saves:2300,color:'linear-gradient(135deg,rgba(124,111,255,0.15),rgba(92,225,230,0.1))'},
  {emoji:'🎓',name:'Aprendizaje acelerado',desc:'Prompts de estudio, resúmenes, técnica Feynman y flashcards automáticas.',count:31,saves:1800,color:'linear-gradient(135deg,rgba(61,220,132,0.15),rgba(92,225,230,0.1))'},
  {emoji:'💼',name:'Productivity suite',desc:'Organiza tu semana, gestiona proyectos, redacta emails y automatiza tareas.',count:28,saves:1200,color:'linear-gradient(135deg,rgba(255,181,71,0.15),rgba(255,107,107,0.1))'},
  {emoji:'🤖',name:'System prompts top',desc:'Los mejores system prompts para crear asistentes y chatbots especializados.',count:52,saves:3100,color:'linear-gradient(135deg,rgba(124,111,255,0.15),rgba(255,121,198,0.1))'},
  {emoji:'💻',name:'Code & Dev 2025',desc:'Prompts para programadores: debugging, arquitectura, code review y documentación.',count:63,saves:4200,color:'linear-gradient(135deg,rgba(92,225,230,0.15),rgba(124,111,255,0.1))'},
  {emoji:'📈',name:'Marketing & Growth',desc:'SEO, content marketing, ads copy, CRO y growth hacking.',count:38,saves:1600,color:'linear-gradient(135deg,rgba(255,107,107,0.15),rgba(255,181,71,0.1))'},
  {emoji:'🎨',name:'Creatividad & Arte',desc:'Prompts para Midjourney, DALL-E, escritura creativa y producción visual.',count:44,saves:2800,color:'linear-gradient(135deg,rgba(255,121,198,0.15),rgba(92,225,230,0.1))'},
  {emoji:'🔬',name:'Investigación',desc:'Prompts para revisar papers, sintetizar investigaciones y analizar datos.',count:22,saves:890,color:'linear-gradient(135deg,rgba(61,220,132,0.15),rgba(255,181,71,0.1))'},
];

function buildCollections() {
  const grid = document.getElementById('collections-grid');
  if (!grid) return;
  grid.innerHTML = COLLECTIONS_DATA.map(c => `
    <div class="collection-card">
      <div class="collection-banner" style="background:${c.color}">${c.emoji}</div>
      <div class="collection-body">
        <div class="collection-name">${c.name}</div>
        <div class="collection-desc">${c.desc}</div>
        <div class="collection-meta">
          <span>${c.count} prompts · ${(c.saves/1000).toFixed(1)}k guardados</span>
          <button class="collection-save-btn" onclick="event.stopPropagation();showToast('✅ Colección guardada','success')">+ Guardar</button>
        </div>
      </div>
    </div>
  `).join('');
}

// ═══════════════════════════════════════════════════════════
// LEADERBOARD (desde BD real)
// ═══════════════════════════════════════════════════════════

function renderLeaderboard(creators, content) {
  if (!creators.length) {
    content.innerHTML = '<div class="empty-state"><div class="empty-icon">🏆</div><div class="empty-title">Sin datos aún</div></div>';
    return;
  }
  const top3 = creators.slice(0, 3);
  const rest  = creators.slice(3);
  const podiumOrder = [top3[1] || top3[0], top3[0], top3[2] || top3[0]];
  const podiumClasses = ['second', 'first', 'third'];
  const podiumMedals = ['🥈', '🥇', '🥉'];
  content.innerHTML = `
    <div class="lb-podium">
      ${podiumOrder.map((u, i) => u ? `
        <div class="lb-podium-card ${podiumClasses[i]}" onclick="verPerfil(null,'${u.id}')"
             style="padding-top:${i===1?'24px':'16px'}">
          <div class="lb-rank">${podiumMedals[i]}</div>
          <div class="lb-avatar" style="background:linear-gradient(135deg,var(--accent),var(--cyan))">
            ${u.nombre.substring(0,2).toUpperCase()}
          </div>
          <div class="lb-name">${escHtml(u.nombre)}</div>
          <div class="lb-handle">@${escHtml(u.nombre)}</div>
          <div class="lb-points" style="color:${i===1?'var(--amber)':i===0?'#C0C0C0':'#CD7F32'}">
            ${u.num_prompts * 10}pts
          </div>
          <div style="font-size:11px;color:var(--text3);margin-top:2px">${u.num_prompts} prompts</div>
        </div>
      ` : '<div></div>').join('')}
    </div>
    <div class="lb-table">
      ${rest.map((u, i) => `
        <div class="lb-row" onclick="verPerfil(null,'${u.id}')">
          <div class="lb-row-rank">#${i + 4}</div>
          <div class="avatar-sm" style="background:linear-gradient(135deg,var(--accent),var(--cyan));width:36px;height:36px;font-size:12px;flex-shrink:0;border-radius:50%">
            ${u.nombre.substring(0,2).toUpperCase()}
          </div>
          <div class="lb-row-info">
            <div class="lb-row-name">${escHtml(u.nombre)}</div>
            <div class="lb-row-sub">${u.num_prompts} prompts · ${u.num_seguidores} seguidores</div>
          </div>
          <div class="lb-row-stat">
            <div class="lb-row-num" style="color:var(--green)">+${u.num_prompts * 10}</div>
            <div class="lb-change up">↑ activo</div>
          </div>
        </div>
      `).join('')}
    </div>`;
}

function lbTabActive(el, mode) {
  document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  buildLeaderboard(mode);
}

async function buildLeaderboard(mode = 'prompts') {
  const content = document.getElementById('lb-content');
  if (!content) return;

  content.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><div class="typing-indicator" style="justify-content:center"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>';

  let creators = [];
  try {
    const r = await fetch(RUTA_URL + 'inicio/leaderboard?modo=' + mode);
    const d = await r.json();
    creators = d.creadores || [];
  } catch {
    // Fallback a datos locales si falla
    creators = window.PV_TOP_CREADORES ? [...window.PV_TOP_CREADORES] : [];
    if (mode === 'seguidores') creators.sort((a,b) => b.num_seguidores - a.num_seguidores);
  }

  renderLeaderboard(creators, content);
}

// ═══════════════════════════════════════════════════════════
// TESTER
// ═══════════════════════════════════════════════════════════
const SAMPLE_PROMPTS = {
  code: {
    system: 'Eres un senior software engineer con 15+ años de experiencia en múltiples lenguajes y paradigmas.',
    prompt: `Eres un revisor de código senior especializado en calidad, seguridad y arquitectura. Al revisar código:

1. 🐛 BUGS: Identifica errores potenciales y edge cases
2. ⚡ PERFORMANCE: Sugiere optimizaciones con análisis de complejidad
3. 🔒 SEGURIDAD: Detecta vulnerabilidades (XSS, inyección SQL, etc.)
4. 🏗️ ARQUITECTURA: Aplica principios SOLID y patrones de diseño
5. ✅ POSITIVO: Reconoce lo que está bien implementado

Proporciona código corregido con comentarios explicativos.`
  },
  writer: {
    system: 'Eres un ghostwriter experto en contenido técnico y viral para redes sociales.',
    prompt: `Actúa como ghostwriter especialista en contenido técnico viral. Transforma conceptos complejos en hilos de Twitter que generen engagement masivo.

Estructura: Hook devastador (<280 chars) → Desarrollo en 8-10 tweets concisos → CTA final.
Para cada tweet: emojis estratégicos + datos concretos + valor real.
Objetivo: que el lector comparta y guarde el hilo.`
  },
  analyst: {
    system: 'Eres un analista cuantitativo con experiencia en hedge funds top-tier.',
    prompt: `Eres un analista financiero cuantitativo. Dado cualquier empresa o sector, genera:

1. Resumen ejecutivo (5 puntos clave)
2. Análisis de métricas clave con benchmarks del sector
3. Identificación de riesgos y oportunidades
4. Recomendación accionable con justificación
5. Preguntas críticas para profundizar el análisis

Sé preciso, usa datos cuando estén disponibles y evita generalidades.`
  }
};

let testerHistory = [];
let testerRunning = false;

function loadSamplePrompt(type) {
  const s = SAMPLE_PROMPTS[type];
  if (!s) return;
  document.getElementById('tester-system').value = s.system;
  document.getElementById('tester-prompt').value = s.prompt;
  document.getElementById('tester-char').textContent = s.prompt.length + ' caracteres';
  showToast('📋 Prompt de muestra cargado', 'info');
}

async function runTesterPrompt() {
  if (testerRunning) return;
  const sys    = document.getElementById('tester-system').value.trim();
  const prompt = document.getElementById('tester-prompt').value.trim();
  if (!prompt) { showToast('⚠️ Escribe un prompt primero', 'error'); return; }

  testerRunning = true;
  document.getElementById('tester-status').textContent = '⚡ Ejecutando...';
  document.getElementById('tester-send-btn').disabled = true;

  const chat = document.getElementById('tester-chat');
  chat.innerHTML = '';
  testerHistory = [];

  const userMsg = Object.assign(document.createElement('div'), {
    className: 'tester-message user', textContent: prompt
  });
  chat.appendChild(userMsg);

  const thinkMsg = document.createElement('div');
  thinkMsg.className = 'tester-message thinking';
  thinkMsg.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
  chat.appendChild(thinkMsg);
  chat.scrollTop = chat.scrollHeight;

  try {
    const msgs = [{ role: 'user', content: prompt }];
    testerHistory = [...msgs];
    const body = { messages: msgs, csrf_token: CSRF() };
    if (sys) body.system = sys;

    const res  = await fetch(RUTA_URL + 'public/api/tester.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const data = await res.json();
    const reply = data.content?.map(c => c.text || '').join('') || data.error?.message || data.error || 'Sin respuesta.';

    thinkMsg.remove();
    const aiMsg = Object.assign(document.createElement('div'), {
      className: 'tester-message ai', textContent: reply
    });
    chat.appendChild(aiMsg);
    testerHistory.push({ role: 'assistant', content: reply });
    document.getElementById('tester-status').textContent = '✓ Completado · ' + reply.length + ' chars';
  } catch (err) {
    thinkMsg.remove();
    const errMsg = Object.assign(document.createElement('div'), { className: 'tester-message ai' });
    errMsg.style.color = 'var(--coral)';
    errMsg.textContent = '⚠️ ' + (err.message || 'Error al ejecutar');
    chat.appendChild(errMsg);
    document.getElementById('tester-status').textContent = '✗ Error';
  }

  testerRunning = false;
  document.getElementById('tester-send-btn').disabled = false;
  chat.scrollTop = chat.scrollHeight;
}

async function sendFollowup() {
  const inp  = document.getElementById('tester-followup');
  const text = inp.value.trim();
  if (!text || testerRunning) return;
  if (!testerHistory.length) { showToast('⚠️ Ejecuta el prompt primero', 'error'); return; }

  inp.value = '';
  inp.style.height = 'auto';
  testerRunning = true;

  const chat = document.getElementById('tester-chat');
  chat.appendChild(Object.assign(document.createElement('div'), { className: 'tester-message user', textContent: text }));

  const thinkMsg = document.createElement('div');
  thinkMsg.className = 'tester-message thinking';
  thinkMsg.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
  chat.appendChild(thinkMsg);
  chat.scrollTop = chat.scrollHeight;

  testerHistory.push({ role: 'user', content: text });

  try {
    const sys  = document.getElementById('tester-system').value.trim();
    const body = { messages: testerHistory, csrf_token: CSRF() };
    if (sys) body.system = sys;
    const res  = await fetch(RUTA_URL + 'public/api/tester.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const data = await res.json();
    const reply = data.content?.map(c => c.text || '').join('') || 'Sin respuesta.';
    thinkMsg.remove();
    const aiMsg = Object.assign(document.createElement('div'), { className: 'tester-message ai', textContent: reply });
    chat.appendChild(aiMsg);
    testerHistory.push({ role: 'assistant', content: reply });
  } catch (err) {
    thinkMsg.remove();
    const errMsg = Object.assign(document.createElement('div'), { className: 'tester-message ai' });
    errMsg.style.color = 'var(--coral)';
    errMsg.textContent = '⚠️ ' + err.message;
    chat.appendChild(errMsg);
  }
  testerRunning = false;
  chat.scrollTop = chat.scrollHeight;
}

function clearTester() {
  document.getElementById('tester-system').value = '';
  document.getElementById('tester-prompt').value = '';
  document.getElementById('tester-char').textContent = '0 caracteres';
  document.getElementById('tester-chat').innerHTML = `<div style="text-align:center;padding:40px 20px;color:var(--text3)">
    <div style="font-size:36px;margin-bottom:12px">⚡</div>
    <div style="font-size:14px;font-weight:500;color:var(--text2)">Escribe un prompt y ejecuta</div>
    <div style="font-size:12px;margin-top:6px">La respuesta de Claude aparecerá aquí</div>
  </div>`;
  document.getElementById('tester-status').textContent = 'Listo para ejecutar';
  testerHistory = [];
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

// ═══════════════════════════════════════════════════════════
// TOAST
// ═══════════════════════════════════════════════════════════
function showToast(msg, type = 'info') {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, 2800);
}

// ═══════════════════════════════════════════════════════════
// UTILIDADES
// ═══════════════════════════════════════════════════════════
function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function getCatClass(cat) {
  const map = { codigo:'cat-code', escritura:'cat-writing', analisis:'cat-analysis',
                imagen:'cat-image', chatbot:'cat-chat', razonamiento:'cat-reason' };
  return map[(cat||'').toLowerCase()] || 'cat-code';
}

function timeAgo(fecha) {
  if (!fecha) return '';
  const diff = (Date.now() - new Date(fecha).getTime()) / 1000;
  if (diff < 60)    return 'ahora mismo';
  if (diff < 3600)  return Math.floor(diff / 60) + ' min';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h';
  if (diff < 604800)return Math.floor(diff / 86400) + 'd';
  return new Date(fecha).toLocaleDateString('es-ES', { day:'numeric', month:'short' });
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  // Marcar nav link activo según URL
  const path = window.location.pathname;
  if (path.includes('/perfil')) document.getElementById('nl-miperfil')?.classList.add('active');
  else document.getElementById('nl-inicio')?.classList.add('active');

  // Init colecciones en precarga
  buildCollections();
  // Leaderboard se carga cuando el usuario navega a esa página (via goPage → buildLeaderboard)
});
