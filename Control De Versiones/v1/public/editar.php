<?php
// Vista para editar prompt
if (!estaAutenticado() || !isset($datos['prompt'])) {
    redirigir('prompts');
}
$prompt = $datos['prompt'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Prompt - PromptVault</title>
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
            <h1>Editar Prompt</h1>

            <form method="POST" class="prompt-form">
                <div class="form-group">
                    <label class="form-label">Título del Prompt</label>
                    <input class="form-input" type="text" name="titulo" required placeholder="Ej: Senior Code Reviewer" maxlength="100" value="<?php echo escapar($prompt->titulo); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Categoría</label>
                        <select class="form-input" name="categoria" required>
                            <option value="chat" <?php echo $prompt->categoria == 'chat' ? 'selected' : ''; ?>>💬 Chat</option>
                            <option value="codigo" <?php echo $prompt->categoria == 'codigo' ? 'selected' : ''; ?>>⌨️ Código</option>
                            <option value="escritura" <?php echo $prompt->categoria == 'escritura' ? 'selected' : ''; ?>>✍️ Escritura</option>
                            <option value="analisis" <?php echo $prompt->categoria == 'analisis' ? 'selected' : ''; ?>>📊 Análisis</option>
                            <option value="imagen" <?php echo $prompt->categoria == 'imagen' ? 'selected' : ''; ?>>🖼️ Imagen</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Modelo IA</label>
                        <select class="form-input" name="modelo" required>
                            <option value="claude" <?php echo $prompt->modelo == 'claude' ? 'selected' : ''; ?>>Claude 3</option>
                            <option value="gpt4" <?php echo $prompt->modelo == 'gpt4' ? 'selected' : ''; ?>>GPT-4</option>
                            <option value="gpt35" <?php echo $prompt->modelo == 'gpt35' ? 'selected' : ''; ?>>GPT-3.5</option>
                            <option value="gemini" <?php echo $prompt->modelo == 'gemini' ? 'selected' : ''; ?>>Gemini</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contenido del Prompt</label>
                    <textarea class="form-textarea" name="contenido" id="prompt-text" required placeholder="Escribe aquí tu prompt detallado..." onkeyup="updateCount()"><?php echo escapar($prompt->contenido); ?></textarea>
                    <div style="text-align:right; margin-top:8px; font-size:12px; color:var(--text3);">
                        <span id="char-count"><?php echo strlen($prompt->contenido); ?> caracteres</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Tags (separados por comas)</label>
                    <input class="form-input" type="text" name="tags" placeholder="Ej: python, api, testing" value="<?php echo escapar($prompt->tags); ?>">
                </div>

                <div style="display:flex; gap:10px; margin-top:30px;">
                    <button type="submit" class="btn-primary" style="flex:1; padding:12px;">
                        Guardar Cambios
                    </button>
                    <button type="button" class="btn-ghost" onclick="window.history.back()" style="flex:1; padding:12px;">
                        Cancelar
                    </button>
                    <button type="button" class="btn-ghost" style="padding:12px; color:var(--coral);" onclick="if(confirm('¿Eliminar este prompt?')) window.location.href='<?php echo RUTA_URL; ?>prompts/eliminar/<?php echo escapar($prompt->id); ?>'">
                        Eliminar
                    </button>
                </div>
            </form>
        </div>

        <div class="editor-preview">
            <h3>Vista Previa</h3>
            <div class="preview-box">
                <div style="padding:16px;">
                    <h4 style="color:var(--text); margin-bottom:8px;" id="preview-title"><?php echo escapar($prompt->titulo); ?></h4>
                    <p style="color:var(--text2); font-size:13px; white-space:pre-wrap;" id="preview-content">
                        <?php echo substr(escapar($prompt->contenido), 0, 200); ?>...
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo RUTA_PUBLIC; ?>js/editor.js"></script>
</body>
</html>