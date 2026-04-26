<?php require RUTA_APP . '/vistas/inc/header.php'; ?>

<div class="layout">
  
  <aside class="sidebar">
    <div class="sidebar-section">Navegar</div>
    <div class="sidebar-item active"><span class="icon">🏠</span> Inicio</div>
    <div class="sidebar-item"><span class="icon">🔥</span> Trending <span class="badge green">LIVE</span></div>
    <div class="sidebar-item"><span class="icon">✨</span> Destacados <span class="badge">12</span></div>
    <div class="sidebar-item"><span class="icon">🔖</span> Guardados</div>
    <div class="sidebar-item"><span class="icon">👤</span> Mi perfil</div>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Categorías</div>
    <div class="sidebar-item"><span class="icon">💻</span> Código</div>
    <div class="sidebar-item"><span class="icon">✍️</span> Escritura</div>
    <div class="sidebar-item"><span class="icon">📊</span> Análisis</div>
    <div class="sidebar-item"><span class="icon">🎨</span> Imagen</div>
    <div class="sidebar-item"><span class="icon">💬</span> Chatbots</div>
    <div class="sidebar-item"><span class="icon">🧠</span> Razonamiento</div>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Tags populares</div>
    <div style="padding:0 4px;display:flex;flex-wrap:wrap">
      <span class="tag-pill">#python</span>
      <span class="tag-pill">#gpt4</span>
      <span class="tag-pill">#claude</span>
      <span class="tag-pill">#few-shot</span>
    </div>
  </aside>

  <main class="main">
    <div class="search-bar">
      <span class="search-icon">🔍</span>
      <input type="text" placeholder="Buscar prompts, usuarios, tags...">
    </div>

    <div class="new-prompt-btn" onclick="openModal('newprompt')">
      <div class="plus">+</div>
      <div>
        <div style="font-weight:600;color:var(--text);margin-bottom:2px">Compartir tu prompt</div>
        <div style="font-size:11px;color:var(--text3)">Ayuda a la comunidad con tus mejores prompts</div>
      </div>
    </div>

    <div class="feed-header">
      <div class="feed-title">Feed principal</div>
      <div class="feed-sort">
        <div class="sort-btn active">🔥 Hot</div>
        <div class="sort-btn">⬆ Top</div>
        <div class="sort-btn">🆕 Nuevo</div>
      </div>
    </div>

    <?php 
      $esPrimero = true; 
      foreach($datos['prompts'] as $prompt) : 
        // El primero será 'featured-card', los demás 'prompt-card'
        $claseTarjeta = $esPrimero ? 'featured-card' : 'prompt-card';
    ?>
      <div class="<?php echo $claseTarjeta; ?>" onclick="verDetalle('<?php echo $prompt->id; ?>')">
        
        <?php if($esPrimero): ?>
          <div class="featured-label"><span class="star-icon">★</span> Prompt del día</div>
        <?php endif; ?>

        <div class="card-top">
          <div class="vote-col">
              <button class="vote-btn" onclick="vote(this, event, '<?php echo $prompt->id; ?>', 'positivo')">▲</button>
              <div class="vote-count"><?php echo $prompt->votos_positivos; ?></div>
              <button class="vote-btn" style="font-size:10px" onclick="vote(this, event, '<?php echo $prompt->id; ?>', 'negativo')">▼</button>
            </div>
          
          <div class="card-body">
            <div class="card-meta">
              <div class="user-chip">
                <?php if(empty($prompt->avatar)): ?>
                    <div class="user-chip-avatar" style="background:linear-gradient(135deg,#7C6FFF,#5CE1E6)">
                        <?php echo strtoupper(substr($prompt->usuario_nombre, 0, 2)); ?>
                    </div>
                <?php else: ?>
                    <img src="<?php echo $prompt->avatar; ?>" alt="Avatar" class="user-chip-avatar" style="border:none;">
                <?php endif; ?>
                <span class="user-chip-name">@<?php echo $prompt->usuario_nombre; ?></span>
              </div>
              <div class="dot"></div>
              <span class="time"><?php echo date('d M', strtotime($prompt->fecha_creacion)); ?></span>
              
              <?php 
                $catClase = '';
                if($prompt->categoria == 'codigo') $catClase = 'cat-code';
                if($prompt->categoria == 'escritura') $catClase = 'cat-writing';
                if($prompt->categoria == 'analisis') $catClase = 'cat-analysis';
              ?>
              <span class="category-badge <?php echo $catClase; ?>"><?php echo ucfirst($prompt->categoria); ?></span>
            </div>
            
            <div class="card-title"><?php echo $prompt->titulo; ?></div>
            <div class="card-preview">
              <p><?php echo substr($prompt->contenido, 0, 200) . '...'; ?></p>
              <div class="preview-fade"></div>
            </div>
            
            <div class="card-tags">
              <?php 
                if(!empty($prompt->tags)){
                    $tags = explode(',', $prompt->tags);
                    foreach($tags as $tag) {
                        echo '<span class="tag">#' . trim($tag) . '</span>';
                    }
                }
              ?>
            </div>
            
            <div class="card-footer">
              <span class="card-action">💬 <span><?php echo $prompt->num_comentarios; ?> comentarios</span></span>
              <span class="card-action">🔗 <span>Copiar</span></span>
              <span class="card-action">🔖 <span>Guardar</span></span>
              <div class="model-chip"><div class="model-dot"></div><?php echo ucfirst($prompt->modelo); ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php 
        $esPrimero = false; 
      endforeach; 
    ?>

  </main>

  <aside class="right-panel">
    <div class="panel-section">
      <div class="panel-title">Estadísticas globales</div>
      <div class="stat-row"><span class="stat-label">Prompts publicados</span><span class="stat-val">24,891</span></div>
      <div class="stat-row"><span class="stat-label">Usuarios activos</span><span class="stat-val">8,234</span></div>
      <div class="stat-row"><span class="stat-label">Votos hoy</span><span class="stat-val">47,102</span></div>
      <div class="stat-row"><span class="stat-label">Copias hoy</span><span class="stat-val">12,847</span></div>
    </div>

    <div class="panel-section">
      <div class="panel-title">Top creadores</div>
      <?php foreach($datos['top_creadores'] as $creador) : ?>
        <div class="trending-user" onclick="openModal('profile')">
          <img src="<?php echo $creador->avatar; ?>" class="avatar" style="width:36px;height:36px;border-radius:50%;">
          <div class="tu-info">
            <div class="tu-name"><?php echo $creador->nombre; ?></div>
            <div class="tu-count"><?php echo $creador->num_prompts; ?> prompts · <?php echo $creador->num_seguidores; ?> seguidores</div>
          </div>
          <div class="tu-score">+<?php echo $creador->num_prompts * 10; ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel-section">
      <div class="panel-title">Colecciones trending</div>
      <div class="mini-card">
        <div class="mini-card-title">🚀 Prompts para startups</div>
        <div class="mini-card-meta"><span>47 prompts</span><span>·</span><span>2.3k saves</span></div>
      </div>
      <div class="mini-card">
        <div class="mini-card-title">💼 Productivity suite</div>
        <div class="mini-card-meta"><span>28 prompts</span><span>·</span><span>1.2k saves</span></div>
      </div>
    </div>
  </aside>

</div>

<?php require RUTA_APP . '/vistas/inc/footer.php'; ?>