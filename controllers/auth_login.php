<?php
// controllers/auth_login.php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['correo'];
    $password = $_POST['password'];

    // 1. Buscar usuario por correo
    $stmt = $pdo->prepare("SELECT id_usuario, password_hash, id_rol, estado FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 2. Verificar si existe y si la contraseña coincide
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // 3. Verificar estado (Si está inactivo, no entra)
        if ($user['estado'] !== 'Activo') {
            header("Location: ../views/login.php?error=inactivo");
            exit;
        }

        // 4. Crear variables de SESIÓN (La "tarjeta de acceso" del usuario)
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['id_rol'] = $user['id_rol'];
        $_SESSION['email'] = $email;

        // 5. Redireccionar según el ROL
        switch ($user['id_rol']) {
            case 1: // Admin
                header("Location: ../systems/admin/home.php");
                break;
            case 2: // Asesor
                header("Location: ../systems/asesor/panel.php");
                break;
            case 3: // Participante
                header("Location: ../systems/participante/home.php");
                break;
            default:
                // Rol desconocido
                header("Location: ../views/login.php?error=rol_desconocido");
                break;
        }
        exit;

    } else {
        // Contraseña incorrecta o usuario no encontrado
        header("Location: ../views/login.php?error=credenciales");
        exit;
    }
} else {
    // Si intentan entrar directo a este archivo sin enviar formulario
    header("Location: ../views/login.php");
    exit;
}
?>