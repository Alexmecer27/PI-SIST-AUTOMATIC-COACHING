<?php
// controllers/quiz_previsualizar.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$id_intento = $input['id_intento'] ?? 0;
$respuestas_usuario = $input['respuestas'] ?? [];

try {
    $stmtCheck = $pdo->prepare("SELECT * FROM intentos WHERE id_intento = ? AND estado = 'En Progreso'");
    $stmtCheck->execute([$id_intento]);
    $intento = $stmtCheck->fetch();

    if (!$intento) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Intento no válido o tiempo agotado.']);
        exit;
    }

    $correctas = 0;
    $preguntas_ids = json_decode($intento['preguntas_json']); 
    $total_real = is_array($preguntas_ids) ? count($preguntas_ids) : 0;

    foreach ($respuestas_usuario as $resp) {
        $stmt = $pdo->prepare("SELECT es_correcta FROM opciones WHERE id_opcion = ? AND id_pregunta = ?");
        $stmt->execute([$resp['id_opcion'], $resp['id_pregunta']]);
        if ($stmt->fetchColumn() == 1) $correctas++;
    }

    // CAMBIO: Regla de tres sobre 100
    $nota_100 = ($total_real > 0) ? ($correctas / $total_real) * 100 : 0;
    
    echo json_encode([
        'status' => 'success',
        'nota' => number_format($nota_100, 2),
        'correctas' => $correctas,
        'total' => $total_real
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>