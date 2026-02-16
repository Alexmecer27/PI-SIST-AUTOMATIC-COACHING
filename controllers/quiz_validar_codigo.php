<?php
// controllers/quiz_validar_codigo.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// Forzar zona horaria también aquí por seguridad
date_default_timezone_set('America/Guayaquil');

$input = json_decode(file_get_contents('php://input'), true);
$codigo_ingresado = trim($input['codigo'] ?? '');

if (empty($codigo_ingresado)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Por favor escribe un código.']);
    exit;
}

try {
    // 1. BUSCAR CÓDIGO (Quitamos el "AND estado='Abierto'" para encontrarlo siempre)
    $stmt = $pdo->prepare("SELECT * FROM sesiones WHERE codigo_acceso = ?");
    $stmt->execute([$codigo_ingresado]);
    $sesion = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. VALIDACIONES DETALLADAS
    
    // A) ¿El código existe en la tabla?
    if (!$sesion) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Código no encontrado. Verifica la escritura (Ej: SCURS-67452).']);
        exit;
    }

    // B) ¿Está cerrado manualmente?
    if ($sesion['estado'] === 'Cerrado') {
        echo json_encode(['status' => 'error', 'mensaje' => 'Este examen ha sido cerrado por el instructor.']);
        exit;
    }

    // C) ¿Está vencido por fecha?
    $ahora = date('Y-m-d H:i:s');
    if ($ahora > $sesion['fecha_expiracion']) {
        echo json_encode(['status' => 'error', 'mensaje' => 'El código ha expirado. Solicita uno nuevo.']);
        exit;
    }

    // 3. ÉXITO
    $_SESSION['id_sesion_activa'] = $sesion['id_sesion'];
    $_SESSION['id_examen_activo'] = $sesion['id_examen'];

    echo json_encode([
        'status' => 'success', 
        'id_examen' => $sesion['id_examen'] 
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error técnico: ' . $e->getMessage()]);
}
?>