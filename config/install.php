<?php
/**
 * Script de instalación de base de datos
 * Ejecutar una sola vez para crear las tablas
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalador - NiceGrowWeb</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #6A1B9A; margin: 0; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-primary { background: #6A1B9A; color: white; }
        .btn-success { background: #388E3C; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🌱 NiceGrowWeb</h1>
            <p>Instalador de Base de Datos</p>
        </div>";

// Conectar sin especificar base de datos para poder crearla
try {
    $pdo = new PDO(
        "mysql:host=localhost;charset=utf8mb4",
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Crear la base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS nicegrow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='status success'>✅ Base de datos 'nicegrow_db' creada/verificada</div>";
    
    // Seleccionar la base de datos
    $pdo->exec("USE nicegrow_db");
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Error al crear la base de datos: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}

$sql = "

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    img VARCHAR(255) DEFAULT NULL,
    stock INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insertar roles por defecto
INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'admin', 'Administrador completo - CRUD usuarios y productos'),
(2, 'seller', 'Vendedor - CRUD productos propios'),
(3, 'viewer', 'Solo lectura - Visualización de productos');

-- Insertar usuario admin por defecto (usuario: BioTRaX, contraseña: Bio4256)
INSERT IGNORE INTO users (username, email, password, role_id) VALUES
('BioTRaX', 'admin@nicegrowweb.com', ?, 1);

-- Migrar productos existentes desde el código PHP
INSERT IGNORE INTO products (id, name, price, description) VALUES
(1, 'Micelio de Psilocybe', 3500.00, 'Micelio de alta calidad para cultivo de hongos'),
(2, 'Kit de Cultivo', 5800.00, 'Kit completo para iniciar tu cultivo'),
(3, 'Sustrato estéril', 2200.00, 'Sustrato preparado y esterilizado');
";

try {
    // Generar hash para la contraseña del admin
    $adminPassword = 'Bio4256';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Ejecutar las consultas
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            // Si es la consulta del usuario admin, usar prepared statement para el hash
            if (strpos($statement, 'INSERT IGNORE INTO users') !== false) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, role_id) VALUES ('BioTRaX', 'admin@nicegrowweb.com', ?, 1)");
                $stmt->execute([$hashedPassword]);
            } else {
                $pdo->exec($statement);
            }
        }
    }
    
    echo "<div class='status success'>✅ Tablas creadas correctamente</div>";
    echo "<div class='status success'>✅ Roles insertados: admin, seller, viewer</div>";
    echo "<div class='status success'>✅ Productos de ejemplo agregados</div>";
    echo "<div class='status success'>👤 Usuario admin creado: <strong>BioTRaX</strong> / <strong>Bio4256</strong></div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<h3>🎉 ¡Instalación Completada!</h3>";
    echo "<p>La base de datos ha sido configurada correctamente. Ahora puedes:</p>";
    echo "<a href='../admin/login.php' class='btn btn-primary'>🔐 Ir al Panel Admin</a>";
    echo "<a href='../index.php' class='btn btn-success'>🏠 Ver la Tienda</a>";
    echo "</div>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h4 style='color: #1976d2; margin-top: 0;'>📋 Credenciales de Administrador:</h4>";
    echo "<ul style='color: #1976d2;'>";
    echo "<li><strong>Usuario:</strong> BioTRaX</li>";
    echo "<li><strong>Contraseña:</strong> Bio4256</li>";
    echo "<li><strong>Rol:</strong> Administrador completo</li>";
    echo "</ul>";
    echo "<p style='color: #666; font-size: 0.9rem;'><em>Puedes cambiar estas credenciales desde el panel de administración.</em></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Error al instalar las tablas: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
