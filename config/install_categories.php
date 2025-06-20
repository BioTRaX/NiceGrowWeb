<?php
/*
# Nombre: install_categories.php
# Ubicación: config/install_categories.php
# Descripción: Crea la tabla de categorías y valores iniciales durante la instalación
*/

function installCategories(PDO $pdo) {
    // Crear tabla de categorías
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Añadir columna category_id a products si no existe
    $pdo->exec("ALTER TABLE products
        ADD COLUMN IF NOT EXISTS category_id INT NULL AFTER user_id,
        ADD CONSTRAINT IF NOT EXISTS fk_products_categories
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");

    // Categorías por defecto
    $stmt = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $cats = [
        ['Genética de Cultivo', 'genetica-cultivo'],
        ['Kits de Cultivo', 'kits-cultivo'],
        ['Sustratos & Insumos', 'sustratos-insumos']
    ];
    foreach ($cats as $c) {
        $stmt->execute($c);
    }
}
