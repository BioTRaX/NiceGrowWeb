<?php
/*
# Nombre: header.php
# Ubicación: includes/header.php
# Descripción: Encabezado con barra de navegación y conteo del carrito
*/
require_once __DIR__ . '/cart.php';
?>
<header>
    <h1><a href="index.php">Nice Grow</a></h1>
    <nav>
        <a href="index.php">Inicio</a>
        <a href="shop.php">Tienda</a>
        <a href="contact.php">Contacto</a>
        <a href="atencion.php">Atenci&oacute;n al cliente</a>
        <a href="carrito.php">🛒 (<?= cartCount() ?>)</a>
        <a href="admin/login.php" style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px;">Admin</a>
    </nav>
</header>
