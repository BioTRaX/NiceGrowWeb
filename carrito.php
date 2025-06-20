<?php
/*
# Nombre: carrito.php
# Ubicación: carrito.php
# Descripción: Vista del carrito de compras con modificaci\xC3\xB3n de cantidades
*/
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/config/db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qty'])) {
        foreach ($_POST['qty'] as $id => $qty) {
            setQty($pdo, (int)$id, (int)$qty);
        }
    }
    if (isset($_POST['remove'])) {
        removeFromCart((int)$_POST['remove']);
    }
    header('Location: carrito.php');
    exit;
}

$items = cartItems();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="container mt-4">
    <h2>Carrito de compras</h2>
    <?php if ($items): ?>
    <form method="post">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $stmt = $pdo->prepare('SELECT name, price FROM products WHERE id = ?'); ?>
                <?php foreach ($items as $id => $qty): ?>
                    <?php
                        $stmt->execute([$id]);
                        $p = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!$p) continue;
                        $sub = $p['price'] * $qty;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><input type="number" name="qty[<?= $id ?>]" value="<?= $qty ?>" min="1" class="form-control"/></td>
                        <td>$<?= number_format($sub, 2) ?></td>
                        <td>
                            <button name="remove" value="<?= $id ?>" class="btn btn-danger btn-sm">Eliminar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Actualizar carrito</button>
        </div>
    </form>
    <h4>Total: $<?= number_format(cartTotal($pdo), 2) ?></h4>
    <a href="pagar.php" class="btn btn-success mt-3">Proceder al pago</a>
    <?php else: ?>
        <p>El carrito est\xC3\xA1 vac\xC3\xADo.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
