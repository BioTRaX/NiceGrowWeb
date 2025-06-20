<?php
/**
 * Script para actualizar credenciales de administrador
 * Ejecutar si ya tienes la BD instalada y solo quieres cambiar las credenciales
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Actualizar Credenciales - NiceGrowWeb</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #6A1B9A; margin: 0; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-primary { background: #6A1B9A; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔧 NiceGrowWeb</h1>
            <p>Actualizar Credenciales de Administrador</p>
        </div>";

try {
    // Conectar a la base de datos
    $pdo = new PDO(
        "mysql:host=localhost;dbname=nicegrow_db;charset=utf8mb4",
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Nuevas credenciales
    $newUsername = 'BioTRaX';
    $newPassword = 'Bio4256';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Verificar si ya existe el usuario BioTRaX
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$newUsername]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Actualizar contraseña del usuario existente
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $newUsername]);
        echo "<div class='status success'>✅ Contraseña actualizada para el usuario '{$newUsername}'</div>";
    } else {
        // Verificar si existe usuario 'admin' para actualizarlo
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminUser = $stmt->fetch();
        
        if ($adminUser) {
            // Actualizar usuario admin existente
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE username = 'admin'");
            $stmt->execute([$newUsername, $hashedPassword]);
            echo "<div class='status success'>✅ Usuario 'admin' actualizado a '{$newUsername}' con nueva contraseña</div>";
        } else {
            // Crear nuevo usuario administrador
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id, active) VALUES (?, ?, ?, 1, 1)");
            $stmt->execute([$newUsername, 'admin@nicegrowweb.com', $hashedPassword]);
            echo "<div class='status success'>✅ Nuevo usuario administrador '{$newUsername}' creado</div>";
        }
    }
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<h3>🎉 ¡Credenciales Actualizadas!</h3>";
    echo "<p>Las credenciales de administrador han sido configuradas correctamente:</p>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #1976d2; margin-top: 0;'>📋 Nuevas Credenciales:</h4>";
    echo "<ul style='color: #1976d2; text-align: left;'>";
    echo "<li><strong>Usuario:</strong> {$newUsername}</li>";
    echo "<li><strong>Contraseña:</strong> {$newPassword}</li>";
    echo "<li><strong>Rol:</strong> Administrador completo</li>";
    echo "</ul>";
    echo "</div>";
    echo "<a href='../admin/login.php' class='btn btn-primary'>🔐 Ir al Panel Admin</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    if ($e->getCode() == 1049) {
        echo "<div class='status error'>❌ La base de datos no existe. Primero ejecuta el <a href='install.php'>instalador completo</a>.</div>";
    } else {
        echo "<div class='status error'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

echo "</div></body></html>";
?>
