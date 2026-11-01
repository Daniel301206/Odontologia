<?php
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? 'Sonrisa Perfecta';
$activeModule = $moduleKey ?? (basename((string)($_SERVER['SCRIPT_NAME'] ?? ''), '.php') === 'home' ? 'inicio' : '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | Sonrisa Perfecta</title>
    <link rel="stylesheet" href="<?= h($basePath) ?>assets/css/estilos.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="workspace">
        <header class="site-header">
            <div class="brand-row">
                <button class="sidebar-toggle" type="button" aria-label="Abrir navegación" aria-expanded="false" aria-controls="app-sidebar">☰</button>
                <?php if (!empty($_SESSION['user'])): ?>
                    <span class="user-name"><?= h($_SESSION['user']['nombre']) ?></span>
                    <a class="button button-outline logout-link" href="<?= h($basePath) ?>views/auth/logout.php">Cerrar sesión</a>
                <?php else: ?>
                    <a class="button button-outline logout-link" href="<?= h($basePath) ?>views/login.php">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </header>
        <div class="page-shell">
