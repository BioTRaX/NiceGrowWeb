# NiceGrowWeb 🌱

Sistema de gestión y tienda online para productos de cultivo con panel administrativo completo.

## 🚀 Características

### Frontend (Tienda)
- ✅ Catálogo de productos dinámico desde base de datos
- ✅ Carrito de compras con sesiones
- ✅ Integración con Mercado Pago
- ✅ Diseño responsive y moderno
- ✅ Imágenes de productos

### Backend (Panel Admin)
- ✅ Sistema de autenticación con roles
- ✅ Dashboard con estadísticas
- ✅ CRUD completo de productos
- ✅ Gestión de usuarios (solo admins)
- ✅ Subida y manejo de imágenes
- ✅ Sistema de permisos granular

## 🔧 Instalación

### 1. Requisitos
- XAMPP (Apache + MySQL + PHP 7.4+)
- Extensiones PHP: PDO, GD, fileinfo

### 2. Configuración
1. Clona o descarga el proyecto en `c:\xampp\htdocs\NiceGrowWeb`
2. Inicia Apache y MySQL desde XAMPP
3. Ejecuta la instalación de BD abriendo `http://localhost/NiceGrowWeb/config/install.php`. El instalador solicitará una contraseña para el usuario administrador. También puedes definirla previamente con la variable de entorno `ADMIN_PASSWORD`.
4. Accede a la tienda: `http://localhost/NiceGrowWeb/`

### 3. Panel Administrativo
- URL: `http://localhost/NiceGrowWeb/admin/login.php`
- Usuario por defecto: `BioTRaX`
- Contraseña: la que definiste durante la instalación

## 📁 Estructura del Proyecto

```
/NiceGrowWeb
├── index.php                 # Tienda principal
├── pagar.php                 # Proceso de pago
├── /admin/                   # Panel administrativo
│   ├── login.php            # Login de administradores
│   ├── dashboard.php        # Dashboard principal
│   ├── products.php         # Gestión de productos
│   └── users.php            # Gestión de usuarios
├── /config/
│   ├── db.php               # Configuración de BD
│   └── install.php          # Instalador de BD
├── /includes/
│   ├── auth.php             # Funciones de autenticación
│   └── upload.php           # Manejo de imágenes
├── /assets/
│   ├── /css/estilos.css     # Estilos principales
│   ├── /js/funciones.js     # JavaScript
│   └── /img/products/       # Imágenes de productos
└── /productos/
    └── productos.json       # Productos legacy (no usado)
```

## 👥 Sistema de Roles

| Rol | ID | Permisos |
|-----|----|---------| 
| **Admin** | 1 | CRUD usuarios + productos |
| **Seller** | 2 | CRUD productos propios |
| **Viewer** | 3 | Solo lectura |

## 🔒 Características de Seguridad

- ✅ Contraseñas hasheadas con bcrypt
- ✅ Prepared statements (previene SQL injection)
- ✅ Validación de archivos subidos
- ✅ Tokens CSRF en formularios
- ✅ Control de sesiones y timeouts
- ✅ Validación de tipos MIME
- ✅ Logs de acceso para auditoría

## 🖼️ Manejo de Imágenes

- Formatos soportados: JPG, PNG, WebP
- Tamaño máximo: 2MB
- Redimensionamiento automático a 800x600px
- Validación de seguridad contra archivos maliciosos
- Nombres únicos con timestamp

## 🛠️ Funcionalidades Técnicas

### Autenticación
```php
// Login
login($username, $password)

// Verificar permisos por rol
requireRole([1, 2]) // admin y seller

// Verificar si está logueado
isLoggedIn()
```

### Productos
```php
// Subida de imagen
handleUpload($_FILES['image'])

// Redimensionar imagen
resizeImage($filePath, $maxWidth, $maxHeight)

// Eliminar imagen
deleteImage($fileName)
```

## 📱 Responsive Design

- Diseño adaptativo para móviles
- Bootstrap 5 en panel admin
- CSS Grid para catálogo de productos
- Navegación optimizada para touch

## 🔄 Próximas Mejoras

- [ ] Sistema de categorías
- [ ] Filtros de búsqueda
- [ ] Inventario automático
- [ ] Reportes de ventas
- [ ] Notificaciones push
- [ ] API REST

## 🐛 Troubleshooting

### Error de conexión a BD
- Verificar que MySQL esté corriendo
- Revisar credenciales en `config/db.php`

### Imágenes no se suben
- Verificar permisos de escritura en `/assets/img/products/`
- Comprobar extensión GD de PHP

### Panel admin no accesible
- Ejecutar `install.php` para crear usuario admin
- Verificar que las tablas estén creadas

## 📄 Licencia

MIT License - Ver archivo LICENSE para más detalles.

---

**Desarrollado con ❤️ para la comunidad de cultivadores** 🌱

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
