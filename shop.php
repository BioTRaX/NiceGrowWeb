<?php
/*
# Nombre: shop.php
# Ubicación: shop.php
# Descripción: Cat\xC3\xA1logo de productos y adici\xC3\xB3n al carrito reutilizando utilidades
*/
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/config/db.php';

$pdo = getDB();

// Obtener productos activos
$stmt = $pdo->query("SELECT id, name, price, description, img, stock FROM products WHERE active = 1 ORDER BY created_at DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agregar al carrito
if (isset($_GET['agregar'])) {
    $id = (int) $_GET['agregar'];
    addToCart($pdo, $id);
    $redir = 'shop.php';
    if (!empty($_GET['cat'])) {
        $redir .= '?cat=' . urlencode($_GET['cat']);
    }
    header('Location: ' . $redir . '&added=1');
    exit;
}

$added = isset($_GET['added']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="container mt-4">
    <?php if ($added): ?>
        <div class="alert alert-success">Producto a\xC3\xB1adido</div>
    <?php endif; ?>
    <div class="row">
        <?php foreach ($productos as $p): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <?php if (!empty($p['img'])): ?>
                        <img src="assets/img/products/<?= htmlspecialchars($p['img']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                        <p class="card-text">$<?= number_format($p['price'], 2) ?></p>
                        <a href="shop.php?agregar=<?= $p['id'] ?>" class="btn btn-primary">Agregar</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
