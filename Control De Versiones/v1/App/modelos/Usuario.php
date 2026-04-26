<?php
namespace Model;

class Usuario extends ActiveRecord {
    protected static $tabla = 'usuarios';
    protected static $columnasDB = ['id', 'nombre', 'email', 'contraseña', 'avatar', 'verificado', 'activo'];

    public $id;
    public $nombre;
    public $email;
    public $contraseña;
    public $avatar;

    public function __construct($args = []) {
        $this->id = $args['id'] ?? bin2hex(random_bytes(18)); // Genera un ID único tipo 'user_xxx'
        $this->nombre = $args['nombre'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->contraseña = $args['contraseña'] ?? '';
        $this->avatar = "https://ui-avatars.com/api/?name=" . str_replace(' ', '+', $this->nombre) . "&background=7C6FFF&color=fff";
        $this->verificado = 0;
        $this->activo = 1;
    }

    // Validar el registro
    public function validarRegistro() {
        if(!$this->nombre) self::$alertas['error'][] = 'El nombre es obligatorio';
        if(!$this->email) self::$alertas['error'][] = 'El email es obligatorio';
        if(strlen($this->contraseña) < 6) self::$alertas['error'][] = 'La contraseña debe tener al menos 6 caracteres';
        return self::$alertas;
    }

    // Revisar si el usuario ya existe
    public function existeUsuario() {
        $query = "SELECT * FROM " . self::$tabla . " WHERE email = '" . $this->email . "' LIMIT 1";
        $resultado = self::$db->query($query);
        if($resultado->num_rows) self::$alertas['error'][] = 'El usuario ya está registrado';
        return $resultado;
    }

    // Hash de la contraseña
    public function hashPassword() {
        $this->contraseña = password_hash($this->contraseña, PASSWORD_BCRYPT);
    }
}