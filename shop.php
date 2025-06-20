<?php
/*
# Nombre: shop.php
# Ubicación: shop.php
# Descripción: Tienda con filtros de categoría y precio
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


session_start();
require_once __DIR__ . '/config/db.php';

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar al carrito
if (isset($_GET['agregar'])) {
    $id = (int)$_GET['agregar'];
    $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + 1;
    header('Location: shop.php');
    exit;
}

// Obtener filtros desde GET
$cat = $_GET['cat'] ?? 'all';
$min = $_GET['min'] ?? '';
$max = $_GET['max'] ?? '';

// Obtener categorías
$categories = [];
try {
    $stmt = $pdo->query("SELECT slug, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Construir consulta de productos
$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1";
$params = [];
if ($cat !== 'all') {
    $sql .= " AND c.slug = :slug";
    $params['slug'] = $cat;
}
if ($min !== '' && is_numeric($min) && $min >= 0) {
    $sql .= " AND p.price >= :min";
    $params['min'] = $min;
}
if ($max !== '' && is_numeric($max) && $max >= 0) {
    $sql .= " AND p.price <= :max";
    $params['max'] = $max;
}
if (isset($params['min'], $params['max']) && $params['min'] > $params['max']) {
    unset($params['min'], $params['max']);
    $min = $max = '';
}
$sql .= " ORDER BY p.name";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue(':' . $key, $val);
    }
    $stmt->execute();
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
}
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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <form class="row g-3 mb-4" method="get">
        <div class="col-md-4">
            <select name="cat" class="form-select" onchange="this.form.submit()">
                <option value="all">Todas</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c['slug']) ?>" <?= $c['slug'] === $cat ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="min" class="form-control" value="<?= htmlspecialchars($min) ?>">
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="max" class="form-control" value="<?= htmlspecialchars($max) ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
        <div class="col-md-2 align-self-end">
            <a href="shop.php" class="btn btn-secondary w-100">Reset</a>
        </div>
    </form>

    <div class="row g-4">
        <?php if ($productos): ?>
            <?php foreach ($productos as $p): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($p['img'] ?: 'assets/img/placeholder.svg') ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                            <p class="card-text mb-4">$<?= number_format($p['price'], 2, ',', '.') ?></p>
                            <a href="?agregar=<?= $p['id'] ?>" class="btn btn-success mt-auto">Agregar al carrito</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning">No se encontraron productos.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
