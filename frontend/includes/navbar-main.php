<?php if (!defined('BOOTSTRAP_ICONS_LOADED')): ?>
    <?php define('BOOTSTRAP_ICONS_LOADED', true); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<?php endif; ?>

<?php require_once __DIR__ . '/user-context.php'; ?>

<nav class="navbar">

    <div class="logo">Welcome to Luke's Tiny House</div>

    <ul class="nav-links">
        <li><a class="navbar-link" href="index.php" title="Inicio">Inicio</a></li>
        <li><a class="navbar-link" href="informacion.php" title="Informacion">Informacion</a></li>

        <?php if (!$isLoggedIn): ?>
            <li><a class="navbar-link" href="galeria.php" title="Galeria">Galeria</a></li>
            <li><a class="navbar-link" href="panel-de-acceso.php" title="Panel de acceso">Panel de acceso</a></li>
        <?php else: ?>
            <li><a class="navbar-link" href="galeria.php" title="Galeria">Galeria</a></li>
            <li><a class="navbar-link" href="Recomendaciones.php" title="Recomendaciones">Recomendaciones</a></li>
            <?php if ($isOwner): ?>
                <li><a class="navbar-link" href="Panel-admin.php" title="Panel admin">Panel admin</a></li>
            <?php endif; ?>
            <li><a class="navbar-link" href="terminos-y-condiciones.php" title="Terminos y condiciones">Terminos</a></li>
            <li><a class="navbar-link" href="https://www.facebook.com/lucas.zuniga.5492" title="Facebook"><i class="bi bi-facebook"></i> Facebook</a></li>
            <li><a class="navbar-link" href="https://www.instagram.com/lucas_zuniga_2006/" title="Instagram"><i class="bi bi-instagram"></i> Instagram</a></li>
            <li><a class="navbar-link" href="mailto:lucaszv2006@gmail.com" title="Gmail"><i class="bi bi-envelope-fill"></i> Gmail</a></li>
            <li><a class="navbar-link" href="tel:+50683256836" title="Contacto"><i class="bi bi-telephone-fill"></i> Contacto</a></li>
            <li class="profile-chip">
                <span class="profile-name" title="<?php echo htmlspecialchars($email !== '' ? $email : $displayName, ENT_QUOTES, 'UTF-8'); ?>">
                    Perfil: <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <form action="logout.php" method="post" style="display:inline;margin:0;">
                    <button type="submit" class="navbar-link" title="Cerrar sesion" style="background:none;border:none;padding:0;cursor:pointer;font:inherit;">Salir</button>
                </form>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<?php if ($isOwner): ?>
    <a class="admin-shortcut" href="Panel-admin.php" title="Acceso directo admin">Admin</a>
<?php endif; ?>
