<?php
/*
# Nombre: auth.php
# Ubicación: includes/auth.php
# Descripción: Funciones de autenticación y manejo de sesiones
*/
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
 * Registrar un nuevo usuario
 * @param string $username Nombre de usuario
 * @param string $password Contrase\xC3\xB1a en texto plano
 * @param int $role_id Rol asignado
 * @return mixed True en \xC3\xA9xito o mensaje de error
 */
function register($username, $password, $role_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return 'El usuario ya existe';
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $db->prepare('INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)');
        $stmt->execute([$username, $hash, $role_id]);
        return true;
    } catch (PDOException $e) {
        return 'Error al registrar: ' . $e->getMessage();
    }
}

/**
 * Iniciar sesi\xC3\xB3n de usuario
 * @param string $username Nombre de usuario
 * @param string $password Contrase\xC3\xB1a
 * @return bool \xC3\x89xito o fallo
 */
function login($username, $password) {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, password, role_id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user["password"])) {
            startSession();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log('Error en login: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verificar sesión y roles permitidos
 * @param array $roles Roles autorizados
 */
function requireRole(array $roles) {
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
    if (!empty($roles) && !in_array($_SESSION['role_id'], $roles)) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
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
