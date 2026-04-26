<!-- ═══════════════════ MODAL OVERLAY ═══════════════════ -->
<div class="modal-overlay" id="modal-overlay" onclick="closeModal(event)">

    <!-- NUEVO PROMPT -->
    <div class="modal modal-lg" id="modal-newprompt" style="display:none">
        <div class="modal-close" onclick="closeModal({target:document.getElementById('modal-overlay')})">✕</div>
        <div class="modal-title">✨ Publicar nuevo prompt</div>
        <div class="modal-sub">Comparte tu prompt con la comunidad y ayuda a otros</div>

        <div id="newprompt-form">
            <div class="form-group">
                <label class="form-label">Título <span class="req">*</span></label>
                <input class="form-input" type="text" id="np-titulo" placeholder="Ej: Senior Code Reviewer con principios SOLID" maxlength="200">
                <div class="field-hint" id="np-titulo-hint">0 / 200</div>
            </div>
            <div class="form-group">
                <label class="form-label">El Prompt <span class="req">*</span></label>
                <textarea class="form-textarea" id="np-contenido" rows="7"
                    placeholder="Escribe tu prompt aquí. Sé específico y detallado para obtener mejores resultados..."
                    oninput="updateCharCount(this,'np-char-count',10000)"></textarea>
                <div class="char-count" id="np-char-count">0 / 10,000</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Categoría <span class="req">*</span></label>
                    <select class="form-select" id="np-categoria">
                        <option value="">Seleccionar...</option>
                        <option value="codigo">💻 Código</option>
                        <option value="escritura">✍️ Escritura</option>
                        <option value="analisis">📊 Análisis</option>
                        <option value="imagen">🎨 Imagen</option>
                        <option value="chatbot">💬 Chatbot</option>
                        <option value="razonamiento">🧠 Razonamiento</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Modelo IA</label>
                    <select class="form-select" id="np-modelo">
                        <option value="claude">Claude</option>
                        <option value="gpt-4">GPT-4</option>
                        <option value="gpt-4o">GPT-4o</option>
                        <option value="gemini">Gemini</option>
                        <option value="midjourney">Midjourney</option>
                        <option value="universal">Universal</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tags <span class="form-hint">(separados por comas, máx. 10)</span></label>
                <input class="form-input" type="text" id="np-tags" placeholder="ej: python, review, SOLID, seguridad">
            </div>
            <div class="modal-footer">
                <button class="btn-ghost" onclick="closeModal({target:document.getElementById('modal-overlay')})">Cancelar</button>
                <button class="btn-primary" onclick="publicarPrompt()">🚀 Publicar prompt</button>
            </div>
        </div>
    </div>

    <!-- VER DETALLE PROMPT -->
    <div class="modal modal-xl" id="modal-view" style="display:none">
        <div class="modal-close" onclick="closeModal({target:document.getElementById('modal-overlay')})">✕</div>
        <div id="view-contenido">
            <div style="text-align:center;padding:40px;color:var(--text3)">
                <div style="font-size:32px">⏳</div>
                <div style="margin-top:12px">Cargando...</div>
            </div>
        </div>
    </div>

    <!-- AUTH -->
    <div class="modal" id="modal-auth" style="display:none;max-width:440px">
        <div class="modal-close" onclick="closeModal({target:document.getElementById('modal-overlay')})">✕</div>

        <div id="auth-tabs" class="auth-tab-row">
            <button class="auth-tab active" onclick="switchAuthTab('login',this)">Iniciar sesión</button>
            <button class="auth-tab" onclick="switchAuthTab('registro',this)">Crear cuenta</button>
        </div>

        <!-- LOGIN -->
        <div id="auth-login">
            <div style="margin:22px 0 18px;text-align:center">
                <div class="modal-title" style="margin-bottom:4px">Bienvenido de nuevo</div>
                <div class="modal-sub">Accede a tu cuenta de PromptVault</div>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" type="email" id="login-email" placeholder="tu@email.com" autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <div class="pass-wrap">
                    <input class="form-input" type="password" id="login-pass" placeholder="••••••••" autocomplete="current-password">
                    <button class="pass-toggle" onclick="togglePass('login-pass',this)">👁</button>
                </div>
            </div>
            <button class="btn-primary" style="width:100%;margin-top:16px" onclick="doLogin()">
                Iniciar sesión
            </button>
            <div id="login-error" class="form-error" style="display:none"></div>
        </div>

        <!-- REGISTRO -->
        <div id="auth-registro" style="display:none">
            <div style="margin:22px 0 18px;text-align:center">
                <div class="modal-title" style="margin-bottom:4px">Únete a PromptVault</div>
                <div class="modal-sub">Crea tu cuenta gratis en segundos</div>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre de usuario</label>
                <input class="form-input" type="text" id="reg-nombre" placeholder="mi_username" autocomplete="username"
                       oninput="validateUsername(this)">
                <div class="field-hint" id="reg-nombre-hint">Solo letras, números y guiones bajos</div>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" type="email" id="reg-email" placeholder="tu@email.com" autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <div class="pass-wrap">
                    <input class="form-input" type="password" id="reg-pass" placeholder="Mínimo 8 caracteres"
                           oninput="updatePassStrength(this)">
                    <button class="pass-toggle" onclick="togglePass('reg-pass',this)">👁</button>
                </div>
                <div class="pass-strength" id="pass-strength"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar contraseña</label>
                <input class="form-input" type="password" id="reg-pass-confirm" placeholder="Repite tu contraseña">
            </div>
            <button class="btn-primary" style="width:100%;margin-top:16px" onclick="doRegistro()">
                🚀 Crear cuenta gratis
            </button>
            <div id="reg-error" class="form-error" style="display:none"></div>
        </div>
    </div>

    <!-- EDITAR PERFIL -->
    <div class="modal" id="modal-editperfil" style="display:none;max-width:480px">
        <div class="modal-close" onclick="closeModal({target:document.getElementById('modal-overlay')})">✕</div>
        <div class="modal-title">⚙️ Configuración</div>

        <div class="tab-row" id="config-tabs">
            <div class="tab active" onclick="switchConfigTab('perfil',this)">Perfil</div>
            <div class="tab" onclick="switchConfigTab('seguridad',this)">Seguridad</div>
        </div>

        <div id="config-perfil">
            <!-- Avatar upload -->
            <div class="form-group" style="text-align:center;margin-bottom:20px">
                <div id="avatar-preview-wrap" style="position:relative;display:inline-block;cursor:pointer" onclick="document.getElementById('avatar-file-input').click()">
                    <img id="avatar-preview-img"
                         src="<?php echo htmlspecialchars(usuarioActual()['avatar'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Avatar"
                         style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--border)">
                    <div style="position:absolute;bottom:0;right:0;background:var(--accent);color:#000;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">📷</div>
                </div>
                <div style="font-size:11px;color:var(--text3);margin-top:6px">JPG, PNG, WebP · máx. 2 MB</div>
                <input type="file" id="avatar-file-input" accept="image/jpeg,image/png,image/webp,image/gif"
                       style="display:none" onchange="previewAndUploadAvatar(this)">
                <div id="avatar-upload-status" style="font-size:12px;margin-top:4px"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Bio</label>
                <textarea class="form-textarea" id="edit-bio" rows="3"
                    placeholder="Cuéntanos sobre ti..." maxlength="300"
                    oninput="updateCharCount(this,'edit-bio-count',300)"></textarea>
                <div class="char-count" id="edit-bio-count">0 / 300</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Ciudad</label>
                    <input class="form-input" type="text" id="edit-ciudad" placeholder="Barcelona" maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Sitio web</label>
                    <input class="form-input" type="url" id="edit-web" placeholder="https://tu-web.com" maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-ghost" onclick="closeModal({target:document.getElementById('modal-overlay')})">Cancelar</button>
                <button class="btn-primary" onclick="guardarPerfil()">Guardar cambios</button>
            </div>
        </div>

        <div id="config-seguridad" style="display:none">
            <div class="form-group">
                <label class="form-label">Contraseña actual</label>
                <input class="form-input" type="password" id="pass-actual" placeholder="Tu contraseña actual">
            </div>
            <div class="form-group">
                <label class="form-label">Nueva contraseña</label>
                <input class="form-input" type="password" id="nueva-pass" placeholder="Mínimo 8 caracteres"
                       oninput="updatePassStrength(this)">
                <div class="pass-strength" id="pass-strength-2"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar nueva contraseña</label>
                <input class="form-input" type="password" id="confirmar-pass" placeholder="Repite la nueva contraseña">
            </div>
            <div class="modal-footer">
                <button class="btn-ghost" onclick="closeModal({target:document.getElementById('modal-overlay')})">Cancelar</button>
                <button class="btn-primary" onclick="cambiarPassword()">Actualizar contraseña</button>
            </div>
        </div>
    </div>

</div><!-- /modal-overlay -->

<script src="<?php echo RUTA_PUBLIC; ?>js/main.js?v=<?php echo VERSION; ?>"></script>
</body>
</html>
