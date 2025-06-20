<?php
/*
# Nombre: contact.php
# Ubicación: contact.php
# Descripción: Formulario de contacto para consultas de los usuarios
*/

$enviado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // En un entorno real se enviaría un correo o se almacenaría en la base de datos
    $enviado = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contacto - Nice Grow</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="container">
    <h2>Contacto</h2>
    <?php if ($enviado): ?>
        <p>Gracias por contactarnos. Responderemos a la brevedad.</p>
    <?php else: ?>
    <form method="post" class="form-contacto">
        <label>Nombre
            <input type="text" name="nombre" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mensaje
            <textarea name="mensaje" rows="4" required></textarea>
        </label>
        <button type="submit">Enviar</button>
    </form>
    <?php endif; ?>
</main>
</body>
</html>
