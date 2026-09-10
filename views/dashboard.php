<?php
$tituloPagina = 'Dashboard';
require __DIR__ . '/layout_header.php';
$esAdmin = ($usuario['rol'] ?? '') === 'administrador';
?>

<div class="contenedor">
    <div class="tarjeta">
        <h3>Indicadores del repositorio</h3>
        <div class="indicadores">
            <div class="indicador">
                <div class="numero" id="indicador-total">–</div>
                <div class="etiqueta">Documentos totales</div>
            </div>
        </div>
    </div>

    <div class="layout-repositorio">
        <div class="tarjeta">
            <h4>Por categoría</h4>
            <table>
                <thead><tr><th>Categoría</th><th>Cantidad</th></tr></thead>
                <tbody id="lista-por-categoria"><tr><td colspan="2">Cargando...</td></tr></tbody>
            </table>
        </div>
        <div class="tarjeta">
            <h4>Por estado de procesamiento</h4>
            <table>
                <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
                <tbody id="lista-por-estado"><tr><td colspan="2">Cargando...</td></tr></tbody>
            </table>
        </div>
    </div>

    <?php if ($esAdmin): ?>
    <div class="tarjeta">
        <h3>Registro de errores y estados (administrador)</h3>
        <table>
            <thead><tr><th>Documento</th><th>Usuario</th><th>Estado</th><th>Mensaje</th><th>Fecha</th></tr></thead>
            <tbody>
                <?php foreach (ProcesamientoLog::listarTodos(50) as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['nombre_archivo']) ?></td>
                    <td><?= htmlspecialchars($log['usuario']) ?></td>
                    <td><span class="badge badge-<?= $log['estado'] ?>"><?= $log['estado'] ?></span></td>
                    <td><?= htmlspecialchars($log['mensaje_error'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['fecha_evento']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="assets/js/dashboard.js"></script>

<?php require __DIR__ . '/layout_footer.php'; ?>
