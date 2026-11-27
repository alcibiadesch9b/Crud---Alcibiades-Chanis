# CRUD con Fetch + PHP OOP + MySQL
**Estudiante:** Alcibiades Chanis · **Grupo:** 1S3122 · **Instructora:** Ing. Irina Fong

## Estructura
```
CRUD_Productos/
├── bd.sql               -> crea productosdb y la tabla productos
├── index.html           -> formulario Bootstrap + tabla + búsqueda
├── script.js            -> fetch, FormData, switch, SweetAlert2
├── registrar.php        -> controlador con switch($accion), responde JSON
└── Modelo/
    ├── conexion.php     -> clase DB (PDO, singleton): insertSeguro, updateSeguro, deleteSeguro, query
    └── Productos.php    -> clase Producto: guardar, editar, buscar, listar, eliminar, validar
```

## Cómo ejecutar
1. Importar `bd.sql` en phpMyAdmin.
2. Copiar la carpeta en `www` (WampServer) o `htdocs` (XAMPP).
3. Ajustar usuario/clave en `Modelo/conexion.php` si es necesario.
4. Abrir `http://localhost/CRUD_Productos/`.

## Respuesta JSON
`{ "success": true, "message": "...", "accion": "Guardar", "errors": {}, "data": [] }`

## Reglas de negocio
- Guardar: cantidad mínima 1. Modificar: cantidad mínima 0 (producto agotado).
- Código único. Validación en cliente (JS) y servidor (PHP).
- Extra: Eliminar con confirmación.
