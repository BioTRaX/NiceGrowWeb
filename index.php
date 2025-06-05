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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nice Grow - Tienda</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <header>
        <h1>Nice Grow</h1>
        <nav>
            <a href="#">Inicio</a>
            <a href="#">Tienda</a>
            <a href="#">Contacto</a>
        </nav>
    </header>

    <section class="hero">
        <h2>Cultivá con estilo y conciencia 🌱</h2>
        <p>Insumos premium para tu cultivo de hongos y plantas</p>
    </section>

    <section class="productos">
        <?php foreach ($productos as $id => $producto): ?>
            <div class="producto">
                <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                <p>Precio: $<?= $producto['precio'] ?></p>
                <a href="?agregar=<?= $id ?>">Agregar al carrito</a>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="carrito">
        <h2>🛒 Carrito</h2>
        <?php if (!empty($_SESSION['carrito'])): ?>
            <?php foreach ($_SESSION['carrito'] as $id => $cantidad): ?>
                <p><?= $productos[$id]['nombre'] ?> x <?= $cantidad ?> - $<?= $productos[$id]['precio'] * $cantidad ?>
                <a href="?eliminar=<?= $id ?>">[Eliminar]</a></p>
            <?php endforeach; ?>
            <p><strong>Total: $<?= total_carrito($productos, $_SESSION['carrito']) ?></strong></p>
            <form action="pagar.php" method="POST">
                <button type="submit">Pagar con Mercado Pago</button>
            </form>
        <?php else: ?>
            <p>El carrito está vacío.</p>
        <?php endif; ?>
    </section>

    <footer>
        © <?= date('Y') ?> Nice Grow. Todos los derechos reservados.
    </footer>
    <script src="assets/js/funciones.js"></script>
</body>
</html>
