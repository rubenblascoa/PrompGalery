<?php
class Inicio extends Controlador {
    
    // 👇 ESTA ES LA LÍNEA QUE FALTA PARA SOLUCIONAR EL ERROR 👇
    private $promptModelo;

    public function __construct() {
        // Ahora sí podemos usarla sin que PHP se queje
        $this->promptModelo = $this->modelo('PromptModelo');
    }
    public function index() {
        $prompts = $this->promptModelo->obtenerPrompts();
        $creadores = $this->promptModelo->obtenerTopCreadores(); // <--- Nueva línea

        $datos = [
            'titulo' => 'PromptVault - Inicio',
            'prompts' => $prompts,
            'top_creadores' => $creadores // <--- Pasamos los creadores a la vista
        ];
        $this->vista('inicio/index', $datos);

    }
}
