<?php $tituloPagina = 'Iniciar sesión'; require __DIR__ . '/layout_header.php'; ?>

<div class="contenedor form-login">
    <div class="tarjeta">
        <h2>Iniciar sesión</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="index.php?ruta=login">
            <label>Correo electrónico</label>
            <input type="email" name="correo" required autofocus>
            <label>Contraseña</label>
            <input type="password" name="password" required>
            <button type="submit">Ingresar</button>
        </form>
        <p style="margin-top:14px; font-size:14px;">
            ¿No tienes cuenta? <a href="index.php?ruta=registro">Regístrate aquí</a>
        </p>
        <p style="margin-top:10px; font-size:12px; color:#888;">
            Usuario de prueba: admin@demo.com / Admin1234
        </p>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
