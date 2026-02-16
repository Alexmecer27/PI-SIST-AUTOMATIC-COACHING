<?php
// CÓDIGO MAESTRO PARA GUARDADO DE EXÁMENES
session_start();

// 1. Conexión a Base de Datos (Ajuste automático de ruta)
if (file_exists('../config/db.php')) {
    require '../config/db.php';
} elseif (file_exists('config/db.php')) {
    require 'config/db.php';
} else {
    die(json_encode(['status'=>'error', 'mensaje'=>'No se encuentra config/db.php']));
}

header('Content-Type: application/json');

// --- SISTEMA DE LOG (Para ver errores en un archivo de texto) ---
function escribirLog($msg) {
    $archivo = __DIR__ . '/debug_guardado.txt';
    $fecha = date('Y-m-d H:i:s');
    file_put_contents($archivo, "[$fecha] $msg" . PHP_EOL, FILE_APPEND);
}

escribirLog("--- INICIANDO PROCESO DE GUARDADO ---");

// 2. Recibir y Validar Datos
$input = json_decode(file_get_contents('php://input'), true);
$respuestas = $input['respuestas'] ?? [];
$id_examen = $input['id_examen'] ?? null;

// Si el frontend manda 'id_intento' en vez de examen, buscamos el examen
if (!$id_examen && isset($input['id_intento'])) {
    $stmt = $pdo->prepare("SELECT id_examen FROM sesiones s JOIN intentos i ON s.id_sesion = i.id_sesion WHERE i.id_intento = ?");
    $stmt->execute([$input['id_intento']]);
    $id_examen = $stmt->fetchColumn();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;
$id_sesion = $_SESSION['id_sesion_activa'] ?? null;

if (empty($respuestas)) {
    escribirLog("ERROR: Array de respuestas vacío.");
    echo json_encode(['status' => 'error', 'mensaje' => 'No llegaron respuestas.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // ---------------------------------------------------------
    // PASO A: Calcular Nota
    // ---------------------------------------------------------
    $correctas = 0;
    foreach ($respuestas as $r) {
        $stmt = $pdo->prepare("SELECT es_correcta FROM opciones WHERE id_opcion = ?");
        $stmt->execute([$r['id_opcion']]);
        if ($stmt->fetchColumn() == 1) $correctas++;
    }
    
    $total = count($respuestas);
    $nota = ($total > 0) ? ($correctas / $total) * 100 : 0;
    escribirLog("Nota calculada: $nota ($correctas de $total)");

    // ---------------------------------------------------------
    // PASO B: Guardar en RESULTADOS (Cabecera)
    // ---------------------------------------------------------
    // Usamos 'fecha_intento' como vimos en tu base de datos
    $sqlRes = "INSERT INTO resultados (id_usuario, id_examen, puntaje, id_sesion, fecha_intento) VALUES (?, ?, ?, ?, NOW())";
    $stmtRes = $pdo->prepare($sqlRes);
    
    // Si id_examen no existe, usamos 0 para evitar error fatal
    if (!$stmtRes->execute([$id_usuario, $id_examen ?? 0, $nota, $id_sesion])) {
        throw new Exception("Error al insertar en resultados: " . implode(" ", $stmtRes->errorInfo()));
    }
    
    $id_resultado = $pdo->lastInsertId();
    if (!$id_resultado) throw new Exception("No se generó ID de resultado.");
    escribirLog("Guardado en resultados. ID: $id_resultado");

    // ---------------------------------------------------------
    // PASO C: Guardar en RESULTADOS_DETALLE (Hijos)
    // ---------------------------------------------------------
    $sqlDet = "INSERT INTO resultados_detalle (id_resultado, id_pregunta, id_opcion_seleccionada) VALUES (?, ?, ?)";
    $stmtDet = $pdo->prepare($sqlDet);

    foreach ($respuestas as $r) {
        if (!$stmtDet->execute([$id_resultado, $r['id_pregunta'], $r['id_opcion']])) {
            throw new Exception("Error al insertar detalle pregunta " . $r['id_pregunta']);
        }
    }
    escribirLog("Detalles guardados correctamente.");

    // ---------------------------------------------------------
    // PASO D: Actualizar INTENTOS (Cerrar ciclo)
    // ---------------------------------------------------------
    if ($id_sesion) {
        $sqlUpd = "UPDATE intentos SET nota = ?, estado = 'Finalizado', fecha_fin = NOW() WHERE id_sesion = ? AND id_usuario = ?";
        $pdo->prepare($sqlUpd)->execute([$nota, $id_sesion, $id_usuario]);
    }

    $pdo->commit();
    escribirLog("PROCESO TERMINADO CON ÉXITO.");
    
    echo json_encode(['status' => 'success', 'nota' => $nota, 'mensaje' => 'Examen guardado correctamente']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    escribirLog("ERROR CRÍTICO: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>