/**
 * script.js
 * Autor: Alcibiades Chanis
 * Captura eventos, valida en el cliente, envía FormData con fetch (async/await)
 * y procesa la respuesta JSON con SweetAlert2. Usa switch para decidir la acción.
 */
const URL_API = 'registrar.php';

const form     = document.getElementById('formProducto');
const tabla    = document.getElementById('tablaProductos');
const alerta   = document.getElementById('alerta');
const btnReg   = document.getElementById('btnRegistrar');
let productos  = []; // copia local del último listado

/* ---------- Petición genérica con fetch ---------- */
async function enviar(datos) {
    try {
        const respuesta = await fetch(URL_API, { method: 'POST', body: datos });
        if (!respuesta.ok) {
            throw new Error('Error HTTP ' + respuesta.status);
        }
        return await respuesta.json();
    } catch (error) {
        console.error('Error en fetch:', error);
        Swal.fire('Error de conexión', 'No se pudo comunicar con el servidor: ' + error.message, 'error');
        return null;
    }
}

/* ---------- Alert de Bootstrap ---------- */
function mostrarAlerta(mensaje, tipo) {
    alerta.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
    alerta.innerHTML = mensaje + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
}

/* ---------- Validación en el cliente ---------- */
function limpiarErrores() {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

function marcarErrores(errores) {
    limpiarErrores();
    Object.keys(errores).forEach(campo => {
        const input = document.getElementById(campo);
        const div = form.querySelector('[data-error="' + campo + '"]');
        if (input) input.classList.add('is-invalid');
        if (div) div.textContent = errores[campo];
    });
}

function validarCliente() {
    const errores = {};
    const accion  = document.getElementById('Accion').value;
    const codigo  = form.codigo.value.trim();
    const nombre  = form.producto.value.trim();
    const precio  = parseFloat(form.precio.value);
    const cant    = form.cantidad.value;
    const minimo  = accion === 'Guardar' ? 1 : 0; // regla de negocio

    if (!codigo) errores.codigo = 'El código es obligatorio.';
    if (!nombre) errores.producto = 'El nombre del producto es obligatorio.';
    if (isNaN(precio) || precio <= 0) errores.precio = 'El precio debe ser mayor que 0.';
    if (cant === '' || !Number.isInteger(Number(cant)) || Number(cant) < minimo) {
        errores.cantidad = 'La cantidad debe ser un entero mayor o igual a ' + minimo + '.';
    }
    return errores;
}

/* ---------- Pintar la tabla ---------- */
function pintarTabla(lista) {
    productos = lista;
    if (!lista.length) {
        tabla.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay productos.</td></tr>';
        return;
    }
    tabla.innerHTML = lista.map(p => `
        <tr>
            <td>${p.id}</td>
            <td><span class="badge text-bg-secondary">${escapar(p.codigo)}</span></td>
            <td>${escapar(p.producto)}</td>
            <td class="text-end">$${Number(p.precio).toFixed(2)}</td>
            <td class="text-center">
                ${Number(p.cantidad) === 0
                    ? '<span class="badge text-bg-danger">Agotado</span>'
                    : '<span class="badge text-bg-success">' + p.cantidad + '</span>'}
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning" data-accion="Editar" data-id="${p.id}"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-danger" data-accion="Eliminar" data-id="${p.id}"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`).join('');
}

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

/* ---------- Funciones CRUD ---------- */
async function ListarProductos() {
    const datos = new FormData();
    datos.append('Accion', 'Listar');
    const r = await enviar(datos);
    if (r && r.success) pintarTabla(r.data);
    else if (r) Swal.fire('Error', r.message, 'error');
}

async function BuscarProductos() {
    const texto = document.getElementById('txtBuscar').value.trim();
    if (!texto) {
        Swal.fire('Atención', 'Escriba un código o nombre para buscar.', 'info');
        return;
    }
    const datos = new FormData();
    datos.append('Accion', 'Buscar');
    datos.append('buscar', texto);
    const r = await enviar(datos);
    if (!r) return;
    pintarTabla(r.data);
    mostrarAlerta('<i class="bi bi-search"></i> ' + r.message, r.data.length ? 'info' : 'warning');
}

async function RegistrarProducto() {
    const errores = validarCliente();
    if (Object.keys(errores).length) {
        marcarErrores(errores);
        Swal.fire('Datos incompletos', 'Revise los campos marcados en rojo.', 'warning');
        return;
    }

    const r = await enviar(new FormData(form)); // envía Accion = Guardar o Modificar
    if (!r) return;

    if (r.success) {
        Swal.fire({ icon: 'success', title: r.accion === 'Guardar' ? '¡Guardado!' : '¡Actualizado!', text: r.message, timer: 1800, showConfirmButton: false });
        mostrarAlerta('<i class="bi bi-check-circle"></i> ' + r.message, 'success');
        modoGuardar();
        ListarProductos();
    } else {
        marcarErrores(r.errors || {});
        const lista = Object.values(r.errors || {}).map(e => '• ' + e).join('<br>');
        Swal.fire({ icon: 'error', title: 'No se pudo completar', html: r.message + (lista ? '<br><br>' + lista : '') });
        mostrarAlerta(r.message, 'danger');
    }
}

function CargarEdicion(id) {
    const p = productos.find(x => String(x.id) === String(id));
    if (!p) return;
    form.id.value       = p.id;
    form.codigo.value   = p.codigo;
    form.producto.value = p.producto;
    form.precio.value   = p.precio;
    form.cantidad.value = p.cantidad;
    modoModificar();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function EliminarProducto(id) {
    const confirmacion = await Swal.fire({
        title: '¿Eliminar producto?', text: 'Esta acción no se puede deshacer.', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc3545'
    });
    if (!confirmacion.isConfirmed) return;

    const datos = new FormData();
    datos.append('Accion', 'Eliminar');
    datos.append('id', id);
    const r = await enviar(datos);
    if (!r) return;
    Swal.fire(r.success ? 'Eliminado' : 'Error', r.message, r.success ? 'success' : 'error');
    ListarProductos();
}

/* ---------- Modos del formulario ---------- */
function modoGuardar() {
    form.reset();
    form.id.value = '';
    document.getElementById('Accion').value = 'Guardar';
    form.cantidad.min = 1; // producto nuevo: al menos 1 unidad
    btnReg.className = 'btn btn-primary';
    btnReg.querySelector('span').textContent = 'Registrar';
    document.getElementById('tituloForm').innerHTML = '<i class="bi bi-plus-circle"></i> Registrar producto';
    limpiarErrores();
}

function modoModificar() {
    document.getElementById('Accion').value = 'Modificar';
    form.cantidad.min = 0; // al modificar puede quedar agotado
    btnReg.className = 'btn btn-warning';
    btnReg.querySelector('span').textContent = 'Actualizar';
    document.getElementById('tituloForm').innerHTML = '<i class="bi bi-pencil-square"></i> Modificar producto';
    limpiarErrores();
}

/* ---------- Un solo manejador de clics con switch ---------- */
document.addEventListener('click', e => {
    const boton = e.target.closest('[data-accion]');
    if (!boton) return;
    e.preventDefault();

    switch (boton.dataset.accion) {
        case 'Guardar':   RegistrarProducto(); break;   // Guardar o Modificar según el campo Accion
        case 'Buscar':    BuscarProductos(); break;
        case 'Listar':    document.getElementById('txtBuscar').value = ''; ListarProductos(); break;
        case 'Editar':    CargarEdicion(boton.dataset.id); break;
        case 'Eliminar':  EliminarProducto(boton.dataset.id); break;
        case 'Cancelar':  modoGuardar(); break;
        default:          console.warn('Acción desconocida:', boton.dataset.accion);
    }
});

// Enter en el buscador
document.getElementById('txtBuscar').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); BuscarProductos(); }
});

// Cargar la tabla al iniciar
document.addEventListener('DOMContentLoaded', ListarProductos);
