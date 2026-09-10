<?php $tituloPagina = 'Crear cuenta'; require __DIR__ . '/layout_header.php'; ?>

<div class="contenedor form-login">
    <div class="tarjeta">
        <h2>Crear cuenta</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="index.php?ruta=registro">
            <label>Nombre completo</label>
            <input type="text" name="nombre" required autofocus>
            <label>Correo electrónico</label>
            <input type="email" name="correo" required>
            <label>Contraseña (mínimo 6 caracteres)</label>
            <input type="password" name="password" minlength="6" required>
            <button type="submit">Registrarme</button>
        </form>
        <p style="margin-top:14px; font-size:14px;">
            ¿Ya tienes cuenta? <a href="index.php?ruta=login">Inicia sesión</a>
        </p>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
