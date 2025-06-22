<?php
/**
 * Script de migración: agrega tabla categories y columna category_id en products
 */
require_once __DIR__ . '/db.php';
try {
    $pdo = getDB();
    // Crear tabla categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB CHARSET=utf8mb4;");
    echo "✅ Tabla categories creada o ya existente.\n";
    // Agregar columna category_id si no existe
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT NULL AFTER user_id");
        echo "✅ Columna category_id agregada.\n";
    } catch (PDOException $e) {
        // 1060 = Duplicate column name
        if ($e->getCode() == 42S21 || $e->errorInfo[1] == 1060) {
            echo "✅ Columna category_id ya existe.\n";
        } else {
            throw $e;
        }
    }
    echo "🚀 Migración completada.\n";
} catch (PDOException $e) {
    echo "❌ Error de migración: " . $e->getMessage();
}
