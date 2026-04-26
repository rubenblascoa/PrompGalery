<?php
namespace Controllers;
use Model\Usuario;

class AuthController {
    
    public static function registro() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarRegistro();

            if(empty($alertas)) {
                $existe = $usuario->existeUsuario();
                if($existe->num_rows) {
                    echo json_encode(['resultado' => 'error', 'mensaje' => 'El email ya está en uso']);
                } else {
                    $usuario->hashPassword();
                    $resultado = $usuario->guardar();
                    if($resultado) {
                        echo json_encode(['resultado' => 'ok', 'mensaje' => 'Registro exitoso']);
                    }
                }
            } else {
                echo json_encode(['resultado' => 'error', 'alertas' => $alertas]);
            }
        }
    }

    public static function login() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            
            // Buscar al usuario por email
            $usuario = Usuario::where('email', $auth->email);

            if($usuario && password_verify($_POST['contraseña'], $usuario->contraseña)) {
                // Iniciar la sesión
                session_start();
                $_SESSION['id'] = $usuario->id;
                $_SESSION['nombre'] = $usuario->nombre;
                $_SESSION['login'] = true;

                echo json_encode([
                    'resultado' => 'ok', 
                    'mensaje' => 'Bienvenido ' . $usuario->nombre,
                    'user' => ['nombre' => $usuario->nombre, 'avatar' => $usuario->avatar]
                ]);
            } else {
                echo json_encode(['resultado' => 'error', 'mensaje' => 'Credenciales incorrectas']);
            }
        }
    }

    public static function logout() {
        session_start();
        $_SESSION = [];
        session_destroy();
        header('Location: /');
    }
}