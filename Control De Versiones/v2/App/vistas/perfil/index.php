<?php require RUTA_APP . '/vistas/inc/header.php'; ?>

<?php
$perfil    = $datos['perfil'];
$prompts   = $datos['prompts'];
$favoritos = $datos['favoritos'];
$esPropio  = $datos['es_propio'];
$siguiendo = $datos['esta_siguiendo'];
$usuario   = $datos['usuario'];
?>

<div class="app-container" style="max-width:900px;margin:0 auto;padding:24px 16px">

  <!-- Portada y cabecera -->
  <div class="profile-cover-card" style="background:var(--card);border-radius:16px;overflow:hidden;margin-bottom:24px;border:1px solid var(--border)">
    <div style="height:140px;background:linear-gradient(135deg,var(--accent) 0%,var(--cyan) 60%,var(--green) 100%);position:relative">
      <div style="position:absolute;bottom:-44px;left:28px">
        <img src="<?php echo htmlspecialchars($perfil->avatar ?? generarAvatar($perfil->nombre)); ?>"
             alt="avatar"
             style="width:88px;height:88px;border-radius:50%;border:4px solid var(--bg);object-fit:cover;background:var(--bg)">
      </div>
    </div>
    <div style="padding:56px 28px 24px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <div style="font-size:22px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:8px">
            <?php echo htmlspecialchars($perfil->nombre); ?>
            <?php if ($perfil->verificado): ?>
              <span class="verified-icon" title="Usuario verificado">✓</span>
            <?php endif; ?>
          </div>
          <div style="color:var(--text3);font-size:13px;margin-top:2px">@<?php echo htmlspecialchars($perfil->nombre); ?></div>
          <?php if ($perfil->bio): ?>
            <div style="color:var(--text2);font-size:14px;margin-top:10px;max-width:480px;line-height:1.5">
              <?php echo htmlspecialchars($perfil->bio); ?>
            </div>
          <?php endif; ?>
          <div style="display:flex;gap:16px;margin-top:12px;flex-wrap:wrap">
            <?php if ($perfil->ciudad): ?>
              <span style="font-size:12px;color:var(--text3)">📍 <?php echo htmlspecialchars($perfil->ciudad); ?></span>
            <?php endif; ?>
            <?php if ($perfil->sitio_web): ?>
              <a href="<?php echo htmlspecialchars($perfil->sitio_web); ?>" target="_blank" rel="noopener noreferrer"
                 style="font-size:12px;color:var(--accent);text-decoration:none">
                🔗 <?php echo htmlspecialchars(parse_url($perfil->sitio_web, PHP_URL_HOST) ?: $perfil->sitio_web); ?>
              </a>
            <?php endif; ?>
            <span style="font-size:12px;color:var(--text3)">📅 Desde <?php echo date('M Y', strtotime($perfil->fecha_registro)); ?></span>
          </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
          <?php if ($esPropio): ?>
            <button class="btn-ghost" onclick="openModal('editperfil')">✏️ Editar perfil</button>
          <?php elseif ($usuario): ?>
            <button class="follow-btn <?php echo $siguiendo ? 'following' : ''; ?>"
                    id="follow-btn-<?php echo $perfil->id; ?>"
                    onclick="toggleSeguir(this,'<?php echo $perfil->id; ?>')">
              <?php echo $siguiendo ? '✓ Siguiendo' : '+ Seguir'; ?>
            </button>
          <?php else: ?>
            <button class="btn-primary" onclick="openModal('auth')">+ Seguir</button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Stats -->
      <div class="profile-stats" style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border)">
        <div class="profile-stat">
          <div class="profile-stat-num"><?php echo formatNumber($perfil->num_prompts); ?></div>
          <div class="profile-stat-label">Prompts</div>
        </div>
        <div class="profile-stat">
          <div class="profile-stat-num" id="seguidor-count"><?php echo formatNumber($perfil->num_seguidores); ?></div>
          <div class="profile-stat-label">Seguidores</div>
        </div>
        <div class="profile-stat">
          <div class="profile-stat-num"><?php echo formatNumber($perfil->num_siguiendo); ?></div>
          <div class="profile-stat-label">Siguiendo</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tab-row" style="margin-bottom:20px" id="perfil-tabs">
    <div class="tab active" onclick="perfilTab(this,'prompts')">
      📝 Prompts <span style="font-size:11px;opacity:.6">(<?php echo count($prompts); ?>)</span>
    </div>
    <?php if ($esPropio && !empty($favoritos)): ?>
    <div class="tab" onclick="perfilTab(this,'favoritos')">
      🔖 Guardados <span style="font-size:11px;opacity:.6">(<?php echo count($favoritos); ?>)</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Contenido tabs -->
  <div id="perfil-tab-content">

    <!-- TAB PROMPTS -->
    <div id="ptab-prompts">
      <?php if (empty($prompts)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <div class="empty-title">Sin prompts todavía</div>
          <div class="empty-sub">
            <?php echo $esPropio ? '¡Comparte tu primer prompt con la comunidad!' : '@' . htmlspecialchars($perfil->nombre) . ' aún no ha publicado prompts'; ?>
          </div>
          <?php if ($esPropio): ?>
            <button class="btn-primary" style="margin-top:16px" onclick="openModal('newprompt')">+ Publicar prompt</button>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px">
          <?php foreach ($prompts as $p): ?>
            <div class="prompt-card" onclick="verDetalle('<?php echo $p->id; ?>')" data-id="<?php echo $p->id; ?>">
              <div class="card-top">
                <div class="vote-col">
                  <button class="vote-btn" onclick="vote(this,event,'<?php echo $p->id; ?>','positivo')" title="Votar positivo">▲</button>
                  <div class="vote-count" id="vc-<?php echo $p->id; ?>"><?php echo formatNumber($p->votos_pos); ?></div>
                  <button class="vote-btn down" onclick="vote(this,event,'<?php echo $p->id; ?>','negativo')" title="Votar negativo">▼</button>
                </div>
                <div class="card-body">
                  <div class="card-meta">
                    <span class="category-badge <?php echo categoriaColor($p->categoria); ?>">
                      <?php echo categoriaEmoji($p->categoria) . ' ' . ucfirst($p->categoria); ?>
                    </span>
                    <div class="dot"></div>
                    <span class="time"><?php echo timeAgo($p->fecha_creacion); ?></span>
                    <div class="model-chip" style="margin-left:auto">
                      <div class="model-dot"></div>
                      <?php echo htmlspecialchars(ucfirst($p->modelo)); ?>
                    </div>
                  </div>
                  <div class="card-title"><?php echo htmlspecialchars($p->titulo); ?></div>
                  <div class="card-preview">
                    <p><?php echo htmlspecialchars(truncar($p->contenido, 180)); ?></p>
                    <div class="preview-fade"></div>
                  </div>
                  <?php if ($p->tags): ?>
                  <div class="card-tags">
                    <?php foreach (array_slice(explode(',', $p->tags), 0, 5) as $tag): ?>
                      <span class="tag">#<?php echo htmlspecialchars(trim($tag)); ?></span>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  <div class="card-footer">
                    <span class="card-action" onclick="verDetalle('<?php echo $p->id; ?>')">💬 <span><?php echo $p->num_comentarios; ?></span></span>
                    <span class="card-action" onclick="copiarPrompt(event,'<?php echo $p->id; ?>')">🔗 <span>Copiar</span></span>
                    <?php if ($esPropio): ?>
                    <span class="card-action" onclick="editarPrompt(event,'<?php echo $p->id; ?>')">✏️ <span>Editar</span></span>
                    <span class="card-action danger" onclick="confirmarEliminar(event,'<?php echo $p->id; ?>')">🗑 <span>Eliminar</span></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ($datos['pagina'] > 1 || count($prompts) >= TAM_PAGINA): ?>
        <div class="pagination" style="margin-top:24px">
          <?php if ($datos['pagina'] > 1): ?>
            <button class="page-btn" onclick="window.location.href='?p=<?php echo $datos['pagina'] - 1; ?>'">← Anterior</button>
          <?php endif; ?>
          <span class="page-info">Página <?php echo $datos['pagina']; ?></span>
          <?php if (count($prompts) >= TAM_PAGINA): ?>
            <button class="page-btn" onclick="window.location.href='?p=<?php echo $datos['pagina'] + 1; ?>'">Siguiente →</button>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- TAB FAVORITOS (solo si es propio) -->
    <?php if ($esPropio): ?>
    <div id="ptab-favoritos" style="display:none">
      <?php if (empty($favoritos)): ?>
        <div class="empty-state">
          <div class="empty-icon">🔖</div>
          <div class="empty-title">Sin favoritos aún</div>
          <div class="empty-sub">Guarda prompts que te gusten con el botón 🔖</div>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px">
          <?php foreach ($favoritos as $p): ?>
            <div class="prompt-card" onclick="verDetalle('<?php echo $p->id; ?>')" data-id="<?php echo $p->id; ?>">
              <div class="card-top">
                <div class="vote-col">
                  <button class="vote-btn" onclick="vote(this,event,'<?php echo $p->id; ?>','positivo')">▲</button>
                  <div class="vote-count" id="vc-<?php echo $p->id; ?>"><?php echo formatNumber($p->votos_pos); ?></div>
                  <button class="vote-btn down" onclick="vote(this,event,'<?php echo $p->id; ?>','negativo')">▼</button>
                </div>
                <div class="card-body">
                  <div class="card-meta">
                    <div class="user-chip" onclick="event.stopPropagation();window.location.href='<?php echo RUTA_URL; ?>perfil/<?php echo $p->usuario_id ?? ''; ?>'">
                      <img src="<?php echo htmlspecialchars($p->avatar ?? generarAvatar($p->usuario_nombre)); ?>" class="user-chip-avatar" alt="">
                      <span class="user-chip-name">@<?php echo htmlspecialchars($p->usuario_nombre); ?></span>
                    </div>
                    <span class="category-badge <?php echo categoriaColor($p->categoria); ?>">
                      <?php echo categoriaEmoji($p->categoria) . ' ' . ucfirst($p->categoria); ?>
                    </span>
                    <div class="model-chip" style="margin-left:auto">
                      <div class="model-dot"></div>
                      <?php echo htmlspecialchars(ucfirst($p->modelo)); ?>
                    </div>
                  </div>
                  <div class="card-title"><?php echo htmlspecialchars($p->titulo); ?></div>
                  <div class="card-preview">
                    <p><?php echo htmlspecialchars(truncar($p->contenido, 180)); ?></p>
                    <div class="preview-fade"></div>
                  </div>
                  <div class="card-footer">
                    <span class="card-action" onclick="verDetalle('<?php echo $p->id; ?>')">💬 <span><?php echo $p->num_comentarios; ?></span></span>
                    <span class="card-action saved" onclick="toggleFavorito(event,'<?php echo $p->id; ?>',this)">✅ <span>Guardado</span></span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div><!-- /tab-content -->
</div>

<script>
function perfilTab(el, tab) {
  document.querySelectorAll('#perfil-tabs .tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('[id^="ptab-"]').forEach(d => d.style.display = 'none');
  const t = document.getElementById('ptab-' + tab);
  if (t) t.style.display = 'block';
}
</script>

<?php require RUTA_APP . '/vistas/inc/footer.php'; ?>
