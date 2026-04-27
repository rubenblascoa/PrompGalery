<?php
class Controlador {

    public function modelo($modelo) {
        $path = RUTA_APP . '/modelos/' . $modelo . '.php';
        if (!file_exists($path)) die('Modelo no encontrado: ' . $modelo);
        require_once $path;
        return new $modelo;
    }

    public function vista($vista, $datos = []) {
        $path = RUTA_APP . '/vistas/' . $vista . '.php';
        if (file_exists($path)) {
            require_once $path;
        } else {
            http_response_code(404);
            require_once RUTA_APP . '/vistas/error/404.php';
        }
    }

    public function vistaApi($datos, $code = 200) {
        jsonResponse($datos, $code);
    }
}
