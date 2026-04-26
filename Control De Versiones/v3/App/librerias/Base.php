<?php
class Base {
    private $host     = DB_HOST;
    private $usuario  = DB_USUARIO;
    private $password = DB_PASSWORD;
    private $nombre   = DB_NOMBRE;
    private $charset  = 'utf8mb4';

    protected $dbh;
    private $stmt;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->nombre};charset={$this->charset}";
        $opciones = [
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];
        try {
            $this->dbh = new PDO($dsn, $this->usuario, $this->password, $opciones);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die('Error BD: ' . $e->getMessage());
            } else {
                error_log('DB Connection Error: ' . $e->getMessage());
                die('Error de conexión. Inténtalo más tarde.');
            }
        }
    }

    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):   $type = PDO::PARAM_INT;  break;
                case is_bool($value):  $type = PDO::PARAM_BOOL; break;
                case is_null($value):  $type = PDO::PARAM_NULL; break;
                default:               $type = PDO::PARAM_STR;  break;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function registros() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function registro() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    public function rowCount() {
        $this->execute();
        return $this->stmt->rowCount();
    }

    public function executeLastId() {
        $this->execute();
        return $this->dbh->lastInsertId();
    }

    public function contarRegistros() {
        $this->execute();
        return $this->stmt->fetchColumn();
    }

    public function beginTransaction() { return $this->dbh->beginTransaction(); }
    public function commit()           { return $this->dbh->commit(); }
    public function rollBack()         { return $this->dbh->rollBack(); }
}
