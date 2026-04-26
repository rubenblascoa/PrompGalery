<?php require RUTA_APP . '/vistas/inc/header.php'; ?>

<div class="app-container" id="app">

<!-- ════════ PÁGINA INICIO ════════ -->
<div class="page active" id="page-inicio">
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-section">Navegar</div>
    <div class="sidebar-item active" onclick="goPage('inicio');sidebarActive(this)">
        <span class="icon">🏠</span> Inicio
    </div>
    <div class="sidebar-item" onclick="goPage('explorar');sidebarActive(this)">
        <span class="icon">🔥</span> Trending <span class="badge green">LIVE</span>
    </div>
    <div class="sidebar-item" onclick="goPage('colecciones');sidebarActive(this)">
        <span class="icon">✨</span> Colecciones
    </div>
    <div class="sidebar-item" onclick="goPage('guardados');sidebarActive(this)">
        <span class="icon">🔖</span> Guardados
    </div>
    <?php if ($datos['usuario']): ?>
    <div class="sidebar-item" onclick="goPage('miperfil');sidebarActive(this)">
        <span class="icon">👤</span> Mi perfil
    </div>
    <?php endif; ?>
    <div class="sidebar-divider"></div>

    <div class="sidebar-section">Categorías</div>
    <div class="sidebar-item" onclick="filterByCategory('codigo');sidebarActive(this)">
        <span class="icon">💻</span> Código
        <span class="badge" id="count-codigo"></span>
    </div>
    <div class="sidebar-item" onclick="filterByCategory('escritura');sidebarActive(this)">
        <span class="icon">✍️</span> Escritura
    </div>
    <div class="sidebar-item" onclick="filterByCategory('analisis');sidebarActive(this)">
        <span class="icon">📊</span> Análisis
    </div>
    <div class="sidebar-item" onclick="filterByCategory('imagen');sidebarActive(this)">
        <span class="icon">🎨</span> Imagen
    </div>
    <div class="sidebar-item" onclick="filterByCategory('chatbot');sidebarActive(this)">
        <span class="icon">💬</span> Chatbots
    </div>
    <div class="sidebar-item" onclick="filterByCategory('razonamiento');sidebarActive(this)">
        <span class="icon">🧠</span> Razonamiento
    </div>
    <div class="sidebar-divider"></div>

    <div class="sidebar-section">Tags populares</div>
    <div style="padding:0 4px;display:flex;flex-wrap:wrap">
        <span class="tag-pill" onclick="navSearchTag('#python')">#python</span>
        <span class="tag-pill" onclick="navSearchTag('#claude')">#claude</span>
        <span class="tag-pill" onclick="navSearchTag('#review')">#review</span>
        <span class="tag-pill" onclick="navSearchTag('#gpt4')">#gpt4</span>
        <span class="tag-pill" onclick="navSearchTag('#api')">#api</span>
        <span class="tag-pill" onclick="navSearchTag('#debugging')">#debugging</span>
    </div>

    <div class="sidebar-divider"></div>
    <div style="padding:0 12px 12px;font-size:11px;color:var(--text3)">
        <a href="#" style="color:var(--text3);text-decoration:none">Política de privacidad</a> ·
        <a href="#" style="color:var(--text3);text-decoration:none">Términos</a>
        <br>© 2025 PromptVault
    </div>
  </aside>

  <!-- MAIN FEED -->
  <main class="main">

    <!-- Barra de búsqueda y crear -->
    <div class="new-prompt-btn" onclick="openModal('newprompt')">
      <div class="plus">+</div>
      <div>
        <div style="font-weight:600;color:var(--text);margin-bottom:2px">Compartir tu prompt</div>
        <div style="font-size:11px;color:var(--text3)">Ayuda a la comunidad con tus mejores prompts de IA</div>
      </div>
      <div style="margin-left:auto;font-size:12px;color:var(--text3)">Ctrl+N</div>
    </div>

    <div class="feed-header">
      <div class="feed-title" id="feed-label">
          <?php echo $datos['categoria'] ? '📂 ' . ucfirst($datos['categoria']) : '🏠 Feed principal'; ?>
      </div>
      <div class="feed-sort">
        <button class="sort-btn <?php echo $datos['orden'] === 'hot' ? 'active' : ''; ?>"
                onclick="sortFeed(this,'hot')">🔥 Hot</button>
        <button class="sort-btn <?php echo $datos['orden'] === 'top' ? 'active' : ''; ?>"
                onclick="sortFeed(this,'top')">⬆ Top</button>
        <button class="sort-btn <?php echo $datos['orden'] === 'reciente' ? 'active' : ''; ?>"
                onclick="sortFeed(this,'reciente')">🆕 Nuevo</button>
      </div>
    </div>

    <div id="feed-container">
    <?php if (empty($datos['prompts'])): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <div class="empty-title">No hay prompts aún</div>
            <div class="empty-sub">¡Sé el primero en publicar un prompt!</div>
            <button class="btn-primary" style="margin-top:16px" onclick="openModal('newprompt')">
                + Publicar primer prompt
            </button>
        </div>
    <?php else: ?>
    <?php
      $esPrimero = true;
      foreach ($datos['prompts'] as $prompt):
        $catClass = categoriaColor($prompt->categoria);
        $miVoto = $prompt->mi_voto ?? null;
        $esFavorito = $prompt->es_favorito ?? false;
    ?>
      <div class="<?php echo $esPrimero ? 'featured-card' : 'prompt-card'; ?>"
           onclick="verDetalle('<?php echo $prompt->id; ?>')"
           data-id="<?php echo $prompt->id; ?>">

        <?php if ($esPrimero): ?>
          <div class="featured-label"><span class="star-icon">★</span> Prompt del día</div>
        <?php endif; ?>

        <div class="card-top">
          <!-- Votación -->
          <div class="vote-col">
            <button class="vote-btn <?php echo $miVoto === 'positivo' ? 'voted' : ''; ?>"
                    onclick="vote(this,event,'<?php echo $prompt->id; ?>','positivo')"
                    title="Votar positivo">▲</button>
            <div class="vote-count" id="vc-<?php echo $prompt->id; ?>">
                <?php echo formatNumber($prompt->votos_pos); ?>
            </div>
            <button class="vote-btn down <?php echo $miVoto === 'negativo' ? 'voted-down' : ''; ?>"
                    onclick="vote(this,event,'<?php echo $prompt->id; ?>','negativo')"
                    title="Votar negativo">▼</button>
          </div>

          <div class="card-body">
            <div class="card-meta">
              <div class="user-chip" onclick="verPerfil(event,'<?php echo $prompt->usuario_id; ?>')">
                <?php if ($prompt->avatar): ?>
                    <img src="<?php echo htmlspecialchars($prompt->avatar, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="avatar" class="user-chip-avatar">
                <?php else: ?>
                    <div class="user-chip-avatar" style="background:var(--accent)">
                        <?php echo strtoupper(substr($prompt->usuario_nombre, 0, 2)); ?>
                    </div>
                <?php endif; ?>
                <span class="user-chip-name">@<?php echo htmlspecialchars($prompt->usuario_nombre); ?></span>
                <?php if ($prompt->verificado): ?><span class="verified-icon">✓</span><?php endif; ?>
              </div>
              <div class="dot"></div>
              <span class="time" title="<?php echo $prompt->fecha_creacion; ?>">
                <?php echo timeAgo($prompt->fecha_creacion); ?>
              </span>
              <span class="category-badge <?php echo $catClass; ?>">
                <?php echo categoriaEmoji($prompt->categoria) . ' ' . ucfirst($prompt->categoria); ?>
              </span>
            </div>

            <div class="card-title"><?php echo htmlspecialchars($prompt->titulo); ?></div>

            <div class="card-preview">
              <p><?php echo htmlspecialchars(truncar($prompt->contenido, 220)); ?></p>
              <div class="preview-fade"></div>
            </div>

            <?php if ($prompt->tags): ?>
            <div class="card-tags">
              <?php foreach (array_slice(explode(',', $prompt->tags), 0, 6) as $tag): ?>
                  <span class="tag" onclick="navSearchTag(event,'<?php echo htmlspecialchars(trim($tag)); ?>')">
                      #<?php echo htmlspecialchars(trim($tag)); ?>
                  </span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card-footer">
              <span class="card-action" onclick="verDetalle('<?php echo $prompt->id; ?>')">
                  💬 <span><?php echo $prompt->num_comentarios; ?></span>
              </span>
              <span class="card-action" onclick="copiarPrompt(event,'<?php echo $prompt->id; ?>')">
                  🔗 <span>Copiar</span>
              </span>
              <span class="card-action <?php echo $esFavorito ? 'saved' : ''; ?>"
                    onclick="toggleFavorito(event,'<?php echo $prompt->id; ?>',this)">
                  <?php echo $esFavorito ? '✅' : '🔖'; ?>
                  <span><?php echo $esFavorito ? 'Guardado' : 'Guardar'; ?></span>
              </span>
              <?php if ($datos['usuario'] && $datos['usuario']['id'] === $prompt->usuario_id): ?>
              <span class="card-action" onclick="editarPrompt(event,'<?php echo $prompt->id; ?>')">
                  ✏️ <span>Editar</span>
              </span>
              <span class="card-action danger" onclick="confirmarEliminar(event,'<?php echo $prompt->id; ?>')">
                  🗑 <span>Eliminar</span>
              </span>
              <?php endif; ?>
              <div class="model-chip" style="margin-left:auto">
                  <div class="model-dot"></div>
                  <?php echo htmlspecialchars(ucfirst($prompt->modelo)); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php $esPrimero = false; endforeach; ?>
    <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($datos['total_paginas'] > 1): ?>
    <div class="pagination">
        <?php if ($datos['pagina'] > 1): ?>
            <button class="page-btn" onclick="cambiarPagina(<?php echo $datos['pagina'] - 1; ?>)">← Anterior</button>
        <?php endif; ?>
        <span class="page-info">Página <?php echo $datos['pagina']; ?> de <?php echo $datos['total_paginas']; ?></span>
        <?php if ($datos['pagina'] < $datos['total_paginas']): ?>
            <button class="page-btn" onclick="cambiarPagina(<?php echo $datos['pagina'] + 1; ?>)">Siguiente →</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>

  <!-- PANEL DERECHO -->
  <aside class="right-panel">
    <!-- Stats globales -->
    <div class="panel-section">
        <div class="panel-title">📊 Estadísticas globales</div>
        <?php if ($datos['stats']): ?>
        <div class="stat-row">
            <span class="stat-label">Prompts publicados</span>
            <span class="stat-val"><?php echo formatNumber($datos['stats']->total_prompts); ?></span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Usuarios activos</span>
            <span class="stat-val"><?php echo formatNumber($datos['stats']->total_usuarios); ?></span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Votos hoy</span>
            <span class="stat-val"><?php echo formatNumber($datos['stats']->votos_hoy); ?></span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Comentarios</span>
            <span class="stat-val"><?php echo formatNumber($datos['stats']->total_comentarios); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top creadores -->
    <div class="panel-section">
        <div class="panel-title">🏆 Top creadores</div>
        <?php foreach ($datos['top_creadores'] as $i => $creador): ?>
        <div class="trending-user" onclick="verPerfil(event,'<?php echo $creador->id; ?>')">
            <div class="tu-rank"><?php echo $i + 1; ?></div>
            <img src="<?php echo htmlspecialchars($creador->avatar ?? generarAvatar($creador->nombre)); ?>"
                 class="avatar" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
            <div class="tu-info">
                <div class="tu-name">
                    <?php echo htmlspecialchars($creador->nombre); ?>
                    <?php if ($creador->verificado): ?><span class="verified-icon">✓</span><?php endif; ?>
                </div>
                <div class="tu-count">
                    <?php echo $creador->num_prompts; ?> prompts · <?php echo formatNumber($creador->num_seguidores); ?> seg.
                </div>
            </div>
            <div class="tu-score" style="color:var(--amber)">+<?php echo $creador->num_prompts * 10; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Colecciones trending -->
    <div class="panel-section">
        <div class="panel-title">📁 Colecciones trending</div>
        <div class="mini-card" onclick="goPage('colecciones')">
            <div class="mini-card-title">🚀 Prompts para startups</div>
            <div class="mini-card-meta"><span>47 prompts</span><span>·</span><span>2.3k saves</span></div>
        </div>
        <div class="mini-card" onclick="goPage('colecciones')">
            <div class="mini-card-title">💼 Productivity suite</div>
            <div class="mini-card-meta"><span>28 prompts</span><span>·</span><span>1.2k saves</span></div>
        </div>
        <div class="mini-card" onclick="goPage('colecciones')">
            <div class="mini-card-title">💻 Code & Dev 2025</div>
            <div class="mini-card-meta"><span>63 prompts</span><span>·</span><span>4.2k saves</span></div>
        </div>
    </div>
  </aside>

</div><!-- /layout -->
</div><!-- /page-inicio -->

<!-- ════════ PÁGINAS SPA (dinámicas con JS) ════════ -->
<div class="page" id="page-explorar">
    <div class="explore-header">
        <div class="explore-title">🔍 Explorar prompts</div>
        <div class="explore-sub">Descubre los mejores prompts de la comunidad</div>
        <div class="explore-search">
            <span>🔍</span>
            <input type="text" id="explore-search-input" placeholder="Buscar en todos los prompts..."
                   oninput="debounceSearch(this.value)" autocomplete="off">
            <span id="explore-search-loading" style="display:none">⏳</span>
        </div>
        <div class="filter-row">
            <button class="filter-chip active" onclick="filterChip(this,'')">Todos</button>
            <button class="filter-chip" onclick="filterChip(this,'codigo')">💻 Código</button>
            <button class="filter-chip" onclick="filterChip(this,'escritura')">✍️ Escritura</button>
            <button class="filter-chip" onclick="filterChip(this,'analisis')">📊 Análisis</button>
            <button class="filter-chip" onclick="filterChip(this,'imagen')">🎨 Imagen</button>
            <button class="filter-chip" onclick="filterChip(this,'chatbot')">💬 Chatbot</button>
            <button class="filter-chip" onclick="filterChip(this,'razonamiento')">🧠 Razonamiento</button>
        </div>
        <div class="filter-row" style="margin-top:-4px">
            <span style="font-size:11px;color:var(--text3);margin-right:8px">Modelo:</span>
            <button class="filter-chip active" onclick="filterModelo(this,'')">Todos</button>
            <button class="filter-chip" onclick="filterModelo(this,'claude')">Claude</button>
            <button class="filter-chip" onclick="filterModelo(this,'gpt-4')">GPT-4</button>
            <button class="filter-chip" onclick="filterModelo(this,'gemini')">Gemini</button>
        </div>
    </div>
    <div class="explore-grid" id="explore-grid">
        <div style="text-align:center;padding:60px;color:var(--text3);grid-column:1/-1">
            <div style="font-size:32px;margin-bottom:12px">🔍</div>
            Escribe algo para buscar o explora todos los prompts
        </div>
    </div>
</div>

<div class="page" id="page-colecciones">
    <div class="collections-header">
        <div class="explore-title">📁 Colecciones</div>
        <div class="explore-sub">Colecciones curadas por la comunidad</div>
    </div>
    <div class="collections-grid" id="collections-grid"></div>
</div>

<div class="page" id="page-rankings">
    <div class="leaderboard-header">
        <div class="explore-title">🏆 Rankings</div>
        <div class="explore-sub">Los mejores creadores de PromptVault</div>
        <div class="leaderboard-tabs" style="margin-top:16px">
            <button class="lb-tab active" onclick="lbTabActive(this,'prompts')">Por Prompts</button>
            <button class="lb-tab" onclick="lbTabActive(this,'seguidores')">Por Seguidores</button>
        </div>
    </div>
    <div class="lb-content" id="lb-content"></div>
</div>

<div class="page" id="page-tester">
    <div class="tester-layout">
        <div class="tester-left">
            <div class="tester-title">⚡ Prompt Tester</div>
            <div class="tester-sub">Prueba tus prompts con Claude directamente</div>
            <div class="form-group">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <label class="form-label" style="margin:0">System Prompt</label>
                    <div style="display:flex;gap:6px">
                        <button class="filter-chip" onclick="loadSamplePrompt('code')" style="padding:3px 10px;font-size:11px">Código</button>
                        <button class="filter-chip" onclick="loadSamplePrompt('writer')" style="padding:3px 10px;font-size:11px">Escritura</button>
                        <button class="filter-chip" onclick="loadSamplePrompt('analyst')" style="padding:3px 10px;font-size:11px">Análisis</button>
                    </div>
                </div>
                <textarea class="form-textarea" id="tester-system" rows="3"
                    placeholder="Eres un asistente útil..."></textarea>
            </div>
            <div class="form-group">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <label class="form-label" style="margin:0">Tu Prompt</label>
                    <span class="char-count" id="tester-char">0 caracteres</span>
                </div>
                <textarea class="tester-textarea" id="tester-prompt" rows="10"
                    placeholder="Escribe o pega tu prompt aquí..."
                    oninput="document.getElementById('tester-char').textContent=this.value.length+' caracteres'"></textarea>
            </div>
            <div style="display:flex;gap:10px">
                <button class="btn-primary" style="flex:1" onclick="runTesterPrompt()">▶ Ejecutar</button>
                <button class="btn-ghost" onclick="clearTester()">🗑 Limpiar</button>
            </div>
            <div style="margin-top:10px;font-size:12px;color:var(--text3)" id="tester-status">Listo para ejecutar</div>
        </div>
        <div class="tester-right">
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:16px">💬 Respuesta</div>
            <div class="tester-chat" id="tester-chat">
                <div style="text-align:center;padding:40px 20px;color:var(--text3)">
                    <div style="font-size:36px;margin-bottom:12px">⚡</div>
                    <div style="font-size:14px;font-weight:500;color:var(--text2)">Escribe un prompt y ejecuta</div>
                    <div style="font-size:12px;margin-top:6px">La respuesta de Claude aparecerá aquí</div>
                </div>
            </div>
            <div class="tester-input-area">
                <textarea class="tester-textarea" id="tester-followup" placeholder="Mensaje de seguimiento..."
                    oninput="autoResize(this)"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendFollowup()}"
                    rows="1"></textarea>
                <button class="tester-send" onclick="sendFollowup()" id="tester-send-btn">➤</button>
            </div>
        </div>
    </div>
</div>

<div class="page" id="page-miperfil">
    <div id="miperfil-content">
        <?php if ($datos['usuario']): ?>
        <div class="my-profile-cover">
            <div class="my-profile-avatar-wrap">
                <?php if ($datos['usuario']['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($datos['usuario']['avatar']); ?>"
                         class="my-profile-avatar" alt="avatar" style="object-fit:cover">
                <?php else: ?>
                    <div class="my-profile-avatar" style="background:var(--accent)">
                        <?php echo strtoupper(substr($datos['usuario']['nombre'], 0, 2)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="my-profile-body">
            <div class="my-profile-name-row">
                <div class="my-profile-name">
                    <?php echo htmlspecialchars($datos['usuario']['nombre']); ?>
                    <?php if ($datos['usuario']['verificado']): ?>
                        <span class="verified-icon">✓</span>
                    <?php endif; ?>
                </div>
                <button class="edit-profile-btn" onclick="openModal('editperfil')">✏️ Editar perfil</button>
            </div>
            <div class="profile-handle">@<?php echo htmlspecialchars($datos['usuario']['nombre']); ?></div>
            <div class="profile-stats" style="margin:18px 0">
                <div class="profile-stat">
                    <div class="profile-stat-num" id="mp-prompts">—</div>
                    <div class="profile-stat-label">Prompts</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-num" id="mp-seguidores">—</div>
                    <div class="profile-stat-label">Seguidores</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-num" id="mp-siguiendo">—</div>
                    <div class="profile-stat-label">Siguiendo</div>
                </div>
            </div>
            <div class="tab-row" id="profile-tabs">
                <div class="tab active" onclick="profileTab(this,'prompts')">Mis Prompts</div>
                <div class="tab" onclick="profileTab(this,'saved')">Guardados</div>
                <div class="tab" onclick="profileTab(this,'collections')">Colecciones</div>
                <div class="tab" onclick="profileTab(this,'activity')">Actividad</div>
            </div>
            <div id="profile-tab-content">
                <div style="text-align:center;padding:40px;color:var(--text3)">
                    <div class="skeleton" style="height:80px;border-radius:10px;margin-bottom:10px"></div>
                    <div class="skeleton" style="height:80px;border-radius:10px;margin-bottom:10px"></div>
                    <div class="skeleton" style="height:80px;border-radius:10px"></div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding-top:80px">
            <div class="empty-icon">👤</div>
            <div class="empty-title">Inicia sesión para ver tu perfil</div>
            <button class="btn-primary" style="margin-top:16px" onclick="openModal('auth')">Iniciar sesión</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="page" id="page-guardados">
    <div style="padding:32px 28px">
        <div class="explore-title">🔖 Prompts guardados</div>
        <div class="explore-sub">Tus prompts favoritos en un solo lugar</div>
        <div id="guardados-grid" style="margin-top:24px">
            <?php if (!$datos['usuario']): ?>
            <div class="empty-state">
                <div class="empty-icon">🔖</div>
                <div class="empty-title">Inicia sesión para guardar prompts</div>
                <button class="btn-primary" style="margin-top:16px" onclick="openModal('auth')">Iniciar sesión</button>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:40px;color:var(--text3)">Cargando guardados...</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- /app-container -->

<script>
// Datos del servidor para el JS
window.PV_USER = <?php echo json_encode($datos['usuario']); ?>;
window.PV_CSRF = document.querySelector('meta[name="csrf-token"]').content;
window.PV_TOP_CREADORES = <?php echo json_encode(array_map(function($c) {
    return ['id' => $c->id, 'nombre' => $c->nombre, 'avatar' => $c->avatar, 'verificado' => $c->verificado, 'num_prompts' => $c->num_prompts, 'num_seguidores' => $c->num_seguidores];
}, $datos['top_creadores'])); ?>;
</script>

<?php require RUTA_APP . '/vistas/inc/footer.php'; ?>
