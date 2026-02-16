<?php
// controllers/asesor_guardar_invitacion.php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

// 1. Validar sesión
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']);
    exit;
}

// 2. Recibir datos
$input = json_decode(file_get_contents('php://input'), true);
$codigo = $input['codigo'] ?? '';
$id_invitado = $input['id_invitado'] ?? '';

if (empty($codigo) || empty($id_invitado)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos.']);
    exit;
}

try {
    // 3. Obtener el ID de la sesión basado en el código
    $stmt = $pdo->prepare("SELECT id_sesion FROM sesiones WHERE codigo_acceso = ?");
    $stmt->execute([$codigo]);
    $sesion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sesion) {
        throw new Exception("El código de sesión no existe.");
    }

    $id_sesion = $sesion['id_sesion'];

    // 4. Evitar duplicados (CORRECCIÓN AQUÍ)
    // Cambiamos 'SELECT id_sesion_asesor' por 'SELECT *' para evitar el error de columna desconocida
    $check = $pdo->prepare("SELECT * FROM sesion_asesores WHERE id_sesion = ? AND id_asesor = ?");
    $check->execute([$id_sesion, $id_invitado]);
    
    if ($check->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'mensaje' => 'Este asesor ya colaboraba en este código.']);
        exit;
    }

    // 5. INSERTAR LA VINCULACIÓN
    $sqlInsert = "INSERT INTO sesion_asesores (id_sesion, id_asesor) VALUES (?, ?)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([$id_sesion, $id_invitado]);

    echo json_encode(['status' => 'success', 'mensaje' => 'Colaboración guardada correctamente.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error BD: ' . $e->getMessage()]);
}
?>