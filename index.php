<?php
/*
# Nombre: index.php
# Ubicación: index.php
# Descripción: Página principal con catálogo, carrito y sección hero
*/
session_start();
require_once 'config/db.php';

// Obtener productos desde la base de datos
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM products WHERE active = 1 ORDER BY created_at DESC");
    $productosDB = $stmt->fetchAll();
    
    // Formatear productos para compatibilidad con el código existente
    $productos = [];
    foreach ($productosDB as $prod) {
        $productos[$prod['id']] = [
            "nombre" => $prod['name'],
            "precio" => $prod['price'],
            "descripcion" => $prod['description'],
            "imagen" => $prod['img'],
            "stock" => $prod['stock']
        ];
    }
} catch (PDOException $e) {
    // En caso de error, usar productos por defecto
    $productos = [
        1 => ["nombre" => "Micelio de Psilocybe", "precio" => 3500],
        2 => ["nombre" => "Kit de Cultivo", "precio" => 5800],
        3 => ["nombre" => "Sustrato estéril", "precio" => 2200],
    ];
}

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
    <?php include __DIR__ . '/includes/header.php'; ?>

    <section class="hero container text-center py-5" style="background: linear-gradient(135deg, #6A1B9A, #388E3C);">
        <h1>Cultivá con estilo y conciencia 🌱</h1>
        <p>Insumos premium para tu cultivo de hongos y plantas</p>
        <a href="/NiceGrowWeb/shop.php" class="btn btn-light btn-lg" role="button" aria-label="Ir a la tienda">Ir a la tienda</a>
    </section>
    <section class="productos">
        <?php foreach ($productos as $id => $producto): ?>
            <div class="producto">
                <?php if (!empty($producto['imagen'])): ?>
                    <img src="assets/img/products/<?= htmlspecialchars($producto['imagen']) ?>" 
                         alt="<?= htmlspecialchars($producto['nombre']) ?>"
                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">
                <?php endif; ?>
                <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                <?php if (!empty($producto['descripcion'])): ?>
                    <p class="descripcion"><?= htmlspecialchars($producto['descripcion']) ?></p>
                <?php endif; ?>
                <p class="precio">Precio: $<?= number_format($producto['precio'], 2) ?></p>
                <?php if (isset($producto['stock'])): ?>
                    <p class="stock">Stock: <?= $producto['stock'] ?></p>
                <?php endif; ?>
                <a href="?agregar=<?= $id ?>" class="btn-agregar">Agregar al carrito</a>
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
