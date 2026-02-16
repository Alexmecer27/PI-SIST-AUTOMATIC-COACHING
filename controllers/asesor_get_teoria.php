<?php
// controllers/asesor_get_teoria.php
ob_start();
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$cedula = $_GET['cedula'] ?? '';
$codigo = $_GET['codigo'] ?? '';

try {
    if (!$cedula || !$codigo) throw new Exception("Faltan datos.");

    // Buscar el intento más reciente del alumno en esta sesión
    $sql = "SELECT i.id_intento, i.nota, i.estado, i.fecha_inicio, p.nombre_completo 
            FROM intentos i
            JOIN sesiones s ON i.id_sesion = s.id_sesion
            JOIN perfil_participante p ON i.id_usuario = p.id_usuario
            WHERE p.cedula = ? AND s.codigo_acceso = ?
            ORDER BY i.fecha_inicio DESC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cedula, $codigo]);
    $intento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$intento) {
        throw new Exception("El estudiante aún no ha iniciado la prueba teórica.");
    }

    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $intento]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>