<?php
// controllers/asesor_compartir_sesion.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$codigo_sesion = $input['codigo'];
$id_colaborador = $input['id_colaborador'];

try {
    // 1. Obtener ID Sesión
    $stmtSesion = $pdo->prepare("SELECT id_sesion FROM sesiones WHERE codigo_acceso = ?");
    $stmtSesion->execute([$codigo_sesion]);
    $id_sesion = $stmtSesion->fetchColumn();

    if (!$id_sesion) throw new Exception("Código no encontrado.");

    // 2. Verificar Asesor (CORRECCIÓN CRÍTICA: 'email' en vez de 'usuario')
    $stmtUser = $pdo->prepare("SELECT email FROM usuarios WHERE id_usuario = ? AND id_rol = 2");
    $stmtUser->execute([$id_colaborador]);
    $emailAsesor = $stmtUser->fetchColumn();

    if (!$emailAsesor) throw new Exception("Asesor no encontrado.");

    // 3. Verificar si ya tiene permiso
    $stmtCheck = $pdo->prepare("SELECT id_relacion FROM sesion_asesores WHERE id_sesion = ? AND id_asesor = ?");
    $stmtCheck->execute([$id_sesion, $id_colaborador]);
    
    if ($stmtCheck->rowCount() > 0) {
        throw new Exception("El asesor " . $emailAsesor . " ya tiene acceso.");
    }

    // 4. Insertar Permiso
    $stmtInsert = $pdo->prepare("INSERT INTO sesion_asesores (id_sesion, id_asesor) VALUES (?, ?)");
    $stmtInsert->execute([$id_sesion, $id_colaborador]);

    echo json_encode([
        'status' => 'success', 
        'mensaje' => '¡Listo! Se asignó a: ' . $emailAsesor
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>