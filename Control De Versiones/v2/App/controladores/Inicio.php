<?php
class Inicio extends Controlador {
    private $promptModelo;

    public function __construct() {
        $this->promptModelo = $this->modelo('PromptModelo');
    }

    // GET /inicio/leaderboard?modo=prompts|seguidores
    public function leaderboard() {
        $modo = sanitizarTexto($_GET['modo'] ?? 'prompts');
        if (!in_array($modo, ['prompts', 'seguidores'])) $modo = 'prompts';
        $creadores = $this->promptModelo->obtenerTopCreadores(20);
        // Re-sort based on mode
        if ($modo === 'seguidores') {
            usort($creadores, fn($a, $b) => $b->num_seguidores - $a->num_seguidores);
        }
        jsonResponse(['creadores' => $creadores, 'modo' => $modo]);
    }

    public function index() {
        $orden     = sanitizarTexto($_GET['orden'] ?? 'hot');
        $categoria = sanitizarTexto($_GET['cat'] ?? '');
        $pagina    = max(1, sanitizarInt($_GET['p'] ?? 1));

        $prompts       = $this->promptModelo->obtenerPrompts($orden, $categoria, $pagina);
        $topCreadores  = $this->promptModelo->obtenerTopCreadores(5);
        $stats         = $this->promptModelo->estadisticasGlobales();
        $totalPrompts  = $this->promptModelo->contarPrompts($categoria);
        $totalPaginas  = ceil($totalPrompts / TAM_PAGINA);

        // Marcar votos del usuario actual
        $usuarioActual = usuarioActual();
        if ($usuarioActual) {
            foreach ($prompts as &$p) {
                $p->mi_voto = $this->promptModelo->obtenerVotoUsuario($p->id, $usuarioActual['id']);
                $p->es_favorito = $this->promptModelo->esFavorito($usuarioActual['id'], $p->id);
            }
        }

        $datos = [
            'titulo'        => 'PromptVault — La comunidad de prompts de IA',
            'prompts'       => $prompts,
            'top_creadores' => $topCreadores,
            'stats'         => $stats,
            'orden'         => $orden,
            'categoria'     => $categoria,
            'pagina'        => $pagina,
            'total_paginas' => $totalPaginas,
            'usuario'       => $usuarioActual,
        ];

        $this->vista('inicio/index', $datos);
    }
}
