<?php
/*
# Nombre: logout.php
# Ubicación: admin/logout.php
# Descripción: Cierra la sesión y redirige al login
*/
require_once '../includes/auth.php';
logout();
header('Location: login.php');
exit;
?>
