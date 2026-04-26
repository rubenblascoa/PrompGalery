<?php
// Vista para crear prompt
if (!estaAutenticado()) {
    redirigir('usuarios/login');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Prompt - PromptVault</title>
    <link rel="stylesheet" href="<?php echo RUTA_PUBLIC; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo RUTA_PUBLIC; ?>css/editor.css">
</head>
<body>
    <nav>
        <div class="logo">
            <div class="logo-icon">◆</div>
            <span>Prompt<span>Vault</span></span>
        </div>
        <div class="nav-right">
            <button class="btn-ghost" onclick="window.history.back()">← Volver</button>
            <div class="avatar" style="background:linear-gradient(135deg,var(--accent),var(--cyan));">
                <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 2)); ?>
            </div>
        </div>
    </nav>

    <div class="editor-container">
        <div class="editor-main">
            <h1>Crear Nuevo Prompt</h1>

            <form method="POST" class="prompt-form">
                <div class="form-group">
                    <label class="form-label">Título del Prompt</label>
                    <input class="form-input" type="text" name="titulo" required placeholder="Ej: Senior Code Reviewer" maxlength="100">
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Categoría</label>
                        <select class="form-input" name="categoria" required>
                            <option value="chat">💬 Chat</option>
                            <option value="codigo">⌨️ Código</option>
                            <option value="escritura">✍️ Escritura</option>
                            <option value="analisis">📊 Análisis</option>
                            <option value="imagen">🖼️ Imagen</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Modelo IA</label>
                        <select class="form-input" name="modelo" required>
                            <option value="claude">Claude 3</option>
                            <option value="gpt4">GPT-4</option>
                            <option value="gpt35">GPT-3.5</option>
                            <option value="gemini">Gemini</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contenido del Prompt</label>
                    <textarea class="form-textarea" name="contenido" id="prompt-text" required placeholder="Escribe aquí tu prompt detallado..." onkeyup="updateCount()"></textarea>
                    <div style="text-align:right; margin-top:8px; font-size:12px; color:var(--text3);">
                        <span id="char-count">0 caracteres</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Tags (separados por comas)</label>
                    <input class="form-input" type="text" name="tags" placeholder="Ej: python, api, testing">
                </div>

                <div style="display:flex; gap:10px; margin-top:30px;">
                    <button type="submit" class="btn-primary" style="flex:1; padding:12px;">
                        Publicar Prompt
                    </button>
                    <button type="button" class="btn-ghost" onclick="window.history.back()" style="flex:1; padding:12px;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>

        <div class="editor-preview">
            <h3>Vista Previa</h3>
            <div class="preview-box">
                <div style="padding:16px;">
                    <h4 style="color:var(--text); margin-bottom:8px;" id="preview-title">Tu título aquí</h4>
                    <p style="color:var(--text2); font-size:13px; white-space:pre-wrap;" id="preview-content">
                        El contenido aparecerá aquí...
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo RUTA_PUBLIC; ?>js/editor.js"></script>
</body>
</html>