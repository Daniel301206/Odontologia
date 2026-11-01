<?php
$activeModule = $activeModule ?? 'inicio';
$sidebarModules = modules();
$sidebarLinks = [
    'inicio' => ['Inicio', '⌂', 'views/home.php'],
    'pacientes' => [$sidebarModules['pacientes']['title'], '♙', 'views/modulos/pacientes.php'],
    'odontologos' => [$sidebarModules['odontologos']['title'], '⚕', 'views/modulos/odontologos.php'],
    'citas' => [$sidebarModules['citas']['title'], '◷', 'views/modulos/citas.php'],
    'servicios' => [$sidebarModules['servicios']['title'], '✚', 'views/modulos/servicios.php'],
    'pagos' => [$sidebarModules['pagos']['title'], '＄', 'views/modulos/pagos.php'],
    'reportes' => ['Reportes', '◧', 'views/modulos/reportes.php'],
];
?>
<aside class="side-menu" id="app-sidebar">
    <a class="sidebar-brand" href="<?= h($basePath) ?>views/home.php">
        <span class="brand-mark" aria-hidden="true">✦</span>
        <span><strong>Sonrisa Perfecta</strong><small>Clínica odontológica</small></span>
    </a>
    <nav aria-label="Navegación principal">
        <?php foreach ($sidebarLinks as $sidebarItemKey => [$sidebarLabel, $sidebarIcon, $sidebarPath]): ?>
            <a href="<?= h($basePath . $sidebarPath) ?>" class="<?= $activeModule === $sidebarItemKey ? 'active' : '' ?>" <?= $activeModule === $sidebarItemKey ? 'aria-current="page"' : '' ?>>
                <span class="sidebar-icon" aria-hidden="true"><?= h($sidebarIcon) ?></span><span><?= h($sidebarLabel) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="sidebar-backdrop" data-sidebar-close></div>
<script>
(() => {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.getElementById('app-sidebar');
    const closeLayer = document.querySelector('[data-sidebar-close]');
    if (!toggle || !sidebar || !closeLayer) return;
    const setOpen = (open) => {
        document.body.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Cerrar navegación' : 'Abrir navegación');
    };
    toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('sidebar-open')));
    closeLayer.addEventListener('click', () => setOpen(false));
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));
})();
</script>
