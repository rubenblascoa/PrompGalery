# PromptVault v4.0.0 — Changelog

## Nuevas funcionalidades

### ✅ Recuperación de contraseña
- Controlador `Recuperar.php` con flujo completo: solicitar → email con token → reset
- Vistas `/recuperar`, `/recuperar/reset/:token`, `/recuperar/mensaje`
- Enlace "¿Olvidaste tu contraseña?" en el modal de login
- Token expira en 60 minutos (configurable en `configurar.php`)

### ✅ Verificación de email
- Al registrarse se envía email de confirmación con token de 24h
- Endpoint `GET /auth/verificar/:token` activa el campo `verificado = 1`
- Badge ⚠️ en el nav si el usuario no ha verificado (con botón para reenviar)
- Endpoint `POST /auth/reenviar_verificacion`
- Tabla `tokens_usuario` en BD (ver `migration_v4.sql`)

### ✅ Vista /explorar propia (SEO-indexable)
- Controlador `Explorar.php` con URL propia `/explorar`
- Filtros de categoría, modelo y orden funcionan con GET params → indexables
- Paginación con URLs limpias
- Meta description y canonical dinámicos por filtro
- Ya no es solo una sección SPA sin URL

### ✅ Colecciones completas
- Tabla `colecciones` + `coleccion_prompts` en BD
- CRUD completo: crear, ver, eliminar colecciones, agregar/quitar prompts
- Vista `/colecciones` con modales
- Controlador `Colecciones.php` + `ColeccionModelo.php`
- Integrado en nav y menú de usuario

### ✅ Actividad del perfil (pestaña real)
- Endpoint `GET /perfil/actividad` retorna historial real: prompts creados, comentarios dados, votos
- La pestaña "Actividad" en el perfil ya no dice "Próximamente"

### ✅ Notificaciones dinámicas
- Badge en nav muestra conteo REAL desde BD (votos + comentarios + seguidores desde `notif_leidas_hasta`)
- Al abrir el panel → se marcan como leídas (endpoint `POST /perfil/marcar_notif_leidas`)
- Columna `notif_leidas_hasta` en tabla `usuarios`

## Bugs corregidos

### 🐛 Badge de notificaciones fijo en "3"
- Ahora se carga vía `GET /perfil/notif_count` al iniciar la página

### 🐛 Modal editar perfil con PV_PERFIL null
- Si el usuario no ha visitado su perfil en la sesión, el modal ahora hace fetch a `/perfil/stats` automáticamente antes de rellenar los campos

### 🐛 Colecciones "Próximamente" en pestaña de perfil
- La pestaña ahora redirige a `/colecciones` (URL propia)

### 🐛 handleNavSearch sin estado vacío ni paginación
- Ahora muestra "Ver los N resultados →" con link a `/explorar?q=...`
- Estado vacío "Sin resultados para X" correctamente manejado

### 🐛 goPage('colecciones') no tenía URL real
- Ahora redirige a `/colecciones` en lugar de mostrar la SPA estática

## Seguridad

### 🔐 Logs por categoría
- `auth.log`, `seguridad.log`, `mail.log`, `errores.log`, `app.log`
- Función `logApp($mensaje, $categoria, $nivel)`

### 🔐 CSP actualizado
- Añadido `connect-src https://api.anthropic.com` (para el Tester de la SPA)

### 🔐 HSTS — guía clara
- `.htaccess` tiene la línea comentada con instrucción explícita de cuándo activarla

### 🔐 Directorio /logs protegido
- `.htaccess` con `Deny from all` en `/logs/`

## Producción

### 🚀 Favicon y og:image
- `/public/img/favicon.svg` (SVG, funciona en todos los navegadores modernos)
- `/public/img/favicon.ico` (fallback legacy)
- `/public/img/og-image.png/svg` (1200×630 para redes sociales)
- Header incluye todas las meta tags og: y twitter:

---

## Instalación v4

1. Importa `database/promptvault.sql`
2. Importa `database/migration_security.sql`
3. Importa `database/migration_v4.sql`
4. Edita `App/config/configurar.php` → ajusta `SITE_URL`, `MAIL_FROM`, credenciales BD
5. Asegúrate de que `logs/` sea escribible: `chmod 755 logs/`
6. Para envío de emails con servidor de correo real, implementa PHPMailer en `enviarEmail()` en `funciones.php`
7. Cuando tengas HTTPS, descomenta la línea HSTS en `public/.htaccess`

## Requisitos
- PHP 8.0+
- MariaDB 10.4+ / MySQL 8.0+
- Apache con mod_rewrite
- (Opcional) Event Scheduler activado para limpieza automática de tokens
