<?php
// Override meta description for SEO
$datos['extra_head'] = '<meta name="description" content="' . htmlspecialchars($datos['meta_desc'] ?? 'Explora los mejores prompts de IA en PromptVault.') . '">';
$datos['extra_head'] .= '<link rel="canonical" href="' . htmlspecialchars(rtrim(SITE_URL,'/') . '/explorar' . (isset($_GET['cat']) ? '?cat=' . urlencode($_GET['cat']) : '')) . '">';
require RUTA_APP . '/vistas/inc/header.php';

$cats    = ['' => '🌐 Todos', 'codigo' => '💻 Código', 'escritura' => '✍️ Escritura',
            'analisis' => '📊 Análisis', 'imagen' => '🎨 Imagen', 'chatbot' => '💬 Chatbot', 'razonamiento' => '🧠 Razonamiento'];
$modelos = ['' => 'Todos los modelos', 'claude' => 'Claude', 'gpt4' => 'GPT-4', 'gemini' => 'Gemini', 'llama' => 'Llama'];
$ordenes = ['hot' => '🔥 Trending', 'nuevo' => '✨ Nuevo', 'top' => '⭐ Top'];

$q         = $datos['q'] ?? '';
$categoria = $datos['categoria'] ?? '';
$modeloFiltro = $datos['modelo_filtro'] ?? '';
$orden     = $datos['orden'] ?? 'hot';
$pagina    = $datos['pagina'] ?? 1;
$totalPaginas = $datos['total_paginas'] ?? 1;
$total     = $datos['total'] ?? 0;
$resultados = $datos['resultados'] ?? [];
?>

<div class="layout" style="padding-top:24px">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-section">Navegar</div>
    <div class="sidebar-item" onclick="window.location.href='<?php echo RUTA_URL; ?>'"><span class="icon">🏠</span> Inicio</div>
    <div class="sidebar-item active"><span class="icon">🔥</span> Explorar <span class="badge green">SEO</span></div>
    <?php if ($datos['usuario']): ?>
    <div class="sidebar-item" onclick="window.location.href='<?php echo RUTA_URL; ?>colecciones'"><span class="icon">📁</span> Colecciones</div>
    <?php endif; ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Categorías</div>
    <?php foreach ($cats as $key => $label): if ($key === '') continue; ?>
    <div class="sidebar-item <?php echo $categoria === $key ? 'active' : ''; ?>"
         onclick="window.location.href='<?php echo RUTA_URL; ?>explorar?cat=<?php echo $key; ?>'">
      <?php echo $label; ?>
    </div>
    <?php endforeach; ?>
    <div class="sidebar-divider"></div>
    <div style="padding:0 12px 12px;font-size:11px;color:var(--text3)">
      <a href="<?php echo RUTA_URL; ?>explorar" style="color:var(--text3);text-decoration:none">Política de privacidad</a> ·
      <a href="#" style="color:var(--text3);text-decoration:none">Términos</a><br>© <?php echo date('Y'); ?> PromptVault
    </div>
  </aside>

  <main class="main">
    <!-- Cabecera SEO -->
    <div style="margin-bottom:24px">
      <h1 style="font-size:24px;font-weight:700;margin:0 0 6px;color:var(--text1)">
        <?php echo $q ? '🔍 Búsqueda: <em>' . htmlspecialchars($q) . '</em>' : ($categoria ? categoriaEmoji($categoria) . ' ' . ucfirst($categoria) : '⚡ Explorar Prompts'); ?>
      </h1>
      <?php if ($total > 0): ?>
      <p style="color:var(--text3);font-size:14px;margin:0"><?php echo number_format($total); ?> prompts encontrados</p>
      <?php endif; ?>
    </div>

    <!-- Filtros -->
    <form method="GET" action="<?php echo RUTA_URL; ?>explorar" style="margin-bottom:20px">
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>"
               placeholder="Buscar prompts, tags..."
               class="form-input" style="flex:1;min-width:200px;max-width:320px"
               oninput="debounceExploreUrl(this.value)">
        <select name="cat" class="form-input" style="min-width:140px" onchange="this.form.submit()">
          <?php foreach ($cats as $key => $label): ?>
          <option value="<?php echo $key; ?>" <?php echo $categoria === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
        <select name="modelo" class="form-input" style="min-width:120px" onchange="this.form.submit()">
          <?php foreach ($modelos as $key => $label): ?>
          <option value="<?php echo $key; ?>" <?php echo $modeloFiltro === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
        <select name="orden" class="form-input" style="min-width:130px" onchange="this.form.submit()">
          <?php foreach ($ordenes as $key => $label): ?>
          <option value="<?php echo $key; ?>" <?php echo $orden === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary" style="padding:10px 16px">Buscar</button>
      </div>
    </form>

    <!-- Resultados -->
    <?php if (empty($resultados)): ?>
    <div class="empty-state">
      <div class="empty-icon">🔍</div>
      <div class="empty-title">Sin resultados</div>
      <div class="empty-sub">Prueba con otros términos o categorías</div>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px">
      <?php foreach ($resultados as $p): ?>
      <article class="prompt-card" onclick="openPromptDetail('<?php echo htmlspecialchars($p->id); ?>')">
        <div class="prompt-card-header">
          <div class="prompt-card-meta">
            <img src="<?php echo htmlspecialchars($p->avatar ?? ''); ?>" class="avatar-sm" alt="<?php echo htmlspecialchars($p->usuario_nombre ?? ''); ?>" loading="lazy">
            <div>
              <span class="username">@<?php echo htmlspecialchars($p->usuario_nombre ?? ''); ?></span>
              <span class="dot">·</span>
              <span class="timeago"><?php echo timeAgo($p->fecha_creacion); ?></span>
            </div>
          </div>
          <span class="cat-badge <?php echo categoriaColor($p->categoria); ?>"><?php echo categoriaEmoji($p->categoria); ?> <?php echo ucfirst(htmlspecialchars($p->categoria)); ?></span>
        </div>
        <h2 class="prompt-title"><?php echo htmlspecialchars($p->titulo); ?></h2>
        <p class="prompt-excerpt"><?php echo htmlspecialchars(truncar($p->contenido ?? '', 180)); ?></p>
        <?php if (!empty($p->tags)): ?>
        <div class="prompt-tags">
          <?php foreach (array_slice(explode(',', $p->tags), 0, 5) as $tag): if (!trim($tag)) continue; ?>
          <span class="tag">#<?php echo htmlspecialchars(trim($tag)); ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="prompt-actions">
          <button class="action-btn vote-btn <?php echo ($p->mi_voto ?? null) === 'positivo' ? 'voted' : ''; ?>"
                  onclick="event.stopPropagation();votarPrompt('<?php echo $p->id; ?>','positivo',this)">
            ⬆️ <span class="vote-count"><?php echo formatNumber($p->votos_positivos ?? 0); ?></span>
          </button>
          <button class="action-btn" onclick="event.stopPropagation();openPromptDetail('<?php echo $p->id; ?>')">
            💬 <?php echo $p->num_comentarios ?? 0; ?>
          </button>
          <button class="action-btn fav-btn <?php echo ($p->es_favorito ?? false) ? 'saved' : ''; ?>"
                  onclick="event.stopPropagation();toggleFavorito('<?php echo $p->id; ?>',this)">
            🔖
          </button>
          <button class="action-btn" onclick="event.stopPropagation();copiarPrompt('<?php echo addslashes(htmlspecialchars($p->contenido ?? '')); ?>')">
            📋 Copiar
          </button>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <nav style="display:flex;justify-content:center;gap:8px;margin-top:32px;flex-wrap:wrap" aria-label="Páginas">
      <?php if ($pagina > 1): ?>
      <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $pagina - 1])); ?>" class="btn-ghost" style="text-decoration:none">← Anterior</a>
      <?php endif; ?>
      <?php
        $start = max(1, $pagina - 2);
        $end   = min($totalPaginas, $pagina + 2);
        for ($i = $start; $i <= $end; $i++):
      ?>
      <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>"
         style="padding:8px 14px;border-radius:8px;text-decoration:none;<?php echo $i === $pagina ? 'background:var(--accent);color:#fff' : 'color:var(--text2);border:1px solid var(--border)'; ?>">
        <?php echo $i; ?>
      </a>
      <?php endfor; ?>
      <?php if ($pagina < $totalPaginas): ?>
      <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $pagina + 1])); ?>" class="btn-ghost" style="text-decoration:none">Siguiente →</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
  </main>
</div>

<script>
window.PV_USER = <?php echo json_encode($datos['usuario']); ?>;
window.PV_CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
window.PV_PERFIL = null;

let _exploreTimer;
function debounceExploreUrl(val) {
  clearTimeout(_exploreTimer);
  _exploreTimer = setTimeout(() => {
    if (val.length >= 2 || val.length === 0) {
      const url = new URL(window.location.href);
      if (val) url.searchParams.set('q', val); else url.searchParams.delete('q');
      url.searchParams.delete('p');
      window.location.href = url.toString();
    }
  }, 600);
}

// Copiar prompt
function copiarPrompt(texto) {
  navigator.clipboard.writeText(texto).then(() => showToast('📋 Copiado al portapapeles', 'success')).catch(() => showToast('Error al copiar', 'error'));
}

// Votar (reutiliza función global de main.js si se carga el modal)
function votarPrompt(id, tipo, btn) {
  if (!window.PV_USER) { showToast('Inicia sesión para votar', 'warn'); return; }
  fetch(RUTA_URL + 'prompts/votar/' + id + '/' + tipo, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'csrf_token=' + encodeURIComponent(window.PV_CSRF)
  }).then(r => r.json()).then(d => {
    if (d.ok) {
      btn.classList.toggle('voted');
      const span = btn.querySelector('.vote-count');
      if (span) span.textContent = d.positivos;
    } else showToast(d.error || 'Error', 'error');
  }).catch(() => showToast('Error de conexión', 'error'));
}

function toggleFavorito(id, btn) {
  if (!window.PV_USER) { showToast('Inicia sesión para guardar', 'warn'); return; }
  fetch(RUTA_URL + 'prompts/favorito/' + id, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'csrf_token=' + encodeURIComponent(window.PV_CSRF)
  }).then(r => r.json()).then(d => {
    if (d.ok) { btn.classList.toggle('saved'); showToast(d.mensaje, 'success'); }
    else showToast(d.error || 'Error', 'error');
  });
}

function showToast(msg, tipo) {
  const tc = document.getElementById('toast-container');
  if (!tc) return;
  const t = document.createElement('div');
  t.className = 'toast toast-' + (tipo || 'info');
  t.textContent = msg;
  tc.appendChild(t);
  setTimeout(() => t.classList.add('show'), 10);
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3500);
}

function openPromptDetail(id) {
  // Si la SPA está disponible (usuario viene del inicio) úsala, si no redirige
  if (typeof openModal === 'function' && window.currentPromptId !== undefined) {
    window.currentPromptId = id;
    loadPromptDetail(id);
    openModal('prompt');
  } else {
    // Fallback: URL directa (cuando el usuario llega directo a /explorar)
    window.location.href = RUTA_URL + '?prompt=' + id;
  }
}
</script>

<?php require RUTA_APP . '/vistas/inc/footer.php'; ?>
