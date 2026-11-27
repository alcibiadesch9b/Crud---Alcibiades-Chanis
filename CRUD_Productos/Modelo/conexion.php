<?php
/**
 * Modelo/conexion.php
 * Autor: Alcibiades Chanis
 * Clase DB (singleton): conexión segura a MySQL con PDO.
 * Expone insertSeguro(), updateSeguro(), deleteSeguro() y query().
 */
class DB
{
    private static $instancia = null;
    private $pdo;

    // Datos de conexión (ajustar según WampServer / XAMPP)
    private $host    = 'localhost';
    private $bd      = 'productosdb';
    private $usuario = 'root';
    private $clave   = '';

    private function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->bd};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $this->usuario, $this->clave, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lanzar excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                  // sentencias preparadas reales
        ]);
    }

    /** Devuelve la única instancia de la conexión */
    public static function getInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new DB();
        }
        return self::$instancia;
    }

    /** INSERT seguro: $datos = ['columna' => valor] */
    public function insertSeguro($tabla, array $datos)
    {
        $columnas = implode(', ', array_keys($datos));
        $marcas   = ':' . implode(', :', array_keys($datos));
        $stmt = $this->pdo->prepare("INSERT INTO $tabla ($columnas) VALUES ($marcas)");
        $stmt->execute($datos);
        return $this->pdo->lastInsertId();
    }

    /** UPDATE seguro por id */
    public function updateSeguro($tabla, array $datos, $id)
    {
        $sets = [];
        foreach (array_keys($datos) as $col) {
            $sets[] = "$col = :$col";
        }
        $datos['id'] = $id;
        $stmt = $this->pdo->prepare("UPDATE $tabla SET " . implode(', ', $sets) . " WHERE id = :id");
        $stmt->execute($datos);
        return $stmt->rowCount();
    }

    /** DELETE seguro por id (reto extra) */
    public function deleteSeguro($tabla, $id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM $tabla WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }

    /** Consulta SELECT con parámetros */
    public function query($sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
