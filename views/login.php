<?php
// views/login.php
session_start();
// Si ya hay sesión, redirigir (para que no se logueen doble)
if (isset($_SESSION['id_usuario'])) {
    // Redirección básica por defecto (luego la lógica la maneja el sistema)
    header("Location: ../index.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Coaching Lift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/lojo.png">
</head>
<body>
    <div class="container">
        
        <div class="logo-container">
            <img src="../assets/img/logo.png" alt="Logo Coaching Lift">
        </div>

        <h2>Bienvenido</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                <?php 
                    if ($_GET['error'] == 'credenciales') echo "Usuario o contraseña incorrectos.";
                    if ($_GET['error'] == 'inactivo') echo "Tu cuenta está desactivada. Contacta al soporte.";
                    if ($_GET['error'] == 'nologin') echo "Debes iniciar sesión para acceder.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../controllers/auth_login.php" method="POST">
            
            <div class="form-group">
                <label>Correo Electrónico:</label>
                <input type="email" name="correo" required autofocus>
            </div>

            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">Ingresar</button>
        </form>

        <div class="link-text">
            ¿Aún no tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </div>
    </div>
</body>
</html>