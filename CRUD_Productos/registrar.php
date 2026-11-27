<?php
/**
 * registrar.php
 * Autor: Alcibiades Chanis
 * Controlador: recibe $_POST y con switch($accion) ejecuta Guardar, Modificar, Buscar, Listar o Eliminar.
 * Siempre responde SOLO JSON: { success, message, accion, errors, data }
 */
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // evitar "ruido" que rompa el JSON

require_once __DIR__ . '/Modelo/Productos.php';

$respuesta = ['success' => false, 'message' => '', 'accion' => '', 'errors' => [], 'data' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $accion = isset($_POST['Accion']) ? trim($_POST['Accion']) : '';
    $respuesta['accion'] = $accion;
    $producto = new Producto($_POST);

    switch ($accion) {
        case 'Guardar':
            $errores = $producto->validar(true);
            if ($errores) {
                $respuesta['message'] = 'Revise los campos del formulario.';
                $respuesta['errors']  = $errores;
                break;
            }
            $id = $producto->guardar();
            $respuesta['success'] = true;
            $respuesta['message'] = 'Producto registrado correctamente.';
            $respuesta['data']    = ['id' => $id];
            break;

        case 'Modificar':
            $errores = $producto->validar(false);
            if ($errores) {
                $respuesta['message'] = 'Revise los campos del formulario.';
                $respuesta['errors']  = $errores;
                break;
            }
            $producto->editar();
            $respuesta['success'] = true;
            $respuesta['message'] = 'Producto actualizado correctamente.';
            break;

        case 'Buscar':
            $texto = isset($_POST['buscar']) ? trim($_POST['buscar']) : '';
            $respuesta['data']    = $producto->buscar($texto);
            $respuesta['success'] = true;
            $respuesta['message'] = count($respuesta['data']) . ' producto(s) encontrado(s).';
            break;

        case 'Listar':
            $respuesta['data']    = $producto->listar();
            $respuesta['success'] = true;
            $respuesta['message'] = 'Listado de productos.';
            break;

        case 'Eliminar':
            $id = isset($_POST['id']) ? $_POST['id'] : '';
            if (filter_var($id, FILTER_VALIDATE_INT) === false) {
                $respuesta['message'] = 'Id de producto no válido.';
                $respuesta['errors']  = ['id' => 'Id no válido.'];
                break;
            }
            $filas = $producto->eliminar($id);
            $respuesta['success'] = $filas > 0;
            $respuesta['message'] = $filas > 0 ? 'Producto eliminado.' : 'El producto no existe.';
            break;

        default:
            $respuesta['message'] = 'Acción no reconocida.';
            $respuesta['errors']  = ['Accion' => 'Debe enviar Guardar, Modificar, Buscar, Listar o Eliminar.'];
    }
} catch (PDOException $e) {
    $respuesta['message'] = 'Error de base de datos. Verifique la conexión y que exista productosdb.';
    $respuesta['errors']  = ['bd' => $e->getMessage()];
} catch (Exception $e) {
    $respuesta['message'] = $e->getMessage();
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
