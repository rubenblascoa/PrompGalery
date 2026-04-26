<?php require RUTA_APP . '/vistas/inc/header.php'; ?>

<?php $prompt = $datos['prompt']; ?>

<div class="app-container" style="max-width:720px;margin:0 auto;padding:32px 16px">
  <div style="background:var(--card);border-radius:16px;border:1px solid var(--border);padding:32px">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <div>
        <h1 style="font-size:20px;font-weight:800;color:var(--text);margin:0">✏️ Editar Prompt</h1>
        <div style="font-size:12px;color:var(--text3);margin-top:4px">Modifica los detalles de tu prompt</div>
      </div>
      <button class="btn-ghost" onclick="history.back()">← Volver</button>
    </div>

    <div class="form-group">
      <label class="form-label">Título <span class="req">*</span></label>
      <input class="form-input" type="text" id="edit-titulo"
             value="<?php echo htmlspecialchars($prompt->titulo); ?>"
             maxlength="200"
             oninput="document.getElementById('edit-titulo-hint').textContent=this.value.length+' / 200'">
      <div class="field-hint" id="edit-titulo-hint"><?php echo strlen($prompt->titulo); ?> / 200</div>
    </div>

    <div class="form-group">
      <label class="form-label">El Prompt <span class="req">*</span></label>
      <textarea class="form-textarea" id="edit-contenido" rows="10"
                oninput="updateCharCount(this,'edit-char-count',10000)"><?php echo htmlspecialchars($prompt->contenido); ?></textarea>
      <div class="char-count" id="edit-char-count"><?php echo strlen($prompt->contenido); ?> / 10,000</div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Categoría <span class="req">*</span></label>
        <select class="form-select" id="edit-categoria">
          <?php
          $cats = ['codigo'=>'💻 Código','escritura'=>'✍️ Escritura','analisis'=>'📊 Análisis',
                   'imagen'=>'🎨 Imagen','chatbot'=>'💬 Chatbot','razonamiento'=>'🧠 Razonamiento'];
          foreach ($cats as $val => $label):
          ?>
          <option value="<?php echo $val; ?>" <?php echo $prompt->categoria === $val ? 'selected' : ''; ?>>
            <?php echo $label; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Modelo IA</label>
        <select class="form-select" id="edit-modelo">
          <?php
          $modelos = ['claude'=>'Claude','gpt-4'=>'GPT-4','gpt-4o'=>'GPT-4o',
                      'gemini'=>'Gemini','midjourney'=>'Midjourney','universal'=>'Universal'];
          foreach ($modelos as $val => $label):
          ?>
          <option value="<?php echo $val; ?>" <?php echo $prompt->modelo === $val ? 'selected' : ''; ?>>
            <?php echo $label; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Tags <span class="form-hint">(separados por comas, máx. 10)</span></label>
      <input class="form-input" type="text" id="edit-tags"
             value="<?php echo htmlspecialchars($prompt->tags ?? ''); ?>"
             placeholder="ej: python, review, SOLID, seguridad">
    </div>

    <!-- Zona de peligro -->
    <div style="margin-top:28px;padding:16px;background:rgba(255,107,107,.07);border:1px solid rgba(255,107,107,.2);border-radius:10px">
      <div style="font-size:13px;font-weight:600;color:var(--coral);margin-bottom:10px">⚠️ Zona de peligro</div>
      <button class="btn-ghost" style="border-color:var(--coral);color:var(--coral)"
              onclick="confirmarEliminarDirecto('<?php echo $prompt->id; ?>')">
        🗑 Eliminar este prompt permanentemente
      </button>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
      <button class="btn-ghost" onclick="history.back()">Cancelar</button>
      <button class="btn-primary" onclick="guardarEdicion('<?php echo $prompt->id; ?>')" id="save-btn">
        💾 Guardar cambios
      </button>
    </div>

    <div id="edit-error" class="form-error" style="display:none;margin-top:12px"></div>
  </div>
</div>

<script>
async function guardarEdicion(id) {
  const titulo    = document.getElementById('edit-titulo').value.trim();
  const contenido = document.getElementById('edit-contenido').value.trim();
  const categoria = document.getElementById('edit-categoria').value;
  const modelo    = document.getElementById('edit-modelo').value;
  const tags      = document.getElementById('edit-tags').value.trim();
  const errEl     = document.getElementById('edit-error');
  const btn       = document.getElementById('save-btn');

  errEl.style.display = 'none';
  if (!titulo || titulo.length < 5) { errEl.textContent = 'El título debe tener al menos 5 caracteres'; errEl.style.display='block'; return; }
  if (!contenido || contenido.length < 20) { errEl.textContent = 'El prompt debe tener al menos 20 caracteres'; errEl.style.display='block'; return; }

  btn.disabled = true;
  btn.textContent = '⏳ Guardando...';

  const fd = new FormData();
  fd.append('titulo',    titulo);
  fd.append('contenido', contenido);
  fd.append('categoria', categoria);
  fd.append('modelo',    modelo);
  fd.append('tags',      tags);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

  try {
    const r = await fetch(RUTA_URL + 'prompts/editar/' + id, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('✓ Prompt actualizado', 'success');
      setTimeout(() => history.back(), 1200);
    } else {
      errEl.textContent = d.error || 'Error al guardar';
      errEl.style.display = 'block';
      btn.disabled = false;
      btn.textContent = '💾 Guardar cambios';
    }
  } catch {
    errEl.textContent = 'Error de conexión';
    errEl.style.display = 'block';
    btn.disabled = false;
    btn.textContent = '💾 Guardar cambios';
  }
}

async function confirmarEliminarDirecto(id) {
  if (!confirm('¿Eliminar este prompt? Esta acción no se puede deshacer.')) return;
  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  try {
    const r = await fetch(RUTA_URL + 'prompts/eliminar/' + id, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      showToast('🗑 Prompt eliminado', 'success');
      setTimeout(() => window.location.href = RUTA_URL, 1200);
    } else {
      showToast('❌ ' + (d.error || 'Error'), 'error');
    }
  } catch {
    showToast('Error de conexión', 'error');
  }
}
</script>

<?php require RUTA_APP . '/vistas/inc/footer.php'; ?>
