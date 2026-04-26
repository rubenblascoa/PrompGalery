<div class="modal-overlay" id="modal-overlay" onclick="closeModal(event)">

  <div class="modal" id="modal-newprompt" style="display:none">
    <div class="modal-title">Publicar nuevo prompt</div>
    <div class="modal-sub">Comparte tu prompt con la comunidad</div>
    <form action="<?php echo RUTA_URL; ?>/prompts/crear" method="POST">
        <div class="form-group">
          <label class="form-label">Título</label>
          <input class="form-input" type="text" name="titulo" placeholder="Ej: Senior Code Reviewer...">
        </div>
        <div class="form-group">
          <label class="form-label">El Prompt</label>
          <textarea class="form-textarea" name="contenido" placeholder="Escribe tu prompt aquí..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-ghost" onclick="closeModal()">Cancelar</button>
          <button type="submit" class="btn-primary">🚀 Publicar prompt</button>
        </div>
    </form>
  </div>

  <div class="modal" id="modal-auth" style="display:none;max-width:420px">
    <div class="modal-title" style="text-align:center">Bienvenido a PromptVault</div>
    <button class="btn-primary" style="width:100%; margin-top:20px;">Iniciar sesión</button>
  </div>

  </div>

<script src="<?php echo RUTA_PUBLIC; ?>/js/main.js"></script>
</body>
</html>