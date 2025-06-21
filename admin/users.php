<?php
/*
# Nombre: users.php
# Ubicación: admin/users.php
# Descripción: Administración de usuarios y asignación de roles
*/
require_once '../includes/auth.php';

// Solo administradores pueden gestionar usuarios
requireRole([1]); // solo admin

$user = getCurrentUser();
$error = '';
$success = '';

// Obtener usuarios y roles
try {
    $db = getDB();
    
    // Obtener usuarios
    $stmt = $db->query("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
    
    // Obtener roles para el formulario
    $stmt = $db->query("SELECT * FROM roles ORDER BY id");
    $roles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        // Verificar token CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token CSRF inválido');
        }
        
        switch ($action) {
            case 'create':
            case 'update':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $roleId = intval($_POST['role_id'] ?? 0);
                $active = isset($_POST['active']) ? 1 : 0;
                $userId = intval($_POST['user_id'] ?? 0);
                
                if (empty($username) || empty($email) || $roleId <= 0) {
                    throw new Exception('Usuario, email y rol son obligatorios');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email no válido');
                }
                
                if ($action === 'create') {
                    // Crear usuario
                    if (empty($password)) {
                        throw new Exception('La contraseña es obligatoria para usuarios nuevos');
                    }
                    
                    if (strlen($password) < 6) {
                        throw new Exception('La contraseña debe tener al menos 6 caracteres');
                    }
                    
                    // Verificar que no exista el usuario o email
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        throw new Exception('Ya existe un usuario con ese nombre o email');
                    }
                    
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("
                        INSERT INTO users (username, email, password, role_id, active) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $email, $hashedPassword, $roleId, $active]);
                    
                    $success = 'Usuario creado exitosamente';
                    
                } else {
                    // Actualizar usuario
                    if ($userId === $user['id'] && !$active) {
                        throw new Exception('No puedes desactivar tu propia cuenta');
                    }
                    
                    // Verificar que no exista otro usuario con el mismo username/email
                    $stmt = $db->prepare("
                        SELECT id FROM users 
                        WHERE (username = ? OR email = ?) AND id != ?
                    ");
                    $stmt->execute([$username, $email, $userId]);
                    if ($stmt->fetch()) {
                        throw new Exception('Ya existe otro usuario con ese nombre o email');
                    }
                    
                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                            throw new Exception('La contraseña debe tener al menos 6 caracteres');
                        }
                        
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("
                            UPDATE users 
                            SET username = ?, email = ?, password = ?, role_id = ?, active = ?, 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $email, $hashedPassword, $roleId, $active, $userId]);
                    } else {
                        $stmt = $db->prepare("
                            UPDATE users 
                            SET username = ?, email = ?, role_id = ?, active = ?, 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $email, $roleId, $active, $userId]);
                    }
                    
                    $success = 'Usuario actualizado exitosamente';
                }
                break;
                
            case 'delete':
                $userId = intval($_POST['user_id'] ?? 0);
                
                if ($userId > 0) {
                    if ($userId === $user['id']) {
                        throw new Exception('No puedes eliminar tu propia cuenta');
                    }
                    
                    // Verificar que no sea el único admin
                    $stmt = $db->query("SELECT COUNT(*) as admin_count FROM users WHERE role_id = 1 AND active = 1");
                    $adminCount = $stmt->fetch()['admin_count'];
                    
                    $stmt = $db->prepare("SELECT role_id FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $userToDelete = $stmt->fetch();
                    
                    if ($userToDelete['role_id'] == 1 && $adminCount <= 1) {
                        throw new Exception('No puedes eliminar el último administrador');
                    }
                    
                    // Eliminar usuario
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $success = 'Usuario eliminado exitosamente';
                }
                break;
        }
        
        // Recargar usuarios después de la acción
        header('Location: users.php?success=' . urlencode($success));
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener usuario para editar
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$editId]);
        $editUser = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error al cargar usuario: " . $e->getMessage();
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
    <title>Gestión de Usuarios - Panel Administrativo | Nice Grow</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="/NiceGrowWeb/assets/css/admin-dark.css">

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
        
        .role-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
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
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i>
                    Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags me-2"></i>
                    Categorías
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    Usuarios
                </a>
            </li>
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
                    <h1 class="h3 mb-0">Gestión de Usuarios</h1>
                    <p class="text-muted mb-0">Administra usuarios del sistema</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="fas fa-user-plus me-2"></i>
                    Nuevo Usuario
                </button>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Lista de Usuarios
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Registrado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $userData): ?>
                                <tr class="<?= !$userData['active'] ? 'table-secondary' : '' ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle fa-2x text-muted me-3"></i>
                                            <div>
                                                <strong><?= htmlspecialchars($userData['username']) ?></strong>
                                                <?php if ($userData['id'] === $user['id']): ?>
                                                    <span class="badge bg-info ms-2">Tú</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($userData['email']) ?></td>
                                    <td>
                                        <span class="badge role-badge bg-<?= 
                                            $userData['role_id'] == 1 ? 'danger' : 
                                            ($userData['role_id'] == 2 ? 'warning' : 'info') 
                                        ?>">
                                            <?= htmlspecialchars($userData['role_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($userData['active']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted">
                                        <?= date('d/m/Y', strtotime($userData['created_at'])) ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-action" 
                                                onclick="editUser(<?= htmlspecialchars(json_encode($userData)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if ($userData['id'] !== $user['id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-action" 
                                                    onclick="deleteUser(<?= $userData['id'] ?>, '<?= htmlspecialchars($userData['username']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay usuarios</h4>
                        <p class="text-muted">Comienza agregando un usuario</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-user-plus me-2"></i>
                            Agregar Usuario
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">
                        <i class="fas fa-user-plus me-2"></i>
                        Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="userForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="user_id" id="userId" value="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Contraseña <span id="passwordRequired">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text" id="passwordHelp">
                                Mínimo 6 caracteres. Dejar en blanco para mantener la actual al editar.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Rol *</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Seleccionar rol...</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>">
                                        <?= htmlspecialchars($role['name']) ?>
                                        <?php if ($role['description']): ?>
                                            - <?= htmlspecialchars($role['description']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                            <label class="form-check-label" for="active">
                                Usuario activo
                            </label>
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
                    <p>¿Estás seguro de que quieres eliminar al usuario <strong id="deleteUserName"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="deleteUserId">
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
        // Editar usuario
        function editUser(userData) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = userData.id;
            document.getElementById('username').value = userData.username;
            document.getElementById('email').value = userData.email;
            document.getElementById('role_id').value = userData.role_id;
            document.getElementById('active').checked = userData.active == 1;
            
            // Cambiar textos del modal
            document.getElementById('userModalTitle').innerHTML = 
                '<i class="fas fa-edit me-2"></i>Editar Usuario';
            document.getElementById('submitBtn').innerHTML = 
                '<i class="fas fa-save me-2"></i>Actualizar';
            
            // Hacer opcional la contraseña al editar
            document.getElementById('password').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHelp').style.display = 'block';
            
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }
        
        // Eliminar usuario
        function deleteUser(id, username) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = username;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        // Reset modal al cerrar
        document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('userForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('userId').value = '';
            document.getElementById('userModalTitle').innerHTML = 
                '<i class="fas fa-user-plus me-2"></i>Nuevo Usuario';
            document.getElementById('submitBtn').innerHTML = 
                '<i class="fas fa-save me-2"></i>Guardar';
            
            // Restaurar campos para crear
            document.getElementById('password').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('passwordHelp').style.display = 'block';
            document.getElementById('active').checked = true;
        });
        
        <?php if ($editUser): ?>
        // Auto-abrir modal para editar si viene de URL
        document.addEventListener('DOMContentLoaded', function() {
            editUser(<?= json_encode($editUser) ?>);
        });
        <?php endif; ?>
    </script>
    <button id="modoBtn" class="modo-toggle" aria-label="Cambiar modo"></button>
    <script defer src="../assets/js/funciones.js"></script>
</body>
</html>
