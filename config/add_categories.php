<?php
/*
# Nombre: add_categories.php
# Ubicación: config/add_categories.php
# Descripción: Script para poblar categorías iniciales en la base de datos
*/

require_once __DIR__ . '/db.php';

$db = getDB();

$categories = [
    ['Genética de Cultivo', 'genetica-cultivo'],
    ['Kits de Cultivo', 'kits-cultivo'],
    ['Sustratos & Insumos', 'sustratos-insumos'],
];

$stmt = $db->prepare('INSERT INTO categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)');
foreach ($categories as $cat) {
    $stmt->execute($cat);
}

$assign = $db->prepare('UPDATE products SET category_id = (SELECT id FROM categories WHERE slug = ?) WHERE name = ?');
$assign->execute(['genetica-cultivo', 'Jeringa de esporas']);
$assign->execute(['genetica-cultivo', 'Micelio en grano']);
$assign->execute(['genetica-cultivo', 'Semillas de María']);

echo "Categorías insertadas y productos asignados.";
