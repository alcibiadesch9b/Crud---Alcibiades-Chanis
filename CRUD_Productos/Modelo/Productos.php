<?php
/**
 * Modelo/Productos.php
 * Autor: Alcibiades Chanis
 * Clase Producto: propiedades, validación y métodos guardar(), editar(), buscar(), listar(), eliminar().
 */
require_once __DIR__ . '/conexion.php';

class Producto
{
    public $id;
    public $codigo;
    public $producto;
    public $precio;
    public $cantidad;

    private $db;
    private $tabla = 'productos';

    public function __construct($datos = [])
    {
        $this->db       = DB::getInstancia();
        $this->id       = isset($datos['id']) ? trim($datos['id']) : null;
        $this->codigo   = isset($datos['codigo']) ? trim($datos['codigo']) : '';
        $this->producto = isset($datos['producto']) ? trim($datos['producto']) : '';
        $this->precio   = isset($datos['precio']) ? trim($datos['precio']) : '';
        $this->cantidad = isset($datos['cantidad']) ? trim($datos['cantidad']) : '';
    }

    /**
     * Valida los campos obligatorios.
     * $esNuevo = true  -> cantidad mínima 1 (regla de negocio para registrar)
     * $esNuevo = false -> cantidad mínima 0 (al modificar puede estar agotado)
     */
    public function validar($esNuevo = true)
    {
        $errores = [];

        if ($this->codigo === '') {
            $errores['codigo'] = 'El código es obligatorio.';
        } elseif (strlen($this->codigo) > 20) {
            $errores['codigo'] = 'El código no puede pasar de 20 caracteres.';
        }

        if ($this->producto === '') {
            $errores['producto'] = 'El nombre del producto es obligatorio.';
        } elseif (strlen($this->producto) > 100) {
            $errores['producto'] = 'El producto no puede pasar de 100 caracteres.';
        }

        if ($this->precio === '' || !is_numeric($this->precio) || $this->precio <= 0) {
            $errores['precio'] = 'El precio debe ser un número mayor que 0.';
        }

        $minimo = $esNuevo ? 1 : 0;
        if ($this->cantidad === '' || filter_var($this->cantidad, FILTER_VALIDATE_INT) === false || (int)$this->cantidad < $minimo) {
            $errores['cantidad'] = "La cantidad debe ser un entero mayor o igual a $minimo.";
        }

        if (!$esNuevo && (empty($this->id) || filter_var($this->id, FILTER_VALIDATE_INT) === false)) {
            $errores['id'] = 'Debe seleccionar un producto válido para modificar.';
        }

        // Código único
        if (!isset($errores['codigo'])) {
            $sql = 'SELECT id FROM productos WHERE codigo = :codigo' . ($esNuevo ? '' : ' AND id <> :id');
            $params = ['codigo' => $this->codigo];
            if (!$esNuevo) { $params['id'] = (int)$this->id; }
            if ($this->db->query($sql, $params)) {
                $errores['codigo'] = 'Ya existe un producto con ese código.';
            }
        }

        return $errores;
    }

    private function datos()
    {
        return [
            'codigo'   => $this->codigo,
            'producto' => $this->producto,
            'precio'   => (float)$this->precio,
            'cantidad' => (int)$this->cantidad,
        ];
    }

    public function guardar()
    {
        return $this->db->insertSeguro($this->tabla, $this->datos());
    }

    public function editar()
    {
        return $this->db->updateSeguro($this->tabla, $this->datos(), (int)$this->id);
    }

    public function eliminar($id)
    {
        return $this->db->deleteSeguro($this->tabla, (int)$id);
    }

    /** Busca por código o nombre (texto parcial) */
    public function buscar($texto)
    {
        return $this->db->query(
            'SELECT * FROM productos WHERE codigo LIKE :t1 OR producto LIKE :t2 ORDER BY id DESC',
            ['t1' => "%$texto%", 't2' => "%$texto%"]
        );
    }

    public function listar()
    {
        return $this->db->query('SELECT * FROM productos ORDER BY id DESC');
    }
}
