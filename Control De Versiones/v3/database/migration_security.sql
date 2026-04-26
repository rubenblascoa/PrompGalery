-- PromptVault — Security Migration
-- Run AFTER importing the main promptvault.sql dump

-- Brute-force tracking (IP + email)
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45)  NOT NULL,
  `email_hash`   VARCHAR(64)  NOT NULL COMMENT 'SHA-256 of email, never plain',
  `bloqueado_hasta` DATETIME  DEFAULT NULL,
  `intentos`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `ultimo_intento` TIMESTAMP NOT NULL DEFAULT current_timestamp()
                             ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ip_email` (`ip`, `email_hash`),
  KEY `idx_ip`    (`ip`),
  KEY `idx_email` (`email_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event to auto-purge old records (optional, needs Event Scheduler ON)
-- DELIMITER $$
-- CREATE EVENT IF NOT EXISTS purge_login_intentos
-- ON SCHEDULE EVERY 1 DAY
-- DO DELETE FROM login_intentos WHERE ultimo_intento < NOW() - INTERVAL 7 DAY;
-- $$
-- DELIMITER ;
