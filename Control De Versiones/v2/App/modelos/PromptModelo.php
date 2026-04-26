<?php
class PromptModelo {
    private $db;

    public function __construct() {
        $this->db = new Base;
    }

    // ─── FEED PRINCIPAL ─────────────────────────────────────────────────────
    public function obtenerPrompts($orden = 'reciente', $categoria = '', $pagina = 1) {
        $offset = ($pagina - 1) * TAM_PAGINA;
        $orderBy = match($orden) {
            'top'    => 'votos_pos DESC',
            'hot'    => '(votos_pos * 2 - TIMESTAMPDIFF(HOUR, p.fecha_creacion, NOW())) DESC',
            default  => 'p.fecha_creacion DESC',
        };

        $where = 'WHERE p.activo = 1';
        if ($categoria) {
            $where .= ' AND p.categoria = :categoria';
        }

        $sql = "SELECT p.id, p.titulo, p.contenido, p.categoria, p.tags, p.modelo,
                       p.fecha_creacion, p.usuario_id,
                       u.nombre AS usuario_nombre, u.avatar, u.verificado,
                       (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'positivo') AS votos_pos,
                       (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'negativo') AS votos_neg,
                       (SELECT COUNT(*) FROM comentarios WHERE prompt_id = p.id AND activo = 1) AS num_comentarios,
                       (SELECT COUNT(*) FROM favoritos WHERE prompt_id = p.id) AS num_favoritos
                FROM prompts p
                JOIN usuarios u ON p.usuario_id = u.id
                $where
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        if ($categoria) $this->db->bind(':categoria', $categoria);
        $this->db->bind(':limit', TAM_PAGINA, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->registros();
    }

    public function contarPrompts($categoria = '') {
        $where = 'WHERE activo = 1';
        if ($categoria) $where .= ' AND categoria = :categoria';
        $this->db->query("SELECT COUNT(*) FROM prompts $where");
        if ($categoria) $this->db->bind(':categoria', $categoria);
        return $this->db->contarRegistros();
    }

    // ─── DETALLE DE PROMPT ──────────────────────────────────────────────────
    public function obtenerPromptPorId($id) {
        $this->db->query("SELECT p.*, u.nombre AS usuario_nombre, u.avatar, u.verificado, u.bio,
                         (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'positivo') AS votos_pos,
                         (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'negativo') AS votos_neg,
                         (SELECT COUNT(*) FROM comentarios WHERE prompt_id = p.id AND activo = 1) AS num_comentarios,
                         (SELECT COUNT(*) FROM favoritos WHERE prompt_id = p.id) AS num_favoritos,
                         (SELECT COUNT(*) FROM seguidores WHERE usuario_seguido_id = p.usuario_id) AS seguidores_autor
                         FROM prompts p
                         JOIN usuarios u ON p.usuario_id = u.id
                         WHERE p.id = :id AND p.activo = 1");
        $this->db->bind(':id', $id);
        return $this->db->registro();
    }

    // ─── CREAR / EDITAR / ELIMINAR ──────────────────────────────────────────
    public function agregarPrompt($datos) {
        $this->db->query("INSERT INTO prompts (id, usuario_id, titulo, contenido, categoria, modelo, tags)
                          VALUES (UUID(), :usuario_id, :titulo, :contenido, :categoria, :modelo, :tags)");
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':titulo',     $datos['titulo']);
        $this->db->bind(':contenido',  $datos['contenido']);
        $this->db->bind(':categoria',  $datos['categoria']);
        $this->db->bind(':modelo',     $datos['modelo']);
        $this->db->bind(':tags',       $datos['tags']);
        return $this->db->execute();
    }

    public function editarPrompt($datos) {
        $this->db->query("UPDATE prompts SET titulo = :titulo, contenido = :contenido,
                          categoria = :categoria, modelo = :modelo, tags = :tags,
                          fecha_actualizacion = NOW()
                          WHERE id = :id AND usuario_id = :usuario_id AND activo = 1");
        $this->db->bind(':titulo',     $datos['titulo']);
        $this->db->bind(':contenido',  $datos['contenido']);
        $this->db->bind(':categoria',  $datos['categoria']);
        $this->db->bind(':modelo',     $datos['modelo']);
        $this->db->bind(':tags',       $datos['tags']);
        $this->db->bind(':id',         $datos['id']);
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        return $this->db->execute();
    }

    public function eliminarPrompt($id, $usuario_id) {
        $this->db->query("UPDATE prompts SET activo = 0 WHERE id = :id AND usuario_id = :usuario_id");
        $this->db->bind(':id', $id);
        $this->db->bind(':usuario_id', $usuario_id);
        return $this->db->execute();
    }

    public function esDuenoPrompt($id, $usuario_id) {
        $this->db->query("SELECT id FROM prompts WHERE id = :id AND usuario_id = :usuario_id AND activo = 1");
        $this->db->bind(':id', $id);
        $this->db->bind(':usuario_id', $usuario_id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    // ─── VOTOS ──────────────────────────────────────────────────────────────
    public function registrarVoto($prompt_id, $usuario_id, $tipo) {
        // Ver si ya existe voto de este usuario
        $this->db->query("SELECT id, tipo FROM votos WHERE prompt_id = :pid AND usuario_id = :uid");
        $this->db->bind(':pid', $prompt_id);
        $this->db->bind(':uid', $usuario_id);
        $votoExistente = $this->db->registro();

        if ($votoExistente) {
            if ($votoExistente->tipo === $tipo) {
                // Mismo voto → quitar (toggle)
                $this->db->query("DELETE FROM votos WHERE prompt_id = :pid AND usuario_id = :uid");
                $this->db->bind(':pid', $prompt_id);
                $this->db->bind(':uid', $usuario_id);
                $this->db->execute();
                return 'removed';
            } else {
                // Cambiar voto
                $this->db->query("UPDATE votos SET tipo = :tipo WHERE prompt_id = :pid AND usuario_id = :uid");
                $this->db->bind(':tipo', $tipo);
                $this->db->bind(':pid', $prompt_id);
                $this->db->bind(':uid', $usuario_id);
                $this->db->execute();
                return 'changed';
            }
        } else {
            $this->db->query("INSERT INTO votos (id, prompt_id, usuario_id, tipo) VALUES (UUID(), :pid, :uid, :tipo)");
            $this->db->bind(':pid', $prompt_id);
            $this->db->bind(':uid', $usuario_id);
            $this->db->bind(':tipo', $tipo);
            $this->db->execute();
            return 'added';
        }
    }

    public function obtenerVotoUsuario($prompt_id, $usuario_id) {
        $this->db->query("SELECT tipo FROM votos WHERE prompt_id = :pid AND usuario_id = :uid");
        $this->db->bind(':pid', $prompt_id);
        $this->db->bind(':uid', $usuario_id);
        $r = $this->db->registro();
        return $r ? $r->tipo : null;
    }

    public function getVotosCount($prompt_id) {
        $this->db->query("SELECT
            SUM(tipo = 'positivo') AS positivos,
            SUM(tipo = 'negativo') AS negativos
            FROM votos WHERE prompt_id = :pid");
        $this->db->bind(':pid', $prompt_id);
        return $this->db->registro();
    }

    // ─── COMENTARIOS ────────────────────────────────────────────────────────
    public function obtenerComentarios($prompt_id) {
        $this->db->query("SELECT c.id, c.contenido, c.fecha_creacion,
                          u.id AS usuario_id, u.nombre AS usuario_nombre, u.avatar, u.verificado
                          FROM comentarios c
                          JOIN usuarios u ON c.usuario_id = u.id
                          WHERE c.prompt_id = :pid AND c.activo = 1
                          ORDER BY c.fecha_creacion ASC");
        $this->db->bind(':pid', $prompt_id);
        return $this->db->registros();
    }

    public function agregarComentario($datos) {
        $this->db->query("INSERT INTO comentarios (id, prompt_id, usuario_id, contenido)
                          VALUES (UUID(), :prompt_id, :usuario_id, :contenido)");
        $this->db->bind(':prompt_id',  $datos['prompt_id']);
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':contenido',  $datos['contenido']);
        return $this->db->execute();
    }

    public function eliminarComentario($id, $usuario_id) {
        $this->db->query("UPDATE comentarios SET activo = 0 WHERE id = :id AND usuario_id = :uid");
        $this->db->bind(':id', $id);
        $this->db->bind(':uid', $usuario_id);
        return $this->db->execute();
    }

    // ─── FAVORITOS ──────────────────────────────────────────────────────────
    public function toggleFavorito($usuario_id, $prompt_id) {
        $this->db->query("SELECT id FROM favoritos WHERE usuario_id = :uid AND prompt_id = :pid");
        $this->db->bind(':uid', $usuario_id);
        $this->db->bind(':pid', $prompt_id);
        $existe = $this->db->registro();

        if ($existe) {
            $this->db->query("DELETE FROM favoritos WHERE usuario_id = :uid AND prompt_id = :pid");
            $this->db->bind(':uid', $usuario_id);
            $this->db->bind(':pid', $prompt_id);
            $this->db->execute();
            return false; // eliminado
        } else {
            $this->db->query("INSERT INTO favoritos (id, usuario_id, prompt_id) VALUES (UUID(), :uid, :pid)");
            $this->db->bind(':uid', $usuario_id);
            $this->db->bind(':pid', $prompt_id);
            $this->db->execute();
            return true; // guardado
        }
    }

    public function esFavorito($usuario_id, $prompt_id) {
        $this->db->query("SELECT id FROM favoritos WHERE usuario_id = :uid AND prompt_id = :pid");
        $this->db->bind(':uid', $usuario_id);
        $this->db->bind(':pid', $prompt_id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function obtenerFavoritos($usuario_id, $pagina = 1) {
        $offset = ($pagina - 1) * TAM_PAGINA;
        $this->db->query("SELECT p.id, p.titulo, p.contenido, p.categoria, p.tags, p.modelo, p.fecha_creacion,
                          u.nombre AS usuario_nombre, u.avatar,
                          (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'positivo') AS votos_pos,
                          (SELECT COUNT(*) FROM comentarios WHERE prompt_id = p.id AND activo = 1) AS num_comentarios
                          FROM favoritos f
                          JOIN prompts p ON f.prompt_id = p.id
                          JOIN usuarios u ON p.usuario_id = u.id
                          WHERE f.usuario_id = :uid AND p.activo = 1
                          ORDER BY f.fecha_creacion DESC
                          LIMIT :lim OFFSET :off");
        $this->db->bind(':uid', $usuario_id);
        $this->db->bind(':lim', TAM_PAGINA, PDO::PARAM_INT);
        $this->db->bind(':off', $offset, PDO::PARAM_INT);
        return $this->db->registros();
    }

    // ─── BÚSQUEDA ────────────────────────────────────────────────────────────
    public function buscar($query, $categoria = '', $modelo = '', $orden = 'relevancia') {
        $q = '%' . $query . '%';
        $where = "WHERE p.activo = 1 AND (p.titulo LIKE :q OR p.contenido LIKE :q2 OR p.tags LIKE :q3)";
        if ($categoria) $where .= ' AND p.categoria = :cat';
        if ($modelo) $where .= ' AND p.modelo = :mod';

        $orderBy = match($orden) {
            'votos'  => 'votos_pos DESC',
            'nuevo'  => 'p.fecha_creacion DESC',
            default  => 'votos_pos DESC, p.fecha_creacion DESC',
        };

        $this->db->query("SELECT p.id, p.titulo, p.contenido, p.categoria, p.tags, p.modelo, p.fecha_creacion,
                          u.nombre AS usuario_nombre, u.avatar,
                          (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'positivo') AS votos_pos,
                          (SELECT COUNT(*) FROM comentarios WHERE prompt_id = p.id AND activo = 1) AS num_comentarios
                          FROM prompts p
                          JOIN usuarios u ON p.usuario_id = u.id
                          $where
                          ORDER BY $orderBy
                          LIMIT 30");
        $this->db->bind(':q',  $q);
        $this->db->bind(':q2', $q);
        $this->db->bind(':q3', $q);
        if ($categoria) $this->db->bind(':cat', $categoria);
        if ($modelo)    $this->db->bind(':mod', $modelo);
        return $this->db->registros();
    }

    // ─── TOP CREADORES ───────────────────────────────────────────────────────
    public function obtenerTopCreadores($limite = 5) {
        $this->db->query("SELECT * FROM usuarios_estadisticas ORDER BY num_prompts DESC, num_seguidores DESC LIMIT :lim");
        $this->db->bind(':lim', $limite, PDO::PARAM_INT);
        return $this->db->registros();
    }

    // ─── PROMPTS DE UN USUARIO ───────────────────────────────────────────────
    public function obtenerPromptsPorUsuario($usuario_id, $pagina = 1) {
        $offset = ($pagina - 1) * TAM_PAGINA;
        $this->db->query("SELECT p.id, p.titulo, p.contenido, p.categoria, p.tags, p.modelo, p.fecha_creacion,
                          u.nombre AS usuario_nombre, u.avatar,
                          (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'positivo') AS votos_pos,
                          (SELECT COUNT(*) FROM comentarios WHERE prompt_id = p.id AND activo = 1) AS num_comentarios
                          FROM prompts p
                          JOIN usuarios u ON p.usuario_id = u.id
                          WHERE p.usuario_id = :uid AND p.activo = 1
                          ORDER BY p.fecha_creacion DESC
                          LIMIT :lim OFFSET :off");
        $this->db->bind(':uid', $usuario_id);
        $this->db->bind(':lim', TAM_PAGINA, PDO::PARAM_INT);
        $this->db->bind(':off', $offset, PDO::PARAM_INT);
        return $this->db->registros();
    }

    // ─── ESTADÍSTICAS ────────────────────────────────────────────────────────
    public function estadisticasGlobales() {
        $this->db->query("SELECT
            (SELECT COUNT(*) FROM prompts WHERE activo = 1) AS total_prompts,
            (SELECT COUNT(*) FROM usuarios WHERE activo = 1) AS total_usuarios,
            (SELECT COUNT(*) FROM votos WHERE DATE(fecha_creacion) = CURDATE()) AS votos_hoy,
            (SELECT COUNT(*) FROM comentarios WHERE activo = 1) AS total_comentarios");
        return $this->db->registro();
    }

    // ─── PROMPTS RELACIONADOS ────────────────────────────────────────────────
    public function obtenerRelacionados($prompt_id, $categoria, $limite = 3) {
        $this->db->query("SELECT p.id, p.titulo, p.categoria, p.modelo,
                          u.nombre AS usuario_nombre,
                          (SELECT COUNT(*) FROM votos WHERE prompt_id = p.id AND tipo = 'positivo') AS votos_pos
                          FROM prompts p JOIN usuarios u ON p.usuario_id = u.id
                          WHERE p.categoria = :cat AND p.id != :pid AND p.activo = 1
                          ORDER BY votos_pos DESC LIMIT :lim");
        $this->db->bind(':cat', $categoria);
        $this->db->bind(':pid', $prompt_id);
        $this->db->bind(':lim', $limite, PDO::PARAM_INT);
        return $this->db->registros();
    }
}
