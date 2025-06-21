<?php
/*
# Nombre: categories.php
# Ubicación: admin/categories.php
# Descripción: Gestión básica de categorías para administradores
*/
require_once '../includes/auth.php';
requireRole([1]);

$user = getCurrentUser();
$errors = [];
$success = '';

try {
    $db = getDB();

    // Crear tabla si no existe para evitar errores en instalaciones nuevas
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Token CSRF inválido';
        } else {
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            if ($name === '' || $slug === '') {
                $errors[] = 'Nombre y slug son obligatorios';
            } else {
                $stmt = $db->prepare('INSERT INTO categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)');
                $stmt->execute([$name, $slug]);
                $success = 'Categoría guardada correctamente';
            }
        }
    }

    $stmt = $db->query('SELECT id, name, slug FROM categories ORDER BY name');
    $categories = $stmt->fetchAll();

} catch (PDOException $e) {
    $errors[] = 'Error al cargar categorías: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container py-4">
    <h1 class="mb-4">Categorías</h1>

    <?php foreach ($errors as $msg): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endforeach; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="row g-3 mb-4">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <div class="col-md-5">
            <input type="text" name="name" class="form-control" placeholder="Nombre" required>
        </div>
        <div class="col-md-5">
            <input type="text" name="slug" class="form-control" placeholder="Slug" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Agregar</button>
        </div>
    </form>

    <table class="table table-striped table-dark">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Slug</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($categories): ?>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= htmlspecialchars($cat['slug']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" class="text-center">Sin categorías</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
