<?php
// views/layout_header.php
$usuario = Sesion::usuarioActual();
$rutaActual = $_GET['ruta'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?? 'Repositorio Inteligente' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if ($usuario): ?>
<div class="barra-superior">
    <a href="index.php?ruta=repositorio" class="marca">
        <span class="marca-icono" aria-hidden="true"></span>
        <span>Repositorio Inteligente</span>
    </a>
    <nav class="nav-principal">
        <a href="index.php?ruta=repositorio" class="nav-link<?= $rutaActual === 'repositorio' ? ' activo' : '' ?>">Repositorio</a>
        <a href="index.php?ruta=consulta" class="nav-link<?= $rutaActual === 'consulta' ? ' activo' : '' ?>">Preguntar a la IA</a>
        <a href="index.php?ruta=dashboard" class="nav-link<?= $rutaActual === 'dashboard' ? ' activo' : '' ?>">Dashboard</a>
    </nav>
    <div class="usuario-actual">
        <span class="avatar-usuario" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($usuario['nombre'], 0, 1))) ?></span>
        <span class="nombre-usuario"><?= htmlspecialchars($usuario['nombre']) ?></span>
        <a href="index.php?ruta=logout" class="nav-link salir">Salir</a>
    </div>
</div>
<?php endif; ?>
