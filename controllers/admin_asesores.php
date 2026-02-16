<?php
// controllers/admin_asesores.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// Seguridad: Solo Admin (Rol 1)
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit;
}

$accion = $_GET['accion'] ?? 'listar';

try {
    if ($accion === 'listar') {
        // Unimos usuarios con perfil_asesor
        $sql = "SELECT u.id_usuario, u.email, u.estado, 
                       p.nombre_completo, p.cedula, p.telefono
                FROM usuarios u 
                LEFT JOIN perfil_asesor p ON u.id_usuario = p.id_usuario
                WHERE u.id_rol = 2 -- Solo Asesores
                ORDER BY u.id_usuario DESC";
        
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif ($accion === 'crear') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = trim($input['email']); // En tu DB es 'email', no username
        $pass = password_hash(trim($input['password']), PASSWORD_DEFAULT);
        $nombre = trim($input['nombre']);
        $cedula = trim($input['cedula']);
        $telefono = trim($input['telefono']);

        // Validar si existe el email
        $stmtCheck = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        if($stmtCheck->rowCount() > 0) throw new Exception("El correo '$email' ya está registrado.");

        // Transacción: Guardar en ambas tablas
        $pdo->beginTransaction();

        try {
            // 1. Tabla usuarios
            $stmtUser = $pdo->prepare("INSERT INTO usuarios (email, password_hash, id_rol, estado) VALUES (?, ?, 2, 'Activo')");
            $stmtUser->execute([$email, $pass]);
            $id_nuevo = $pdo->lastInsertId();

            // 2. Tabla perfil_asesor
            $stmtPerfil = $pdo->prepare("INSERT INTO perfil_asesor (id_usuario, nombre_completo, cedula, telefono) VALUES (?, ?, ?, ?)");
            $stmtPerfil->execute([$id_nuevo, $nombre, $cedula, $telefono]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'mensaje' => 'Asesor registrado correctamente.']);
        } catch (Exception $ex) {
            $pdo->rollBack();
            throw $ex;
        }

    } elseif ($accion === 'toggle') {
        // Cambiar estado Activo <-> Inactivo
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id_usuario'];
        $estadoActual = $input['estado_actual']; // 'Activo' o 'Inactivo'

        $nuevoEstado = ($estadoActual === 'Activo') ? 'Inactivo' : 'Activo';

        $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
        $stmt->execute([$nuevoEstado, $id]);
        
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>