<?php
// Controlador dedicado para /explorar — URL propia, indexable por Google
class Explorar extends Controlador {
    private $promptModelo;

    public function __construct() {
        $this->promptModelo = $this->modelo('PromptModelo');
    }

    // GET /explorar
    public function index() {
        $q         = sanitizarTexto($_GET['q']      ?? '');
        $categoria = sanitizarTexto($_GET['cat']    ?? '');
        $modelo    = sanitizarTexto($_GET['modelo'] ?? '');
        $orden     = sanitizarTexto($_GET['orden']  ?? 'hot');
        $pagina    = max(1, sanitizarInt($_GET['p'] ?? 1));

        $resultados  = [];
        $total       = 0;

        if (strlen($q) >= 2) {
            $resultados  = $this->promptModelo->buscar($q, $categoria, $modelo, $orden);
            $total       = count($resultados);
            // Paginación manual sobre resultados de búsqueda
            $offset      = ($pagina - 1) * TAM_PAGINA;
            $resultados  = array_slice($resultados, $offset, TAM_PAGINA);
        } else {
            $resultados = $this->promptModelo->obtenerPrompts($orden, $categoria, $pagina);
            $total      = $this->promptModelo->contarPrompts($categoria);
        }

        $totalPaginas = max(1, ceil($total / TAM_PAGINA));
        $usuario      = usuarioActual();

        // Marcar votos/favoritos
        if ($usuario) {
            foreach ($resultados as &$p) {
                $p->mi_voto    = $this->promptModelo->obtenerVotoUsuario($p->id, $usuario['id']);
                $p->es_favorito = $this->promptModelo->esFavorito($usuario['id'], $p->id);
            }
        }

        $metaDesc = $q
            ? "Resultados de búsqueda para \"$q\" en PromptVault."
            : ($categoria ? "Prompts de IA de la categoría " . ucfirst($categoria) . " en PromptVault." : "Explora los mejores prompts de IA en PromptVault.");

        $this->vista('explorar/index', [
            'titulo'        => ($q ? "Búsqueda: $q" : 'Explorar Prompts') . ' — PromptVault',
            'meta_desc'     => $metaDesc,
            'resultados'    => $resultados,
            'total'         => $total,
            'total_paginas' => $totalPaginas,
            'pagina'        => $pagina,
            'q'             => $q,
            'categoria'     => $categoria,
            'modelo_filtro' => $modelo,
            'orden'         => $orden,
            'usuario'       => $usuario,
        ]);
    }
}
