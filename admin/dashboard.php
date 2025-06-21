<?php
/*
# Nombre: dashboard.php
# Ubicación: admin/dashboard.php
# Descripción: Panel de control con estadísticas generales
*/
require_once '../includes/auth.php';

// Requiere estar logueado (cualquier rol)
requireRole([1,2]);

$user = getCurrentUser();

// Obtener estadísticas
try {
    $db = getDB();
    
    // Contar productos
    $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE active = 1");
    $totalProducts = $stmt->fetch()['total'];
    
    // Contar usuarios
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE active = 1");
    $totalUsers = $stmt->fetch()['total'];
    
    // Productos recientes
    $stmt = $db->query("
        SELECT p.*, u.username as created_by 
        FROM products p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.active = 1 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $recentProducts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Administrativo | Nice Grow</title>
    
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
            transition: all 0.3s ease;
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
          .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .stat-content {
            flex: 1;
        }
        
        .stat-card .stat-icon {
            font-size: 3rem;
            opacity: 0.8;
            margin-left: 1rem;
        }
        
        .stat-card h6 {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
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
            margin-bottom: 0;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin: 10px;
            border-radius: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stat-card {
                text-align: center;
                flex-direction: column;
                gap: 1rem;
            }
            
            .stat-card .stat-icon {
                margin-left: 0;
                font-size: 2.5rem;
            }
            
            .brand-header {
                padding: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .stat-card h2 {
                font-size: 2rem;
            }
            
            .stat-card .stat-icon {
                font-size: 2rem;
            }
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
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>
                    Productos
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags me-2"></i>
                    Categorías
                </a>
            </li>
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
            <h1 class="h3 mb-0">Dashboard</h1>
            <p class="text-muted mb-0">Bienvenido al panel de administración</p>
        </div>
          <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="/admin/products.php" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-content">
                        <h6 class="text-uppercase mb-1">Productos</h6>
                        <h2 class="mb-0"><?= $totalProducts ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="/admin/users.php" class="text-decoration-none">
                <div class="stat-card success">
                    <div class="stat-content">
                        <h6 class="text-uppercase mb-1">Usuarios</h6>
                        <h2 class="mb-0"><?= $totalUsers ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="/admin/products.php?lowstock=1" class="text-decoration-none">
                <div class="stat-card warning">
                    <div class="stat-content">
                        <h6 class="text-uppercase mb-1">Stock Bajo</h6>
                        <h2 class="mb-0">3</h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                </a>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card info">
                    <div class="stat-content">
                        <h6 class="text-uppercase mb-1">Ventas Hoy</h6>
                        <h2 class="mb-0">$0</h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Products -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-box me-2"></i>
                            Productos Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentProducts)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Precio</th>
                                            <th>Stock</th>
                                            <th>Creado por</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentProducts as $product): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <a href="/admin/products.php?edit=<?= $product['id'] ?>">
                                                    <?= htmlspecialchars($product['name']) ?>
                                                </a>
                                            </td>
                                            <td class="text-success">$<?= number_format($product['price'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger') ?>">
                                                    <?= $product['stock'] ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($product['created_by'] ?? 'Sistema') ?></td>
                                            <td class="text-muted">
                                                <?= date('d/m/Y', strtotime($product['created_at'])) ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay productos registrados</p>
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                    Agregar Producto
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Acciones Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>
                                Nuevo Producto
                            </a>
                            
                            <?php if (isAdmin()): ?>
                            <a href="users.php" class="btn btn-outline-success">
                                <i class="fas fa-user-plus me-2"></i>
                                Nuevo Usuario
                            </a>
                            <?php endif; ?>
                            
                            <a href="../index.php" target="_blank" class="btn btn-outline-info">
                                <i class="fas fa-eye me-2"></i>
                                Ver Tienda
                            </a>
                        </div>
                        
                        <hr class="my-3">
                        
                        <h6 class="text-muted mb-2">Tu Información</h6>
                        <ul class="list-unstyled">
                            <li><strong>Usuario:</strong> <?= htmlspecialchars($user['username']) ?></li>
                            <li><strong>Rol:</strong> <?= htmlspecialchars($user['role_name']) ?></li>
                            <li><strong>Sesión desde:</strong> <?= date('d/m/Y H:i', $_SESSION['login_time'] ?? time()) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
