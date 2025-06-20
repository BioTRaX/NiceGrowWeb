<?php
/*
# Nombre: db.php
# Ubicación: config/db.php
# Descripción: Configuración de base de datos mediante variables de entorno
*/

// Configuración de base de datos
// Las constantes obtienen su valor desde variables de entorno
// y utilizan un valor predeterminado si no están definidas
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'nicegrow_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

class Database {
    private static $instance = null;
    private $connection;
      private function __construct() {
        try {
            // Intentar conectar a la base de datos específica
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Si la base de datos no existe, mostrar mensaje informativo
            if ($e->getCode() == 1049) {
                die("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
                    <h2 style='color: #d32f2f;'>⚠️ Base de datos no encontrada</h2>
                    <p>La base de datos '<strong>" . DB_NAME . "</strong>' no existe.</p>
                    <p>Para instalar la base de datos y crear las tablas necesarias:</p>
                    <ol>
                        <li>Asegúrate de que MySQL esté ejecutándose en XAMPP</li>
                        <li>Haz clic en el siguiente enlace para ejecutar el instalador:</li>
                    </ol>
                    <p style='text-align: center; margin: 20px 0;'>
                        <a href='/NiceGrowWeb/config/install.php' 
                           style='background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            🔧 Instalar Base de Datos
                        </a>
                    </p>
                    <hr>
                    <small style='color: #666;'>
                        <strong>Nota:</strong> Esto solo necesita ejecutarse una vez. 
                        El instalador creará la base de datos, las tablas y un usuario administrador por defecto.
                    </small>
                </div>
                ");
            }
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Función helper para obtener la conexión PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
