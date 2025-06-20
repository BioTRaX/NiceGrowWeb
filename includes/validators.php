<?php
/*
# Nombre: validators.php
# Ubicación: includes/validators.php
# Descripción: Funciones de validación para formularios y archivos de imagen
*/

function validateImage($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Campo opcional
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir la imagen.');
    }

    $maxSize = 2 * 1024 * 1024; // 2 MB
    if ($file['size'] > $maxSize) {
        throw new Exception('La imagen supera el tamaño máximo de 2MB.');
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExt)) {
        throw new Exception('Formato de imagen no permitido.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!in_array($mime, $allowedMime)) {
        throw new Exception('Tipo MIME de imagen no válido.');
    }

    $newName = uniqid() . '_' . basename($file['name']);
    $dir = __DIR__ . '/../assets/img/products/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dir . $newName)) {
        throw new Exception('No se pudo guardar la imagen.');
    }

    return $newName;
}
