<?php
// controllers/asesor_add_empresa.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// Seguridad: Solo Asesores
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$nombre_empresa = trim($input['nombre_empresa'] ?? '');

if (empty($nombre_empresa)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'El nombre de la empresa no puede estar vacío.']);
    exit;
}

try {
    // Verificar si ya existe
    $stmtCheck = $pdo->prepare("SELECT id_empresa FROM empresas WHERE nombre_empresa = ?");
    $stmtCheck->execute([$nombre_empresa]);
    if ($stmtCheck->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Esta empresa ya está registrada.']);
        exit;
    }

    // Insertar
    $stmt = $pdo->prepare("INSERT INTO empresas (nombre_empresa) VALUES (?)");
    $stmt->execute([$nombre_empresa]);
    
    echo json_encode(['status' => 'success', 'mensaje' => 'Empresa agregada correctamente.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error de BD: ' . $e->getMessage()]);
}
?>