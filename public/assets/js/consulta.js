// public/assets/js/consulta.js

document.addEventListener('DOMContentLoaded', cargarDocumentosEnSelector);

document.getElementById('form-consulta').addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const input = document.getElementById('pregunta');
    const pregunta = input.value.trim();
    if (!pregunta) return;

    const selector = document.getElementById('selector-documento');
    const valorSeleccionado = selector.value; // 'todos' | 'libre' | id numérico del documento

    agregarMensaje(pregunta, 'usuario');
    input.value = '';

    const idMensajeCargando = agregarMensaje('Pensando...', 'ia');

    // Traducir la selección del combo a los parámetros que espera el backend
    let modo = 'documentos';
    let idDocumento = '';
    if (valorSeleccionado === 'libre') {
        modo = 'libre';
    } else if (valorSeleccionado !== 'todos') {
        idDocumento = valorSeleccionado; // id de un documento específico
    }

    try {
        const cuerpo = new URLSearchParams({ pregunta, modo, id_documento: idDocumento });
        const respuesta = await fetch('index.php?ruta=api/consulta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: cuerpo.toString(),
        });
        const datos = await respuesta.json();

        const elemento = document.getElementById(idMensajeCargando);
        if (datos.error) {
            elemento.querySelector('.texto').textContent = 'Ocurrió un error: ' + datos.error;
        } else {
            elemento.querySelector('.texto').textContent = datos.respuesta;
            if (datos.fuentes && datos.fuentes.length) {
                const fuentes = document.createElement('div');
                fuentes.className = 'fuentes';
                fuentes.textContent = 'Fuentes: ' + datos.fuentes.join(', ');
                elemento.appendChild(fuentes);
            }
        }
    } catch (e) {
        document.getElementById(idMensajeCargando).querySelector('.texto').textContent =
            'No fue posible conectar con el servidor.';
    }
});

async function cargarDocumentosEnSelector() {
    const selector = document.getElementById('selector-documento');
    try {
        const respuesta = await fetch('index.php?ruta=api/documentos');
        const documentos = await respuesta.json();

        documentos.forEach((doc) => {
            const opcion = document.createElement('option');
            opcion.value = doc.id_documento;
            opcion.textContent = doc.nombre_archivo + (doc.categoria ? ` (${doc.categoria})` : '');
            selector.appendChild(opcion);
        });
    } catch (e) {
        console.error('No se pudo cargar el listado de documentos', e);
    }
}

function agregarMensaje(texto, tipo) {
    const contenedor = document.getElementById('chat-mensajes');
    const id = 'msg-' + Date.now() + Math.random().toString(36).slice(2);

    const div = document.createElement('div');
    div.className = `mensaje ${tipo}`;
    div.id = id;
    div.innerHTML = `<div class="texto"></div>`;
    div.querySelector('.texto').textContent = texto;

    contenedor.appendChild(div);
    contenedor.scrollTop = contenedor.scrollHeight;
    return id;
}


// Enter envía la pregunta; Shift + Enter permite un salto de línea.
document.getElementById('pregunta').addEventListener('keydown', (evento) => {
    if (evento.key === 'Enter' && !evento.shiftKey) {
        evento.preventDefault();
        document.getElementById('form-consulta').requestSubmit();
    }
});
