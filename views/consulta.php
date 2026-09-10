<?php $tituloPagina = 'Preguntar a la IA'; require __DIR__ . '/layout_header.php'; ?>

<div class="contenedor">
    <div class="tarjeta chat-consulta">
        <h3>Pregúntale a tu repositorio</h3>
        <p style="color:#888; font-size:13px; margin-top:-8px;">
            Elige si quieres preguntar sobre todos tus documentos, sobre uno en concreto, o hablar libremente con la IA.
        </p>

        <div style="margin-bottom:14px;">
            <label>¿Sobre qué quieres preguntar?</label>
            <select id="selector-documento">
                <option value="todos">Todos mis documentos</option>
                <option value="libre">Chat libre (sin usar mis documentos)</option>
            </select>
        </div>

        <div class="chat-mensajes" id="chat-mensajes"></div>
        <form id="form-consulta" class="chat-input">
            <textarea id="pregunta" placeholder="Escribe tu pregunta..." required></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>
</div>

<script src="assets/js/consulta.js"></script>

<?php require __DIR__ . '/layout_footer.php'; ?>
