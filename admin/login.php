<?php
/*
# Nombre: login.php
# Ubicación: admin/login.php
# Descripción: Formulario y proceso de inicio de sesión para administradores
*/
require_once '../includes/auth.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Manejar timeout de sesión
if (isset($_GET['timeout'])) {
    $error = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
}

// Manejar logout
if (isset($_GET['logout'])) {
    logout();
    $success = 'Has cerrado sesión correctamente.';
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        if (login($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel Administrativo | Nice Grow</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand-logo h1 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .brand-logo p {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .form-floating input {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-floating input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-to-site a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="brand-logo">
                    <h1><i class="fas fa-seedling text-success"></i> Nice Grow</h1>
                    <p>Panel de Administración</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Usuario" required autocomplete="username">
                        <label for="username">
                            <i class="fas fa-user me-2"></i>Usuario
                        </label>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Contraseña" required autocomplete="current-password">
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Contraseña
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sesión
                    </button>
                </form>
                
                <div class="back-to-site">
                    <a href="../index.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver a la tienda
                    </a>
                </div>
                
                <hr class="my-4">
                  <div class="text-center">
                    <small class="text-muted">
                        <strong>Credenciales de administrador:</strong><br>
                        Usuario: <code>BioTRaX</code><br>
                        Contraseña: <code>Bio4256</code>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
