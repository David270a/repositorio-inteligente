// public/assets/js/repositorio.js

let carpetaActual = null;

document.addEventListener('DOMContentLoaded', () => {
    const primeraCarpeta = document.querySelector('.lista-carpetas li');
    if (primeraCarpeta) {
        seleccionarCarpeta(primeraCarpeta.dataset.id, primeraCarpeta);
    }

    document.getElementById('form-nueva-carpeta').addEventListener('submit', crearCarpeta);
    document.getElementById('form-subir-documento').addEventListener('submit', subirDocumento);
});

function seleccionarCarpeta(idCarpeta, elemento) {
    carpetaActual = idCarpeta;
    document.querySelectorAll('.lista-carpetas li').forEach(li => li.classList.remove('activa'));
    if (elemento) elemento.classList.add('activa');
    document.getElementById('id_carpeta_subida').value = idCarpeta;
    cargarDocumentos(idCarpeta);
}

async function crearCarpeta(evento) {
    evento.preventDefault();
    const nombre = document.getElementById('nombre_carpeta').value.trim();
    if (!nombre) return;

    const respuesta = await fetch('index.php?ruta=api/carpetas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `nombre=${encodeURIComponent(nombre)}`,
    });
    const datos = await respuesta.json();

    if (datos.id_carpeta) {
        const li = document.createElement('li');
        li.dataset.id = datos.id_carpeta;
        li.textContent = datos.nombre;
        li.onclick = () => seleccionarCarpeta(datos.id_carpeta, li);
        document.querySelector('.lista-carpetas').appendChild(li);
        document.getElementById('nombre_carpeta').value = '';
    } else {
        alert(datos.error || 'No se pudo crear la carpeta.');
    }
}

async function cargarDocumentos(idCarpeta) {
    const tbody = document.getElementById('cuerpo-tabla-documentos');
    tbody.innerHTML = '<tr><td colspan="4">Cargando...</td></tr>';

    const respuesta = await fetch(`index.php?ruta=api/documentos/carpeta/${idCarpeta}`);
    const documentos = await respuesta.json();

    if (!documentos.length) {
        tbody.innerHTML = '<tr><td colspan="4">No hay documentos en esta carpeta.</td></tr>';
        return;
    }

    tbody.innerHTML = documentos.map(filaDocumento).join('');
}

function filaDocumento(doc) {
    return `
        <tr id="fila-doc-${doc.id_documento}">
            <td>${escaparHtml(doc.nombre_archivo)}</td>
            <td>${doc.categoria ? escaparHtml(doc.categoria) : '—'}</td>
            <td>${badgeEstado(doc.estado)}</td>
            <td>
                <a href="#" onclick="verDetalle(${doc.id_documento}); return false;">Ver</a> ·
                <a href="index.php?ruta=api/documentos/${doc.id_documento}/descargar">Descargar</a> ·
                <a href="#" onclick="eliminarDocumento(${doc.id_documento}); return false;">Eliminar</a>
            </td>
        </tr>`;
}

function badgeEstado(estado) {
    const etiquetas = { pendiente: 'Pendiente', procesando: 'Procesando', completado: 'Completado', error: 'Error' };
    return `<span class="badge badge-${estado}">${etiquetas[estado] || estado}</span>`;
}

async function subirDocumento(evento) {
    evento.preventDefault();
    const form = evento.target;
    const formData = new FormData(form);
    const boton = form.querySelector('button');

    boton.disabled = true;
    boton.textContent = 'Procesando con IA... esto puede tardar unos segundos';

    try {
        const respuesta = await fetch('index.php?ruta=api/documentos', { method: 'POST', body: formData });
        const datos = await respuesta.json();

        if (datos.error) {
            alert(datos.error);
        } else {
            await cargarDocumentos(carpetaActual);
        }
    } finally {
        boton.disabled = false;
        boton.textContent = 'Subir documento';
        form.reset();
        document.getElementById('id_carpeta_subida').value = carpetaActual;
    }
}

async function verDetalle(idDocumento) {
    const respuesta = await fetch(`index.php?ruta=api/documentos/${idDocumento}`);
    const doc = await respuesta.json();

    const metadatos = doc.metadatos.map(m => `<li><strong>${escaparHtml(m.campo)}:</strong> ${escaparHtml(m.valor)}</li>`).join('');

    document.getElementById('panel-detalle').innerHTML = `
        <h3>${escaparHtml(doc.nombre_archivo)}</h3>
        <p>${badgeEstado(doc.estado)} &nbsp; Categoría: <strong>${doc.categoria ? escaparHtml(doc.categoria) : '—'}</strong></p>
        <h4>Resumen</h4>
        <p>${doc.resumen ? escaparHtml(doc.resumen) : 'Aún no se ha generado el resumen.'}</p>
        <h4>Información extraída</h4>
        <ul>${metadatos || '<li>Sin metadatos extraídos.</li>'}</ul>
    `;
    document.getElementById('panel-detalle').style.display = 'block';
    document.getElementById('panel-detalle').scrollIntoView({ behavior: 'smooth' });
}

async function eliminarDocumento(idDocumento) {
    if (!confirm('¿Eliminar este documento? Esta acción no se puede deshacer.')) return;

    await fetch(`index.php?ruta=api/documentos/${idDocumento}/eliminar`, { method: 'POST' });
    document.getElementById(`fila-doc-${idDocumento}`)?.remove();
}

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}
