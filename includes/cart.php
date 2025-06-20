<?php
/*
# Nombre: cart.php
# Ubicación: includes/cart.php
# Descripción: Funciones para gestionar el carrito de compras almacenado en sesión
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Verificar si el producto existe en la base de datos
 */
function cartProductExists(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Agregar un producto al carrito
 */
function addToCart(PDO $pdo, int $id, int $qty = 1): void {
    if ($qty < 1 || !cartProductExists($pdo, $id)) {
        return;
    }
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
}

/**
 * Eliminar un producto del carrito
 */
function removeFromCart(int $id): void {
    unset($_SESSION['cart'][$id]);
}

/**
 * Establecer cantidad exacta de un producto
 */
function setQty(PDO $pdo, int $id, int $qty): void {
    if ($qty < 1) {
        removeFromCart($id);
        return;
    }
    if (!cartProductExists($pdo, $id)) {
        return;
    }
    $_SESSION['cart'][$id] = $qty;
}

/**
 * Obtener items del carrito
 */
function cartItems(): array {
    return $_SESSION['cart'];
}

/**
 * Calcular el total del carrito (precio * cantidad)
 */
function cartTotal(PDO $pdo): float {
    $total = 0.0;
    $stmt = $pdo->prepare('SELECT price FROM products WHERE id = ?');
    foreach (cartItems() as $id => $qty) {
        $stmt->execute([$id]);
        $price = $stmt->fetchColumn();
        if ($price !== false) {
            $total += $price * $qty;
        }
    }
    return $total;
}

/**
 * Contar la cantidad total de productos en el carrito
 */
function cartCount(): int {
    return array_sum(cartItems());
}
