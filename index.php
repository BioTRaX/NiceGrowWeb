<?php
// archivo: index.php
session_start();

// Simulamos productos
$productos = [
    1 => ["nombre" => "Micelio de Psilocybe", "precio" => 3500],
    2 => ["nombre" => "Kit de Cultivo", "precio" => 5800],
    3 => ["nombre" => "Sustrato estéril", "precio" => 2200],
];

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar al carrito
if (isset($_GET['agregar'])) {
    $id = (int) $_GET['agregar'];
    if (isset($productos[$id])) {
        $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + 1;
    }
    header("Location: index.php");
    exit;
}

// Eliminar del carrito
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    unset($_SESSION['carrito'][$id]);
    header("Location: index.php");
    exit;
}

function total_carrito($productos, $carrito) {
    $total = 0;
    foreach ($carrito as $id => $cantidad) {
        $total += $productos[$id]['precio'] * $cantidad;
    }
    return $total;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nice Grow - Tienda</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f7f7f7; }
        .producto, .carrito { margin: 10px 0; padding: 10px; background: white; border-radius: 6px; }
        .carrito { border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>🛒 Tienda Nice Grow</h1>

    <h2>Productos</h2>
    <?php foreach ($productos as $id => $producto): ?>
        <div class="producto">
            <strong><?= htmlspecialchars($producto['nombre']) ?></strong><br>
            Precio: $<?= $producto['precio'] ?><br>
            <a href="?agregar=<?= $id ?>">Agregar al carrito</a>
        </div>
    <?php endforeach; ?>

    <h2>Carrito</h2>
    <?php if (!empty($_SESSION['carrito'])): ?>
        <div class="carrito">
            <?php foreach ($_SESSION['carrito'] as $id => $cantidad): ?>
                <p><?= $productos[$id]['nombre'] ?> x <?= $cantidad ?> - $<?= $productos[$id]['precio'] * $cantidad ?>
                <a href="?eliminar=<?= $id ?>">[Eliminar]</a></p>
            <?php endforeach; ?>
            <p><strong>Total: $<?= total_carrito($productos, $_SESSION['carrito']) ?></strong></p>

            <!-- Botón de pago de Mercado Pago -->
            <form action="pagar.php" method="POST">
                <button type="submit">Pagar con Mercado Pago</button>
            </form>
        </div>
    <?php else: ?>
        <p>El carrito está vacío.</p>
    <?php endif; ?>
</body>
</html>
