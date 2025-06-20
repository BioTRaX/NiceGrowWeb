# NiceGrowWeb

Este proyecto contiene una tienda en línea escrita en PHP. Incluye un carrito de compras básico y una simulación de pago.

## Estructura del proyecto

- `index.php`: página principal de la tienda.
- `pagar.php`: procesa el pago (simulado).
- `assets/`: archivos estáticos.
  - `css/estilos.css`: hoja de estilos principal.
  - `js/funciones.js`: funciones de JavaScript.
- `productos/productos.json`: catálogo en formato JSON.
- `.gitignore`: exclusiones para Git.
- `LICENSE`: licencia del proyecto.

## Cómo usar

1. Clona el repositorio y abre `index.php` en tu servidor local de PHP.
2. Agrega productos al carrito y presiona «Pagar» para ver la simulación.

## Convenciones de código

Todos los archivos PHP, JavaScript y CSS deben comenzar con un bloque de comentarios que indique:

```
# Nombre: <nombre del archivo>
# Ubicación: <ruta relativa desde la raíz>
# Descripción: <breve descripción>
```

Si el bloque ya existe se actualizan solo los valores. Los archivos JSON o binarios no se modifican.

Contribuciones y mejoras son bienvenidas.
