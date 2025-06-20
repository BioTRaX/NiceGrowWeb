<?php
/**
 * Migración: crear tabla categories y asignar categorías a productos
 */
require_once __DIR__ . '/db.php';
$db = getDB();

try {
    // Crear tabla categories
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Agregar columna category_id a products
    $db->exec("ALTER TABLE products 
        ADD COLUMN IF NOT EXISTS category_id INT NULL AFTER user_id,
        ADD CONSTRAINT IF NOT EXISTS fk_products_categories 
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");

    // Insertar categorías
    $insert = $db->prepare("
        INSERT IGNORE INTO categories (name, slug) VALUES
        (?, ?),
        (?, ?),
        (?, ?)
    ");
    $insert->execute([
        'Genética de Cultivo', 'genetica-cultivo',
        'Kits de Cultivo',       'kits-cultivo',
        'Sustratos & Insumos',   'sustratos-insumos'
    ]);

    echo "✅ Tabla 'categories' y columna 'category_id' agregadas correctamente.<br>";
    echo "✅ Categorías insertadas: Genética de Cultivo, Kits de Cultivo, Sustratos & Insumos.<br>";
    
    // Asignar productos existentes
    $assign = $db->prepare("
        UPDATE products p
        JOIN categories c ON c.slug = :slug
        SET p.category_id = c.id
        WHERE p.name = :name
    ");
    $mapping = [
        ['slug' => 'genetica-cultivo',   'name' => 'Micelio de Psilocybe'],
        ['slug' => 'kits-cultivo',        'name' => 'Kit de Cultivo'],
        ['slug' => 'sustratos-insumos',   'name' => 'Sustrato estéril'],
    ];
    foreach ($mapping as $m) {
        $assign->execute(['slug' => $m['slug'], 'name' => $m['name']]);
    }
    echo "✅ Productos existentes asignados a sus categorías.<br>";

} catch (PDOException $e) {
    echo "❌ Error en migración: " . $e->getMessage();
}
