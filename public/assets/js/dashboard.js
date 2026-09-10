// public/assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', cargarIndicadores);

async function cargarIndicadores() {
    const respuesta = await fetch('index.php?ruta=api/dashboard');
    const datos = await respuesta.json();

    document.getElementById('indicador-total').textContent = datos.total;

    const contenedorCategorias = document.getElementById('lista-por-categoria');
    contenedorCategorias.innerHTML = datos.por_categoria.map(c =>
        `<tr><td>${escaparHtml(c.categoria)}</td><td>${c.cantidad}</td></tr>`
    ).join('') || '<tr><td colspan="2">Sin datos aún.</td></tr>';

    const contenedorEstados = document.getElementById('lista-por-estado');
    contenedorEstados.innerHTML = datos.por_estado.map(e =>
        `<tr><td>${etiquetaEstado(e.estado)}</td><td>${e.cantidad}</td></tr>`
    ).join('') || '<tr><td colspan="2">Sin datos aún.</td></tr>';
}

function etiquetaEstado(estado) {
    const etiquetas = { pendiente: 'Pendiente', procesando: 'Procesando', completado: 'Completado', error: 'Error' };
    return etiquetas[estado] || estado;
}

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}
