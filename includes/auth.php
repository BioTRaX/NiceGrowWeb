<?php
/**
 * Funciones de autenticación y autorización
 * NiceGrowWeb - Sistema de gestión
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Iniciar sesión de forma segura (solo si no está ya iniciada)
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Función de login
 * @param string $username Usuario
 * @param string $password Contraseña
 * @return bool True si login exitoso, false si falla
 */
function login($username, $password) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.password, u.role_id, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ? AND u.active = 1
        ");
        
        $stmt->execute([$username]);
        $user = $stmt->fetch();
          if ($user && password_verify($password, $user['password'])) {
            // Iniciar sesión
            startSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['login_time'] = time();
            
            // Log del login (opcional)
            logAccess($user['id'], 'login', 'Successful login');
            
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        return false;
    }
}

/**
 * Middleware de autorización por roles
 * @param array $allowedRoles Array de role_ids permitidos
 * @param string $redirectUrl URL de redirección si no autorizado
 */
function requireRole($allowedRoles = [], $redirectUrl = '/admin/login.php') {
    startSession();
    
    // Verificar si hay sesión activa
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        header("Location: " . $redirectUrl);
        exit;
    }
    
    // Verificar timeout de sesión (24 horas)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
        logout();
        header("Location: " . $redirectUrl . "?timeout=1");
        exit;
    }
    
    // Si no se especifican roles, solo verificar que esté logueado
    if (empty($allowedRoles)) {
        return true;
    }
    
    // Verificar rol
    if (!in_array($_SESSION['role_id'], $allowedRoles)) {
        http_response_code(403);
        die("
        <div style='text-align:center; padding:50px; font-family:Arial,sans-serif;'>
            <h1>🚫 Acceso Denegado</h1>
            <p>No tienes permisos para acceder a esta sección.</p>
            <p>Tu rol: <strong>" . htmlspecialchars($_SESSION['role_name']) . "</strong></p>
            <a href='/admin/dashboard.php'>← Volver al Dashboard</a>
        </div>
        ");
    }
    
    return true;
}

/**
 * Cerrar sesión
 */
function logout() {
    startSession();
    
    // Log del logout si hay usuario
    if (isset($_SESSION['user_id'])) {
        logAccess($_SESSION['user_id'], 'logout', 'User logged out');
    }
    
    // Destruir sesión
    session_destroy();
    session_unset();
}

/**
 * Verificar si el usuario está logueado
 * @return bool
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Obtener información del usuario actual
 * @return array|null
 */
function getCurrentUser() {
    startSession();
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role_id' => $_SESSION['role_id'],
        'role_name' => $_SESSION['role_name']
    ];
}

/**
 * Verificar si el usuario tiene un rol específico
 * @param int $roleId
 * @return bool
 */
function hasRole($roleId) {
    startSession();
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == $roleId;
}

/**
 * Verificar si es admin
 * @return bool
 */
function isAdmin() {
    return hasRole(1);
}

/**
 * Log de accesos (opcional - para auditoría)
 * @param int $userId
 * @param string $action
 * @param string $description
 */
function logAccess($userId, $action, $description = '') {
    try {
        $db = getDB();
        
        // Crear tabla de logs si no existe
        $db->exec("
            CREATE TABLE IF NOT EXISTS access_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(50),
                description TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        $stmt = $db->prepare("
            INSERT INTO access_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (PDOException $e) {
        error_log("Error en log de acceso: " . $e->getMessage());
    }
}

/**
 * Generar token CSRF
 * @return string
 */
function generateCSRFToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
