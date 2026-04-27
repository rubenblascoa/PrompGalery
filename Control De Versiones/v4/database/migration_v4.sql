-- PromptVault — Migration v4
-- Run AFTER importing promptvault.sql AND migration_security.sql

-- ─── Colecciones ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `colecciones` (
  `id`           VARCHAR(36)  NOT NULL,
  `usuario_id`   VARCHAR(36)  NOT NULL,
  `nombre`       VARCHAR(100) NOT NULL,
  `descripcion`  TEXT         DEFAULT NULL,
  `privada`      TINYINT(1)   NOT NULL DEFAULT 0,
  `fecha_creacion`    TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `colecciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coleccion_prompts` (
  `id`           VARCHAR(36)  NOT NULL,
  `coleccion_id` VARCHAR(36)  NOT NULL,
  `prompt_id`    VARCHAR(36)  NOT NULL,
  `orden`        SMALLINT     NOT NULL DEFAULT 0,
  `fecha_agregado` TIMESTAMP  NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_col_prompt` (`coleccion_id`, `prompt_id`),
  KEY `idx_coleccion` (`coleccion_id`),
  KEY `idx_prompt`    (`prompt_id`),
  CONSTRAINT `cp_ibfk_1` FOREIGN KEY (`coleccion_id`) REFERENCES `colecciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cp_ibfk_2` FOREIGN KEY (`prompt_id`)    REFERENCES `prompts`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tokens de usuario (verificación de email + recuperación de contraseña) ──
CREATE TABLE IF NOT EXISTS `tokens_usuario` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id` VARCHAR(36)   NOT NULL,
  `token`      VARCHAR(64)   NOT NULL COMMENT 'SHA-256 hex or random_bytes hex',
  `tipo`       ENUM('verificar_email','recuperar_pass') NOT NULL,
  `expira`     DATETIME      NOT NULL,
  `usado`      TINYINT(1)    NOT NULL DEFAULT 0,
  `creado`     TIMESTAMP     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_usuario_tipo` (`usuario_id`, `tipo`),
  CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Columna para marcar hasta cuándo se leyeron notificaciones ──────────────
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `notif_leidas_hasta` TIMESTAMP NULL DEFAULT NULL;

-- ─── Event para limpiar login_intentos viejos (requiere Event Scheduler ON) ──
-- SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS purge_login_intentos
--   ON SCHEDULE EVERY 1 DAY
--   DO DELETE FROM login_intentos WHERE ultimo_intento < NOW() - INTERVAL 7 DAY;

-- ─── Event para limpiar tokens expirados ─────────────────────────────────────
-- CREATE EVENT IF NOT EXISTS purge_tokens_expirados
--   ON SCHEDULE EVERY 1 DAY
--   DO DELETE FROM tokens_usuario WHERE expira < NOW() - INTERVAL 1 DAY;
