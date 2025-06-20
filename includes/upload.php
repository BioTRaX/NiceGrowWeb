<?php
/*
# Nombre: upload.php
# Ubicación: includes/upload.php
# Descripción: Utilidades para subir y validar archivos de imagen
*/
/**
 * Funciones para manejo de archivos e imágenes
 * NiceGrowWeb - Sistema de gestión
 */

/**
 * Manejar subida de imágenes de productos
 * @param array $file Archivo $_FILES
 * @param string $uploadDir Directorio de destino (opcional)
 * @return string|null Nombre del archivo guardado o null en caso de error
 */
function handleUpload($file, $uploadDir = '../assets/img/products/') {
    // Verificar que se haya subido un archivo
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Configuración
    $maxFileSize = 2 * 1024 * 1024; // 2MB
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    
    // Validar tamaño
    if ($file['size'] > $maxFileSize) {
        throw new Exception('El archivo es demasiado grande. Máximo 2MB.');
    }
    
    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo JPG, PNG y WebP.');
    }
    
    // Validar extensión
    $pathInfo = pathinfo($file['name']);
    $extension = strtolower($pathInfo['extension']);
    
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Extensión de archivo no permitida.');
    }
    
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de destino.');
        }
    }
    
    // Generar nombre único
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName);
    $cleanName = substr($cleanName, 0, 50); // Limitar longitud
    $timestamp = time();
    $newFileName = $timestamp . '_' . $cleanName . '.' . $extension;
    
    // Ruta completa
    $filePath = $uploadDir . $newFileName;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al guardar el archivo.');
    }
    
    // Redimensionar imagen si es necesario
    resizeImage($filePath, 800, 600);
    
    return $newFileName;
}

/**
 * Redimensionar imagen manteniendo proporción
 * @param string $filePath Ruta del archivo
 * @param int $maxWidth Ancho máximo
 * @param int $maxHeight Alto máximo
 * @return bool
 */
function resizeImage($filePath, $maxWidth = 800, $maxHeight = 600) {
    // Obtener información de la imagen
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return false;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // Si ya es del tamaño correcto, no hacer nada
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true;
    }
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = intval($width * $ratio);
    $newHeight = intval($height * $ratio);
    
    // Crear imagen desde archivo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Crear nueva imagen
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preservar transparencia para PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Redimensionar
    imagecopyresampled(
        $destination, $source,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );
    
    // Guardar imagen redimensionada
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($destination, $filePath, 85);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($destination, $filePath, 8);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($destination, $filePath, 85);
            break;
    }
    
    // Limpiar memoria
    imagedestroy($source);
    imagedestroy($destination);
    
    return $success;
}

/**
 * Eliminar archivo de imagen
 * @param string $fileName Nombre del archivo
 * @param string $uploadDir Directorio (opcional)
 * @return bool
 */
function deleteImage($fileName, $uploadDir = '../assets/img/products/') {
    if (empty($fileName)) {
        return true;
    }
    
    $filePath = $uploadDir . $fileName;
    
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    
    return true;
}

/**
 * Validar que un archivo de imagen sea seguro
 * @param string $filePath Ruta del archivo
 * @return bool
 */
function validateImageSecurity($filePath) {
    // Verificar que sea realmente una imagen
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return false;
    }
    
    // Verificar contenido del archivo para detectar código malicioso
    $content = file_get_contents($filePath, false, null, 0, 1024);
    
    // Buscar patrones sospechosos
    $suspiciousPatterns = [
        '/<\?php/i',
        '/<script/i',
        '/javascript:/i',
        '/eval\(/i',
        '/base64_decode/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Obtener URL completa de una imagen
 * @param string $fileName Nombre del archivo
 * @return string
 */
function getImageUrl($fileName) {
    if (empty($fileName)) {
        return '/assets/img/placeholder.jpg'; // Imagen por defecto
    }
    
    return '/assets/img/products/' . $fileName;
}

/**
 * Generar miniaturas de imagen
 * @param string $filePath Ruta del archivo original
 * @param int $thumbWidth Ancho de miniatura
 * @param int $thumbHeight Alto de miniatura
 * @return string|null Nombre del archivo miniatura
 */
function generateThumbnail($filePath, $thumbWidth = 150, $thumbHeight = 150) {
    if (!file_exists($filePath)) {
        return null;
    }
    
    $pathInfo = pathinfo($filePath);
    $thumbName = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    $thumbPath = $pathInfo['dirname'] . '/' . $thumbName;
    
    // Obtener información de la imagen
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return null;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // Crear imagen desde archivo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filePath);
            break;
        default:
            return null;
    }
    
    if (!$source) {
        return null;
    }
    
    // Crear miniatura cuadrada
    $minDimension = min($width, $height);
    $cropX = ($width - $minDimension) / 2;
    $cropY = ($height - $minDimension) / 2;
    
    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
    
    // Preservar transparencia para PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
    }
    
    // Redimensionar y recortar
    imagecopyresampled(
        $thumbnail, $source,
        0, 0, $cropX, $cropY,
        $thumbWidth, $thumbHeight,
        $minDimension, $minDimension
    );
    
    // Guardar miniatura
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($thumbnail, $thumbPath, 85);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($thumbnail, $thumbPath, 8);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($thumbnail, $thumbPath, 85);
            break;
    }
    
    // Limpiar memoria
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $success ? $thumbName : null;
}
