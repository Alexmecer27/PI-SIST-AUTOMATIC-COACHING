<?php
// controllers/quiz_confirmar.php
session_start();

// Configuración de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_confirmar.txt');

require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// LOG para depuración
function logConf($msg) {
    file_put_contents('debug_confirmar.txt', date('H:i:s') . " - $msg" . PHP_EOL, FILE_APPEND);
}

logConf("--- INICIO CONFIRMACIÓN ---");

$input = json_decode(file_get_contents('php://input'), true);

$accion = $input['accion'] ?? 'guardar';
$id_intento = $input['id_intento'] ?? 0;
$nota_calculada = $input['nota'] ?? 0;
$respuestas = $input['respuestas'] ?? []; // <--- INTENTAMOS CAPTURAR LAS RESPUESTAS
$id_usuario = $_SESSION['id_usuario'] ?? null;

// Validación rápida
if (!$id_intento) {
    logConf("ERROR: No llegó ID Intento.");
    echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. ACTUALIZAR EL INTENTO (Lo que ya hacías)
    $sqlUpd = "UPDATE intentos SET nota = ?, fecha_fin = NOW(), estado = 'Finalizado' WHERE id_intento = ?";
    $stmtUpd = $pdo->prepare($sqlUpd);
    $stmtUpd->execute([$nota_calculada, $id_intento]);
    logConf("Intento $id_intento cerrado con nota $nota_calculada");

    // 2. GUARDAR EN RESULTADOS Y DETALLES (LO NUEVO QUE FALTABA)
    if (!empty($respuestas)) {
        
        // A) Obtenemos datos extra necesarios (id_examen, id_sesion) desde el intento
        $stmtDatos = $pdo->prepare("SELECT id_sesion, (SELECT id_examen FROM sesiones WHERE id_sesion = i.id_sesion) as id_examen FROM intentos i WHERE id_intento = ?");
        $stmtDatos->execute([$id_intento]);
        $datosExtra = $stmtDatos->fetch(PDO::FETCH_ASSOC);
        
        $id_sesion = $datosExtra['id_sesion'] ?? null;
        $id_examen = $datosExtra['id_examen'] ?? 0;

        // B) Insertar Cabecera en 'resultados'
        // Usamos 'fecha_intento' según tu estructura
        $sqlRes = "INSERT INTO resultados (id_usuario, id_examen, puntaje, id_sesion, fecha_intento) VALUES (?, ?, ?, ?, NOW())";
        $stmtRes = $pdo->prepare($sqlRes);
        $stmtRes->execute([$id_usuario, $id_examen, $nota_calculada, $id_sesion]);
        
        $id_resultado = $pdo->lastInsertId();
        logConf("Guardado en resultados. ID generado: $id_resultado");

        // C) Insertar Detalles en 'resultados_detalle'
        $sqlDet = "INSERT INTO resultados_detalle (id_resultado, id_pregunta, id_opcion_seleccionada) VALUES (?, ?, ?)";
        $stmtDet = $pdo->prepare($sqlDet);

        foreach ($respuestas as $r) {
            // Validamos que vengan los datos mínimos
            if (isset($r['id_pregunta']) && isset($r['id_opcion'])) {
                $stmtDet->execute([$id_resultado, $r['id_pregunta'], $r['id_opcion']]);
            }
        }
        logConf("Detalles guardados exitosamente.");

    } else {
        logConf("ADVERTENCIA: El frontend NO envió las respuestas a este archivo. Solo se guardó la nota.");
    }

    $pdo->commit();

    // 3. RESPUESTA AL CLIENTE
    if ($accion === 'guardar') {
        echo json_encode([
            'status' => 'success', 
            'accion_siguiente' => 'go_home', 
            'mensaje' => 'Examen finalizado y guardado con detalle.'
        ]);
    } else {
        echo json_encode([
            'status' => 'success', 
            'accion_siguiente' => 'reload', 
            'mensaje' => 'Intento guardado. Reiniciando...'
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    logConf("ERROR CRÍTICO: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>