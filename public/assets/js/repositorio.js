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
        li.innerHTML = `
            <span class="nombre-carpeta"><span class="icono-carpeta">▣</span>${escaparHtml(datos.nombre)}</span>
            <button type="button" class="boton-eliminar-carpeta" title="Eliminar carpeta" aria-label="Eliminar carpeta"><span aria-hidden="true">🗑</span></button>`;
        li.onclick = () => seleccionarCarpeta(datos.id_carpeta, li);
        li.querySelector('.boton-eliminar-carpeta').onclick = (e) => eliminarCarpeta(e, datos.id_carpeta, datos.nombre);
        document.querySelector('.lista-carpetas').appendChild(li);
        document.getElementById('nombre_carpeta').value = '';
    } else {
        alert(datos.error || 'No se pudo crear la carpeta.');
    }
}

async function eliminarCarpeta(evento, idCarpeta, nombreCarpeta) {
    if (evento) {
        evento.preventDefault();
        evento.stopPropagation();
    }

    const confirmar = confirm(
        `¿Eliminar la carpeta "${nombreCarpeta}"?\n\n` +
        'Los documentos que estén dentro de esta carpeta también se eliminarán. Esta acción no se puede deshacer.'
    );
    if (!confirmar) return false;

    const boton = evento?.currentTarget || document.querySelector(`.lista-carpetas li[data-id="${idCarpeta}"] .boton-eliminar-carpeta`);
    if (boton) boton.disabled = true;

    try {
        const respuesta = await fetch(`index.php?ruta=api/carpetas/${encodeURIComponent(idCarpeta)}/eliminar`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        const texto = await respuesta.text();
        let datos = {};
        try { datos = texto ? JSON.parse(texto) : {}; } catch (_) {}

        if (!respuesta.ok || datos.error || datos.ok !== true) {
            throw new Error(datos.error || 'No se pudo eliminar la carpeta.');
        }

        const elemento = document.querySelector(`.lista-carpetas li[data-id="${idCarpeta}"]`);
        if (elemento) elemento.remove();

        if (String(carpetaActual) === String(idCarpeta)) {
            const siguiente = document.querySelector('.lista-carpetas li');
            if (siguiente) {
                seleccionarCarpeta(siguiente.dataset.id, siguiente);
            } else {
                carpetaActual = null;
                document.getElementById('id_carpeta_subida').value = '';
                document.getElementById('cuerpo-tabla-documentos').innerHTML =
                    '<tr><td colspan="4">Crea una carpeta para comenzar.</td></tr>';
                document.getElementById('panel-detalle').style.display = 'none';
            }
        }
    } catch (error) {
        if (boton) boton.disabled = false;
        alert(error.message || 'No se pudo conectar con el servidor para eliminar la carpeta.');
    }

    return false;
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
        const textoRespuesta = await respuesta.text();
        let datos = {};
        try {
            datos = textoRespuesta ? JSON.parse(textoRespuesta) : {};
        } catch (_) {
            throw new Error('El servidor devolvió una respuesta no válida. Revisa la configuración de PHP.');
        }

        if (!respuesta.ok || datos.error) {
            throw new Error(datos.error || 'No se pudo subir el documento.');
        } else {
            if (datos.id_carpeta_final && String(datos.id_carpeta_final) !== String(carpetaActual)) {
                const destino = document.querySelector(`.lista-carpetas li[data-id="${datos.id_carpeta_final}"]`);
                if (destino) {
                    seleccionarCarpeta(datos.id_carpeta_final, destino);
                } else {
                    window.location.reload();
                }
            } else {
                await cargarDocumentos(carpetaActual);
            }
        }
    } catch (error) {
        alert(error.message || 'No se pudo subir el documento.');
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


// Compatibilidad con los botones onclick del HTML.
// Las funciones se exponen explícitamente en window para que
// onclick="eliminarCarpeta(...)" siempre pueda encontrarlas.
window.eliminarCarpeta = eliminarCarpeta;
window.seleccionarCarpeta = seleccionarCarpeta;
window.verDetalle = verDetalle;
window.eliminarDocumento = eliminarDocumento;
