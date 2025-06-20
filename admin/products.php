<?php
/*
# Nombre: products.php
# Ubicación: admin/products.php
# Descripción: Gestión de productos para administradores y vendedores
*/
require_once '../includes/auth.php';
require_once '../includes/upload.php';
require_once '../includes/validators.php';

// Verificar permisos (admin y seller pueden gestionar productos)
requireRole([1, 2]); // admin y seller

$user = getCurrentUser();
$errors = [];
$success = '';
$old = [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category_id' => ''
];

// Obtener productos
try {
    $db = getDB();
    
    // Si es seller, solo mostrar sus productos
    if ($user['role_id'] == 2) {
        $stmt = $db->prepare("
            SELECT p.*, u.username as created_by 
            FROM products p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user['id']]);
    } else {
        // Admin ve todos los productos
        $stmt = $db->query("
            SELECT p.*, u.username as created_by 
            FROM products p 
            LEFT JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC
        ");
    }
    
    $products = $stmt->fetchAll();
    
    // Obtener categorías
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $errors[] = "Error al cargar productos: " . $e->getMessage();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Token CSRF inválido';
        }

        // Obtener y validar campos
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priceInput = $_POST['price'] ?? '';
        $stockInput = $_POST['stock'] ?? '';
        $categoryId = intval($_POST['category_id'] ?? 0);
        $productId = intval($_POST['product_id'] ?? 0);

        $old = [
            'name' => $name,
            'description' => $description,
            'price' => $priceInput,
            'stock' => $stockInput,
            'category_id' => $categoryId
        ];

        if (mb_strlen($name) < 1 || mb_strlen($name) > 255) {
            $errors[] = 'El nombre debe tener entre 1 y 255 caracteres';
        }
        $name = htmlspecialchars($name);

        $description = strip_tags($description, '<strong><em><br>');

        $price = filter_var($priceInput, FILTER_VALIDATE_FLOAT);
        if ($price === false || $price <= 0 || $price > 999999.99) {
            $errors[] = 'Precio inválido';
        }

        $stock = 0;
        if ($stockInput !== '' && $stockInput !== null) {
            $stockVal = filter_var($stockInput, FILTER_VALIDATE_INT);
            if ($stockVal === false || $stockVal < 0) {
                $errors[] = 'Stock inválido';
            } else {
                $stock = $stockVal;
            }
        }

        // Verificar categoría
        $stmt = $db->prepare('SELECT id FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        if (!$stmt->fetch()) {
            $errors[] = 'Categoría no válida';
        }

        // Manejar imagen
        $imageName = null;
        try {
            if (isset($_FILES['image'])) {
                $imageName = validateImage($_FILES['image']);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        if (empty($errors)) {
            switch ($action) {
            case 'create':
                // Crear producto con categoría
                $stmt = $db->prepare("
                    INSERT INTO products (name, description, price, stock, img, user_id, category_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $price, $stock, $imageName, $user['id'], $categoryId]);
                $success = 'Producto creado exitosamente';
                break;

            case 'update':
                // Actualizar producto
                // Verificar permisos
                if ($user['role_id'] == 2) {
                    $stmt = $db->prepare("SELECT user_id FROM products WHERE id = ?");
                    $stmt->execute([$productId]);
                    $product = $stmt->fetch();
                    
                    if (!$product || $product['user_id'] != $user['id']) {
                        throw new Exception('No tienes permisos para editar este producto');
                    }
                }
                
                if ($imageName) {
                    // Eliminar imagen anterior si existe
                    $stmt = $db->prepare("SELECT img FROM products WHERE id = ?");
                    $stmt->execute([$productId]);
                    $oldProduct = $stmt->fetch();
                    if ($oldProduct && $oldProduct['img']) {
                        deleteImage($oldProduct['img']);
                    }
                    
                    // Actualizar con nueva imagen y categoría
                    $stmt = $db->prepare("
                        UPDATE products 
                        SET name = ?, description = ?, price = ?, stock = ?, img = ?, category_id = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $price, $stock, $imageName, $categoryId, $productId]);
                } else {
                    // Actualizar sin cambiar imagen, pero sí categoría
                    $stmt = $db->prepare("
                        UPDATE products 
                        SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $price, $stock, $categoryId, $productId]);
                }
                
                $success = 'Producto actualizado exitosamente';
                break;
                
            case 'delete':
                $productId = intval($_POST['product_id'] ?? 0);
                
                if ($productId > 0) {
                    // Verificar permisos
                    if ($user['role_id'] == 2) {
                        $stmt = $db->prepare("SELECT user_id, img FROM products WHERE id = ?");
                        $stmt->execute([$productId]);
                        $product = $stmt->fetch();
                        
                        if (!$product || $product['user_id'] != $user['id']) {
                            throw new Exception('No tienes permisos para eliminar este producto');
                        }
                    } else {
                        $stmt = $db->prepare("SELECT img FROM products WHERE id = ?");
                        $stmt->execute([$productId]);
                        $product = $stmt->fetch();
                    }
                    
                    // Eliminar imagen si existe
                    if ($product && $product['img']) {
                        deleteImage($product['img']);
                    }
                    
                    // Eliminar producto
                    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$productId]);
                    
                    $success = 'Producto eliminado exitosamente';
                }
                break;
        }

        // Recargar productos después de la acción
        header('Location: products.php?success=' . urlencode($success));
        exit;
        } else {
            $editProduct = [
                'id' => $productId,
                'name' => $name,
                'description' => $description,
                'price' => $priceInput,
                'stock' => $stockInput,
                'category_id' => $categoryId,
                'img' => null,
                'action' => $action
            ];
        }

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Obtener producto para editar
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    try {
        if ($user['role_id'] == 2) {
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
            $stmt->execute([$editId, $user['id']]);
        } else {
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$editId]);
        }
        $editProduct = $stmt->fetch();
        if ($editProduct) {
            $old = [
                'name' => $editProduct['name'],
                'description' => $editProduct['description'],
                'price' => $editProduct['price'],
                'stock' => $editProduct['stock'],
                'category_id' => $editProduct['category_id']
            ];
            $editProduct['action'] = 'update';
        }
    } catch (PDOException $e) {
        $errors[] = "Error al cargar producto: " . $e->getMessage();
    }
}

// Mensaje de éxito desde redirección
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Panel Administrativo | Nice Grow</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: #cbd5e0;
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .brand-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px;
            border-radius: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .btn-action {
            margin: 2px;
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .modal-content {
            border-radius: 15px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
        }
        
        .btn-logout {
            background: rgba(220, 38, 127, 0.2);
            border: 1px solid rgba(220, 38, 127, 0.3);
            color: #fff;
        }
        
        .btn-logout:hover {
            background: rgba(220, 38, 127, 0.3);
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3">
            <h4 class="text-white mb-0">
                <i class="fas fa-seedling text-success"></i>
                Nice Grow
            </h4>
            <small class="text-muted">Panel Admin</small>
        </div>
        
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-user-circle fa-2x text-white"></i>
                </div>
                <div>
                    <div class="text-white fw-bold"><?= htmlspecialchars($user['username']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($user['role_name']) ?></small>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="products.php">
                    <i class="fas fa-box me-2"></i>
                    Productos
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    Usuarios
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>
                    Ver Tienda
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link btn-logout" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="brand-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestión de Productos</h1>
                    <p class="text-muted mb-0">Administra el catálogo de productos</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Producto
                </button>
            </div>
        </div>
        
        <?php foreach ($errors as $msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Products Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>
                    Lista de Productos
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($products)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Creado por</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['img']): ?>
                                            <img src="<?= getImageUrl($product['img']) ?>" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($product['name']) ?></strong>
                                        <?php if ($product['description']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-success fw-bold">$<?= number_format($product['price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger') ?>">
                                            <?= $product['stock'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($product['created_by'] ?? 'Sistema') ?></td>
                                    <td class="text-muted">
                                        <?= date('d/m/Y', strtotime($product['created_at'])) ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-action" 
                                                onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-action" 
                                                onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay productos</h4>
                        <p class="text-muted">Comienza agregando tu primer producto</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                            <i class="fas fa-plus me-2"></i>
                            Agregar Producto
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">
                        <i class="fas fa-plus me-2"></i>
                        Nuevo Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="product_id" id="productId" value="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre del Producto *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($old['name']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($old['description']) ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Precio *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="price" name="price"
                                                       min="0" step="0.01" value="<?= htmlspecialchars($old['price']) ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">Stock</label>
                                            <input type="number" class="form-control" id="stock" name="stock"
                                                   min="0" value="<?= htmlspecialchars($old['stock']) ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Categoría *</label>
                                    <select class="form-select" id="category_id" name="category_id" <?= empty($categories) ? '' : 'required' ?> <?= empty($categories) ? 'disabled' : '' ?>>
                                        <option value="">Seleccionar categoría...</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= ($old['category_id'] == $cat['id'] || (isset($editProduct['category_id']) && $editProduct['category_id'] == $cat['id'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($categories)): ?>
                                        <div class="form-text text-danger">No hay categorías disponibles.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Imagen</label>
                                    <input type="file" class="form-control" id="image" name="image" 
                                           accept="image/*">
                                    <div class="form-text">JPG, PNG o WebP. Máximo 2MB</div>
                                </div>
                                
                                <div id="imagePreview" class="mt-3 text-center" style="display: none;">
                                    <img id="previewImg" src="" alt="Preview" 
                                         style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash text-danger me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar el producto <strong id="deleteProductName"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" id="deleteProductId">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Preview de imagen
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    const img = document.getElementById('previewImg');
                    img.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        function openProductForm(data) {
            document.getElementById('formAction').value = data.action || 'create';
            document.getElementById('productId').value = data.id || '';
            document.getElementById('name').value = data.name || '';
            document.getElementById('description').value = data.description || '';
            document.getElementById('price').value = data.price || '';
            document.getElementById('stock').value = data.stock || '';
            document.getElementById('category_id').value = data.category_id || '';

            if (data.action === 'update') {
                document.getElementById('productModalTitle').innerHTML =
                    '<i class="fas fa-edit me-2"></i>Editar Producto';
                document.getElementById('submitBtn').innerHTML =
                    '<i class="fas fa-save me-2"></i>Actualizar';
            } else {
                document.getElementById('productModalTitle').innerHTML =
                    '<i class="fas fa-plus me-2"></i>Nuevo Producto';
                document.getElementById('submitBtn').innerHTML =
                    '<i class="fas fa-save me-2"></i>Guardar';
            }

            if (data.img) {
                const preview = document.getElementById('imagePreview');
                const img = document.getElementById('previewImg');
                img.src = '<?= getImageUrl('') ?>' + data.img;
                preview.style.display = 'block';
            }

            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        }

        // Editar producto
        function editProduct(product) {
            product.action = 'update';
            openProductForm(product);
        }
        
        // Eliminar producto
        function deleteProduct(id, name) {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteProductName').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        // Reset modal al cerrar
        document.getElementById('productModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('productForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('productId').value = '';
            document.getElementById('productModalTitle').innerHTML = 
                '<i class="fas fa-plus me-2"></i>Nuevo Producto';
            document.getElementById('submitBtn').innerHTML = 
                '<i class="fas fa-save me-2"></i>Guardar';
            document.getElementById('imagePreview').style.display = 'none';
        });
        
        <?php if ($editProduct): ?>
        // Auto-abrir modal con datos previos
        document.addEventListener('DOMContentLoaded', function() {
            openProductForm(<?= json_encode($editProduct) ?>);
        });
        <?php endif; ?>
    </script>
</body>
</html>
