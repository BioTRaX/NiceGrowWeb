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
</head>
<body>
<h1>Categorías</h1>
<?php foreach (\$errors as \$msg): ?>
<p style="color:red"><?= htmlspecialchars(\$msg) ?></p>
<?php endforeach; ?>
<?php if (\$success): ?>
<p style="color:green"><?= htmlspecialchars(\$success) ?></p>
<?php endif; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <label>Nombre:<input type="text" name="name"></label>
    <label>Slug:<input type="text" name="slug"></label>
    <button type="submit">Agregar</button>
</form>
<table border="1">
<thead><tr><th>ID</th><th>Nombre</th><th>Slug</th></tr></thead>
<tbody>
<?php foreach (\$categories as \$cat): ?>
<tr><td><?= \$cat['id'] ?></td><td><?= htmlspecialchars(\$cat['name']) ?></td><td><?= htmlspecialchars(\$cat['slug']) ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
