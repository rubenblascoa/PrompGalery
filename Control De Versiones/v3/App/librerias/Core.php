<?php
class Core {
    protected $controladorActual = 'Inicio';
    protected $metodoActual      = 'index';
    protected $parametros        = [];

    public function __construct() {
        iniciarSesionSegura();
        $url = $this->getUrl();

        if (isset($url[0]) && $url[0] !== '') {
            $nombre = ucwords(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $url[0])));
            $archivo = RUTA_APP . '/controladores/' . $nombre . '.php';
            if (file_exists($archivo)) {
                $this->controladorActual = $nombre;
                unset($url[0]);
            } else {
                $this->controladorActual = 'Error';
            }
        }

        $archCtrl = RUTA_APP . '/controladores/' . $this->controladorActual . '.php';
        if (!file_exists($archCtrl)) {
            require_once RUTA_APP . '/controladores/Error.php';
            $this->controladorActual = 'Error';
        } else {
            require_once $archCtrl;
        }

        $this->controladorActual = new $this->controladorActual;

        if (isset($url[1])) {
            $metodo = preg_replace('/[^a-zA-Z0-9_]/', '', $url[1]);
            if (method_exists($this->controladorActual, $metodo)) {
                $this->metodoActual = $metodo;
                unset($url[1]);
            }
        }

        $this->parametros = $url ? array_values($url) : [];
        call_user_func_array([$this->controladorActual, $this->metodoActual], $this->parametros);
    }

    private function getUrl() {
        if (isset($_GET['url'])) {
            $url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
            $url = trim($url, '/');
            return explode('/', $url);
        }
        return [''];
    }
}
