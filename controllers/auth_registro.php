<?php
// controllers/auth_registro.php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recibir datos
    $rol_seleccionado = $_POST['rol_seleccionado']; 
    $nombre = $_POST['nombre_completo'];
    $cedula = $_POST['cedula'];
    $email = $_POST['correo'];
    $password = $_POST['password'];

    try {
        // ==============================================================
        // VALIDACIONES (Correo y Cédula)
        // ==============================================================
        
        // A) Validar Correo
        $stmtEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmtEmail->execute([$email]);
        if ($stmtEmail->rowCount() > 0) {
            // REDIRECCIÓN EN LUGAR DE DIE
            header("Location: ../views/registro.php?error=email_repetido");
            exit;
        }

        // B) Validar Cédula (En ambas tablas)
        $stmtCedula = $pdo->prepare("
            SELECT cedula FROM perfil_asesor WHERE cedula = ?
            UNION
            SELECT cedula FROM perfil_participante WHERE cedula = ?
        ");
        $stmtCedula->execute([$cedula, $cedula]);
        
        if ($stmtCedula->rowCount() > 0) {
            // REDIRECCIÓN EN LUGAR DE DIE
            header("Location: ../views/registro.php?error=cedula_repetida");
            exit;
        }

        // ==============================================================
        // INICIO DEL REGISTRO
        // ==============================================================
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        $pdo->beginTransaction();

        $id_rol_asignado = 0; 

        // --- ASESOR ---
        if ($rol_seleccionado === 'Asesor') {
            $id_rol_asignado = 2; 

            // Validar código
            if ($_POST['codigo_registro'] !== "coaching_lift2026") {
                header("Location: ../views/registro.php?error=codigo_invalido");
                exit;
            }

            // Insertar Usuario
            $sqlUser = "INSERT INTO usuarios (email, password_hash, id_rol, estado) VALUES (?, ?, ?, 'Activo')";
            $pdo->prepare($sqlUser)->execute([$email, $password_hash, $id_rol_asignado]);
            $id_usuario = $pdo->lastInsertId();

            // Insertar Perfil
            $sqlPerfil = "INSERT INTO perfil_asesor (id_usuario, nombre_completo, cedula, fecha_nacimiento, telefono) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sqlPerfil)->execute([$id_usuario, $nombre, $cedula, $_POST['fecha_nacimiento'], $_POST['telefono']]);

        // --- PARTICIPANTE ---
        } else {
            $id_rol_asignado = 3; 
            
            // Imagen
            $foto_ruta = "";
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('user_') . '.' . $ext;
                
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $uploadDir . $fileName)) {
                    $foto_ruta = $fileName;
                } else {
                    throw new Exception("Error al subir imagen");
                }
            } else {
                header("Location: ../views/registro.php?error=falta_foto");
                exit;
            }

            // Insertar Usuario
            $sqlUser = "INSERT INTO usuarios (email, password_hash, id_rol, estado) VALUES (?, ?, ?, 'Activo')";
            $pdo->prepare($sqlUser)->execute([$email, $password_hash, $id_rol_asignado]);
            $id_usuario = $pdo->lastInsertId();

            // Insertar Perfil
            $sqlPerfil = "INSERT INTO perfil_participante (id_usuario, nombre_completo, cedula, cargo, anios_experiencia, foto_perfil, id_empresa) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sqlPerfil)->execute([$id_usuario, $nombre, $cedula, $_POST['cargo'], $_POST['anios_experiencia'], $foto_ruta, $_POST['id_empresa']]);
        }

        $pdo->commit();

        // Login Automático
        $_SESSION['id_usuario'] = $id_usuario;
        $_SESSION['id_rol'] = $id_rol_asignado;
        $_SESSION['email'] = $email;

        if ($id_rol_asignado == 2) {
            header("Location: ../systems/asesor/panel.php");
        } else {
            header("Location: ../systems/participante/home.php");
        }
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        // Si falla algo técnico (BD caída, etc), mandamos error general
        header("Location: ../views/registro.php?error=error_tecnico");
        exit;
    }
}
?>