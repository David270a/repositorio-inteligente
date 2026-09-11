<?php
$tituloPagina = 'Repositorio';
require __DIR__ . '/layout_header.php';
$carpetas = Carpeta::listarPorUsuario($usuario['id_usuario']);
?>

<div class="contenedor">
    <div class="tarjeta">
        <input type="text" id="buscador-texto" placeholder="Buscar dentro del contenido de tus documentos..."
               onkeypress="if(event.key==='Enter') buscarTexto()">
    </div>

    <div class="layout-repositorio">
        <div class="tarjeta">
            <h3>Carpetas</h3>
            <ul class="lista-carpetas">
                <?php foreach ($carpetas as $carpeta): ?>
                    <li data-id="<?= $carpeta['id_carpeta'] ?>"
                        onclick="seleccionarCarpeta(<?= $carpeta['id_carpeta'] ?>, this)">
                        <span class="nombre-carpeta"><span class="icono-carpeta">▣</span><?= htmlspecialchars($carpeta['nombre']) ?></span>
                        <button type="button" class="boton-eliminar-carpeta"
                                onclick="eliminarCarpeta(event, <?= $carpeta['id_carpeta'] ?>, <?= htmlspecialchars(json_encode($carpeta['nombre']), ENT_QUOTES, 'UTF-8') ?>)"
                                title="Eliminar carpeta" aria-label="Eliminar carpeta"><span aria-hidden="true">🗑</span></button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form id="form-nueva-carpeta" style="margin-top:16px;">
                <input type="text" id="nombre_carpeta" placeholder="Nueva carpeta" required>
                <button type="submit">+ Crear carpeta</button>
            </form>
        </div>

        <div>
            <div class="tarjeta">
                <h3>Subir documento (PDF, DOCX o TXT)</h3>
                <form id="form-subir-documento" enctype="multipart/form-data">
                    <input type="hidden" name="id_carpeta" id="id_carpeta_subida">
                    <input type="file" name="documento" accept=".pdf,.docx,.txt" required>
                    <button type="submit">Subir documento</button>
                </form>
            </div>

            <div class="tarjeta">
                <h3>Documentos</h3>
                <table>
                    <thead>
                        <tr><th>Nombre</th><th>Categoría</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="cuerpo-tabla-documentos">
                        <tr><td colspan="4">Selecciona una carpeta.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="tarjeta" id="panel-detalle" style="display:none;"></div>
            <div class="tarjeta" id="panel-busqueda" style="display:none;">
                <h3>Resultados de búsqueda</h3>
                <div id="resultados-busqueda"></div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/repositorio.js?v=20260911"></script>
<script>
async function buscarTexto() {
    const termino = document.getElementById('buscador-texto').value.trim();
    if (!termino) return;

    const respuesta = await fetch(`index.php?ruta=api/buscar&q=${encodeURIComponent(termino)}`);
    const resultados = await respuesta.json();

    const panel = document.getElementById('panel-busqueda');
    panel.style.display = 'block';
    document.getElementById('resultados-busqueda').innerHTML = resultados.length
        ? resultados.map(r => `<p><strong>${r.nombre_archivo}</strong> — categoría: ${r.categoria || '—'}</p>`).join('')
        : '<p>No se encontraron documentos que contengan ese término.</p>';
}
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
