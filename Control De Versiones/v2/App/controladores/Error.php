<?php
class Error extends Controlador {
    public function index() {
        http_response_code(404);
        $this->vista('error/404', ['titulo' => 'Página no encontrada', 'usuario' => usuarioActual()]);
    }
}
