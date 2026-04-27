<?php
class UsuarioModelo {
    private $db;

    public function __construct() {
        $this->db = new Base;
    }

    // ─── REGISTRO Y AUTENTICACIÓN ────────────────────────────────────────────
    public function registrar($datos) {
        $id     = 'user_' . bin2hex(random_bytes(8));
        $avatar = generarAvatar($datos['nombre']);
        $hash   = password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db->query("INSERT INTO usuarios (id, nombre, email, contraseña, avatar, bio, verificado, activo)
                          VALUES (:id, :nombre, :email, :pass, :avatar, '', 0, 1)");
        $this->db->bind(':id',     $id);
        $this->db->bind(':nombre', $datos['nombre']);
        $this->db->bind(':email',  $datos['email']);
        $this->db->bind(':pass',   $hash);
        $this->db->bind(':avatar', $avatar);
        return $this->db->execute() ? $id : false;
    }

    public function login($email, $password) {
        $this->db->query("SELECT * FROM usuarios WHERE email = :email AND activo = 1 LIMIT 1");
        $this->db->bind(':email', $email);
        $usuario = $this->db->registro();
        if (!$usuario) return false;
        if (!password_verify($password, $usuario->contraseña)) return false;
        return $usuario;
    }

    public function existeEmail($email) {
        $this->db->query("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
        $this->db->bind(':email', $email);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function existeNombre($nombre) {
        $this->db->query("SELECT id FROM usuarios WHERE nombre = :nombre LIMIT 1");
        $this->db->bind(':nombre', $nombre);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // ─── BRUTE-FORCE (BD) ────────────────────────────────────────────────────
    /**
     * Devuelve timestamp UNIX de "bloqueado hasta" si está bloqueado, o false.
     */
    public function obtenerBloqueoLogin($ip, $email) {
        $emailHash = hash('sha256', strtolower(trim($email)));
        $this->db->query("SELECT bloqueado_hasta, intentos FROM login_intentos
                          WHERE ip = :ip AND email_hash = :eh LIMIT 1");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':eh', $emailHash);
        $row = $this->db->registro();
        if (!$row) return false;
        if ($row->bloqueado_hasta && strtotime($row->bloqueado_hasta) > time()) {
            return strtotime($row->bloqueado_hasta);
        }
        return false;
    }

    /**
     * Incrementa intentos fallidos. Devuelve true si se bloqueó ahora.
     */
    public function registrarIntentoLogin($ip, $email) {
        $emailHash = hash('sha256', strtolower(trim($email)));

        // Upsert: insertar o actualizar
        $this->db->query("INSERT INTO login_intentos (ip, email_hash, intentos, ultimo_intento)
                          VALUES (:ip, :eh, 1, NOW())
                          ON DUPLICATE KEY UPDATE
                            intentos = IF(bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW(),
                                          intentos,
                                          intentos + 1),
                            ultimo_intento = NOW(),
                            bloqueado_hasta = NULL");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':eh', $emailHash);
        $this->db->execute();

        // Comprobar si hay que bloquear
        $this->db->query("SELECT intentos FROM login_intentos WHERE ip = :ip AND email_hash = :eh LIMIT 1");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':eh', $emailHash);
        $row = $this->db->registro();

        if ($row && $row->intentos >= MAX_LOGIN_ATTEMPTS) {
            $hasta = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            $this->db->query("UPDATE login_intentos SET bloqueado_hasta = :hasta
                              WHERE ip = :ip AND email_hash = :eh");
            $this->db->bind(':hasta', $hasta);
            $this->db->bind(':ip',    $ip);
            $this->db->bind(':eh',    $emailHash);
            $this->db->execute();
            return true;
        }
        return false;
    }

    public function resetearIntentosLogin($ip, $email) {
        $emailHash = hash('sha256', strtolower(trim($email)));
        $this->db->query("DELETE FROM login_intentos WHERE ip = :ip AND email_hash = :eh");
        $this->db->bind(':ip', $ip);
        $this->db->bind(':eh', $emailHash);
        $this->db->execute();
    }

    // ─── OBTENER USUARIOS ────────────────────────────────────────────────────
    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM usuarios_estadisticas WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->registro();
    }

    public function obtenerPerfil($id) {
        $this->db->query("SELECT u.*, ue.num_prompts, ue.num_seguidores, ue.num_siguiendo
                          FROM usuarios u
                          JOIN usuarios_estadisticas ue ON u.id = ue.id
                          WHERE u.id = :id AND u.activo = 1");
        $this->db->bind(':id', $id);
        return $this->db->registro();
    }

    public function obtenerEstadisticasPropias($id) {
        $this->db->query("SELECT num_prompts, num_seguidores, num_siguiendo
                          FROM usuarios_estadisticas WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->registro();
    }

    public function obtenerLeaderboard($modo = 'prompts', $limite = 20) {
        $order = match($modo) {
            'seguidores' => 'num_seguidores DESC',
            default      => 'num_prompts DESC',
        };
        $this->db->query("SELECT * FROM usuarios_estadisticas ORDER BY $order LIMIT :lim");
        $this->db->bind(':lim', $limite, PDO::PARAM_INT);
        return $this->db->registros();
    }

    // ─── EDITAR PERFIL ───────────────────────────────────────────────────────
    public function actualizarPerfil($id, $datos) {
        $this->db->query("UPDATE usuarios SET bio = :bio, ciudad = :ciudad, sitio_web = :web,
                          fecha_actualizacion = NOW()
                          WHERE id = :id");
        $this->db->bind(':bio',    $datos['bio']);
        $this->db->bind(':ciudad', $datos['ciudad']);
        $this->db->bind(':web',    $datos['web']);
        $this->db->bind(':id',     $id);
        return $this->db->execute();
    }

    public function actualizarAvatar($id, $url) {
        $this->db->query("UPDATE usuarios SET avatar = :avatar, fecha_actualizacion = NOW() WHERE id = :id");
        $this->db->bind(':avatar', $url);
        $this->db->bind(':id',     $id);
        return $this->db->execute();
    }

    public function cambiarPassword($id, $nuevaPass) {
        $hash = password_hash($nuevaPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->query("UPDATE usuarios SET contraseña = :pass WHERE id = :id");
        $this->db->bind(':pass', $hash);
        $this->db->bind(':id',   $id);
        return $this->db->execute();
    }

    public function verificarPasswordActual($id, $pass) {
        $this->db->query("SELECT contraseña FROM usuarios WHERE id = :id");
        $this->db->bind(':id', $id);
        $u = $this->db->registro();
        return $u && password_verify($pass, $u->contraseña);
    }

    // ─── SEGUIDORES ──────────────────────────────────────────────────────────
    public function toggleSeguir($usuario_id, $seguido_id) {
        if ($usuario_id === $seguido_id) return null;

        $this->db->query("SELECT id FROM seguidores WHERE usuario_id = :uid AND usuario_seguido_id = :sid");
        $this->db->bind(':uid', $usuario_id);
        $this->db->bind(':sid', $seguido_id);
        $existe = $this->db->registro();

        if ($existe) {
            $this->db->query("DELETE FROM seguidores WHERE usuario_id = :uid AND usuario_seguido_id = :sid");
            $this->db->bind(':uid', $usuario_id);
            $this->db->bind(':sid', $seguido_id);
            $this->db->execute();
            return false;
        } else {
            $this->db->query("INSERT INTO seguidores (id, usuario_id, usuario_seguido_id) VALUES (UUID(), :uid, :sid)");
            $this->db->bind(':uid', $usuario_id);
            $this->db->bind(':sid', $seguido_id);
            $this->db->execute();
            return true;
        }
    }

    public function estaSiguiendo($usuario_id, $seguido_id) {
        $this->db->query("SELECT id FROM seguidores WHERE usuario_id = :uid AND usuario_seguido_id = :sid");
        $this->db->bind(':uid', $usuario_id);
        $this->db->bind(':sid', $seguido_id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function contarSeguidores($usuario_id) {
        $this->db->query("SELECT COUNT(*) FROM seguidores WHERE usuario_seguido_id = :uid");
        $this->db->bind(':uid', $usuario_id);
        return $this->db->contarRegistros();
    }

    // ─── NOTIFICACIONES ─────────────────────────────────────────────────────
    public function obtenerNotificaciones($usuario_id, $limite = 20) {
        $this->db->query("
            (SELECT 'voto' AS tipo,
                    v.fecha_creacion AS fecha,
                    u.nombre AS actor_nombre,
                    u.avatar AS actor_avatar,
                    p.id AS ref_id,
                    p.titulo AS ref_titulo
             FROM votos v
             JOIN prompts p ON v.prompt_id = p.id
             JOIN usuarios u ON v.usuario_id = u.id
             WHERE p.usuario_id = :uid1 AND v.usuario_id != :uid2 AND v.tipo = 'positivo'
            )
            UNION ALL
            (SELECT 'comentario' AS tipo,
                    c.fecha_creacion AS fecha,
                    u.nombre AS actor_nombre,
                    u.avatar AS actor_avatar,
                    p.id AS ref_id,
                    p.titulo AS ref_titulo
             FROM comentarios c
             JOIN prompts p ON c.prompt_id = p.id
             JOIN usuarios u ON c.usuario_id = u.id
             WHERE p.usuario_id = :uid3 AND c.usuario_id != :uid4 AND c.activo = 1
            )
            UNION ALL
            (SELECT 'seguidor' AS tipo,
                    s.fecha_creacion AS fecha,
                    u.nombre AS actor_nombre,
                    u.avatar AS actor_avatar,
                    u.id AS ref_id,
                    '' AS ref_titulo
             FROM seguidores s
             JOIN usuarios u ON s.usuario_id = u.id
             WHERE s.usuario_seguido_id = :uid5
            )
            ORDER BY fecha DESC
            LIMIT :lim
        ");
        $this->db->bind(':uid1', $usuario_id);
        $this->db->bind(':uid2', $usuario_id);
        $this->db->bind(':uid3', $usuario_id);
        $this->db->bind(':uid4', $usuario_id);
        $this->db->bind(':uid5', $usuario_id);
        $this->db->bind(':lim',  $limite, PDO::PARAM_INT);
        return $this->db->registros();
    }
    // ─── TOKENS (verificación email / recuperar contraseña) ─────────────────
    public function crearToken($usuario_id, $tipo) {
        // Invalida tokens anteriores del mismo tipo
        $this->db->query("UPDATE tokens_usuario SET usado = 1 WHERE usuario_id = :uid AND tipo = :tipo AND usado = 0");
        $this->db->bind(':uid',  $usuario_id);
        $this->db->bind(':tipo', $tipo);
        $this->db->execute();

        $token = bin2hex(random_bytes(32));
        $horas = ($tipo === 'recuperar_pass') ? (TOKEN_RECUPERAR_MINUTOS / 60) : TOKEN_VERIFICAR_HORAS;
        $expira = date('Y-m-d H:i:s', time() + ($horas * 3600));

        $this->db->query("INSERT INTO tokens_usuario (usuario_id, token, tipo, expira) VALUES (:uid, :tok, :tipo, :exp)");
        $this->db->bind(':uid',  $usuario_id);
        $this->db->bind(':tok',  $token);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':exp',  $expira);
        return $this->db->execute() ? $token : false;
    }

    public function validarToken($token, $tipo) {
        $this->db->query("SELECT t.*, u.id AS uid, u.nombre, u.email
                          FROM tokens_usuario t
                          JOIN usuarios u ON t.usuario_id = u.id
                          WHERE t.token = :tok AND t.tipo = :tipo
                            AND t.usado = 0 AND t.expira > NOW()
                          LIMIT 1");
        $this->db->bind(':tok',  $token);
        $this->db->bind(':tipo', $tipo);
        return $this->db->registro();
    }

    public function consumirToken($token) {
        $this->db->query("UPDATE tokens_usuario SET usado = 1 WHERE token = :tok");
        $this->db->bind(':tok', $token);
        return $this->db->execute();
    }

    public function verificarEmail($usuario_id) {
        $this->db->query("UPDATE usuarios SET verificado = 1, fecha_actualizacion = NOW() WHERE id = :id");
        $this->db->bind(':id', $usuario_id);
        return $this->db->execute();
    }

    public function obtenerPorEmail($email) {
        $this->db->query("SELECT * FROM usuarios WHERE email = :email AND activo = 1 LIMIT 1");
        $this->db->bind(':email', $email);
        return $this->db->registro();
    }

    // ─── NOTIFICACIONES — CONTEO REAL ───────────────────────────────────────
    public function contarNotifNuevas($usuario_id) {
        $this->db->query("SELECT notif_leidas_hasta FROM usuarios WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $usuario_id);
        $row = $this->db->registro();
        $desde = $row->notif_leidas_hasta ?? '2000-01-01 00:00:00';

        // Cuenta votos + comentarios + seguidores DESPUÉS de la última lectura
        $this->db->query("
            SELECT (
                (SELECT COUNT(*) FROM votos v
                 JOIN prompts p ON v.prompt_id = p.id
                 WHERE p.usuario_id = :uid1 AND v.usuario_id != :uid2
                   AND v.tipo = 'positivo' AND v.fecha_creacion > :d1)
              + (SELECT COUNT(*) FROM comentarios c
                 JOIN prompts p ON c.prompt_id = p.id
                 WHERE p.usuario_id = :uid3 AND c.usuario_id != :uid4
                   AND c.activo = 1 AND c.fecha_creacion > :d2)
              + (SELECT COUNT(*) FROM seguidores s
                 WHERE s.usuario_seguido_id = :uid5
                   AND s.fecha_creacion > :d3)
            ) AS total
        ");
        $this->db->bind(':uid1', $usuario_id);
        $this->db->bind(':uid2', $usuario_id);
        $this->db->bind(':uid3', $usuario_id);
        $this->db->bind(':uid4', $usuario_id);
        $this->db->bind(':uid5', $usuario_id);
        $this->db->bind(':d1',   $desde);
        $this->db->bind(':d2',   $desde);
        $this->db->bind(':d3',   $desde);
        $row = $this->db->registro();
        return (int)($row->total ?? 0);
    }

    public function marcarNotifLeidas($usuario_id) {
        $this->db->query("UPDATE usuarios SET notif_leidas_hasta = NOW() WHERE id = :id");
        $this->db->bind(':id', $usuario_id);
        return $this->db->execute();
    }

    // ─── ACTIVIDAD DEL USUARIO ───────────────────────────────────────────────
    public function obtenerActividad($usuario_id, $limite = 30) {
        $this->db->query("
            (SELECT 'prompt_creado' AS tipo,
                    p.fecha_creacion AS fecha,
                    p.id AS ref_id,
                    p.titulo AS ref_titulo,
                    p.categoria AS extra,
                    NULL AS actor_nombre
             FROM prompts p
             WHERE p.usuario_id = :uid1 AND p.activo = 1
            )
            UNION ALL
            (SELECT 'comentario_dado' AS tipo,
                    c.fecha_creacion AS fecha,
                    p.id AS ref_id,
                    p.titulo AS ref_titulo,
                    SUBSTRING(c.contenido, 1, 80) AS extra,
                    NULL AS actor_nombre
             FROM comentarios c
             JOIN prompts p ON c.prompt_id = p.id
             WHERE c.usuario_id = :uid2 AND c.activo = 1
            )
            UNION ALL
            (SELECT 'voto_dado' AS tipo,
                    v.fecha_creacion AS fecha,
                    p.id AS ref_id,
                    p.titulo AS ref_titulo,
                    v.tipo AS extra,
                    u.nombre AS actor_nombre
             FROM votos v
             JOIN prompts p ON v.prompt_id = p.id
             JOIN usuarios u ON p.usuario_id = u.id
             WHERE v.usuario_id = :uid3 AND v.tipo = 'positivo'
            )
            ORDER BY fecha DESC
            LIMIT :lim
        ");
        $this->db->bind(':uid1', $usuario_id);
        $this->db->bind(':uid2', $usuario_id);
        $this->db->bind(':uid3', $usuario_id);
        $this->db->bind(':lim',  $limite, PDO::PARAM_INT);
        return $this->db->registros();
    }
}
