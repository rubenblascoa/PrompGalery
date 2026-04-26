<?php
class UsuarioModelo {
    private $db;

    public function __construct() {
        $this->db = new Base;
    }

    // ─── REGISTRO Y AUTENTICACIÓN ────────────────────────────────────────────
    public function registrar($datos) {
        $id = 'user_' . bin2hex(random_bytes(8));
        $avatar = generarAvatar($datos['nombre']);
        $hash = password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => 12]);

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

    public function cambiarPassword($id, $nuevaPass) {
        $hash = password_hash($nuevaPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->query("UPDATE usuarios SET contraseña = :pass WHERE id = :id");
        $this->db->bind(':pass', $hash);
        $this->db->bind(':id', $id);
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
        // Notificaciones: votos positivos en mis prompts + comentarios en mis prompts + nuevos seguidores
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
        $this->db->bind(':lim', $limite, PDO::PARAM_INT);
        return $this->db->registros();
    }
}
