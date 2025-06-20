<?php
/*
# Nombre: pagar.php
# Ubicación: pagar.php
# Descripción: Muestra la confirmación del pago simulado y limpia el carrito
*/
// En un entorno real, aquí se integraría la API de Mercado Pago
// Para este ejemplo solo se mostrará un mensaje de confirmación.

require_once __DIR__ . '/includes/cart.php';
// Vaciar el carrito después del pago simulado
$_SESSION['cart'] = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago</title>
</head>
<body>
    <h1>Gracias por tu compra</h1>
    <p>El pago se procesó correctamente. (Simulado)</p>
    <a href="index.php">Volver a la tienda</a>
</body>
</html>
