<?php
// controllers/quiz_init.php

// 1. ACTIVAR LIMPIEZA DE BÚFER (Atrapa cualquier error sucio de PHP)
ob_start();

session_start();
require '../config/db.php';

// Definir cabecera JSON
header('Content-Type: application/json; charset=utf-8');

// Variable para capturar errores fatales
$error_debug = "";

try {
    // Verificar sesión
    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_sesion_activa'])) {
        throw new Exception("Tu sesión ha caducado. Vuelve a ingresar el código en el inicio.");
    }

    $id_usuario = $_SESSION['id_usuario'];
    $id_sesion = $_SESSION['id_sesion_activa'];

    // CONFIGURACIÓN
    $LIMITE_INTENTOS = 2;
    $TIEMPO_LIMITE_MIN = 60; // 1 Hora

    // 1. Verificar Intentos
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM intentos WHERE id_usuario = ? AND id_sesion = ? AND estado = 'Finalizado'");
    $stmtCount->execute([$id_usuario, $id_sesion]);
    $intentos_usados = $stmtCount->fetchColumn();

    if ($intentos_usados >= $LIMITE_INTENTOS) {
        throw new Exception("Has agotado tus 2 intentos permitidos.");
    }

    // 2. Buscar intento activo
    $stmtActivo = $pdo->prepare("SELECT * FROM intentos WHERE id_usuario = ? AND id_sesion = ? AND estado = 'En Progreso'");
    $stmtActivo->execute([$id_usuario, $id_sesion]);
    $intento_actual = $stmtActivo->fetch(PDO::FETCH_ASSOC);

    $preguntas_ids = [];
    $fecha_inicio = null;
    $id_intento = null;

    if ($intento_actual) {
        // --- RECUPERAR ---
        $fecha_inicio = $intento_actual['fecha_inicio'];
        $id_intento = $intento_actual['id_intento'];
        
        // Validar tiempo
        $minutos_pasados = (time() - strtotime($fecha_inicio)) / 60;
        if ($minutos_pasados > $TIEMPO_LIMITE_MIN) {
            $pdo->prepare("UPDATE intentos SET estado = 'Finalizado', nota = 0 WHERE id_intento = ?")->execute([$id_intento]);
            throw new Exception("El tiempo de tu intento anterior se agotó.");
        }

        // Decodificar JSON de preguntas
        $preguntas_ids = json_decode($intento_actual['preguntas_json'], true);
        
        if (!is_array($preguntas_ids)) {
            // Si el JSON está corrupto, forzamos cierre para generar uno nuevo (Edge case)
            $pdo->prepare("UPDATE intentos SET estado = 'Finalizado', nota = 0 WHERE id_intento = ?")->execute([$id_intento]);
            throw new Exception("Error en datos del intento previo. Por favor intenta de nuevo.");
        }

    } else {
        // --- NUEVO INTENTO ---
        $stmtSesion = $pdo->prepare("SELECT id_examen FROM sesiones WHERE id_sesion = ?");
        $stmtSesion->execute([$id_sesion]);
        $id_examen = $stmtSesion->fetchColumn();

        if (!$id_examen) {
            throw new Exception("No se encontró el examen asociado a este código.");
        }

        // Seleccionar 20 preguntas
        $stmtRand = $pdo->prepare("SELECT id_pregunta FROM preguntas WHERE id_examen = ? ORDER BY RAND() LIMIT 20");
        $stmtRand->execute([$id_examen]);
        $preguntas_raw = $stmtRand->fetchAll(PDO::FETCH_COLUMN);

        if (empty($preguntas_raw)) {
            throw new Exception("NO HAY PREGUNTAS CARGADAS. Pide al administrador que cargue el Banco de Preguntas para el examen ID: " . $id_examen);
        }

        $json_preguntas = json_encode($preguntas_raw);
        $stmtInsert = $pdo->prepare("INSERT INTO intentos (id_usuario, id_sesion, preguntas_json, estado) VALUES (?, ?, ?, 'En Progreso')");
        $stmtInsert->execute([$id_usuario, $id_sesion, $json_preguntas]);
        
        $id_intento = $pdo->lastInsertId();
        $preguntas_ids = $preguntas_raw;
        $fecha_inicio = date('Y-m-d H:i:s');
    }

    // 3. Cargar contenido de preguntas
    if (empty($preguntas_ids)) {
         throw new Exception("Error interno: Lista de preguntas vacía.");
    }

    $in  = str_repeat('?,', count($preguntas_ids) - 1) . '?';
    $sqlLoad = "SELECT id_pregunta, enunciado, imagen_url, tipo FROM preguntas WHERE id_pregunta IN ($in)";
    $stmtLoad = $pdo->prepare($sqlLoad);
    $stmtLoad->execute($preguntas_ids);
    $preguntas_db = $stmtLoad->fetchAll(PDO::FETCH_ASSOC);

    // Cargar opciones
    foreach ($preguntas_db as &$preg) {
        $stmtOp = $pdo->prepare("SELECT id_opcion, texto_opcion FROM opciones WHERE id_pregunta = ?");
        $stmtOp->execute([$preg['id_pregunta']]);
        $opciones = $stmtOp->fetchAll(PDO::FETCH_ASSOC);
        shuffle($opciones);
        $preg['opciones'] = $opciones;
    }

    // Calcular tiempo
    $segundos_pasados = time() - strtotime($fecha_inicio);
    $segundos_restantes = ($TIEMPO_LIMITE_MIN * 60) - $segundos_pasados;

    // PREPARAR RESPUESTA LIMPIA
    $response = [
        'status' => 'success',
        'id_intento' => $id_intento,
        'tiempo_restante' => $segundos_restantes,
        'preguntas' => $preguntas_db,
        'intento_numero' => $intentos_usados + 1,
        'intentos_totales' => $LIMITE_INTENTOS
    ];

    // LIMPIAR BÚFER Y ENVIAR
    ob_clean(); 
    echo json_encode($response);

} catch (Exception $e) {
    // CAPTURAR ERROR Y ENVIAR COMO JSON
    ob_clean(); 
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
} catch (Throwable $t) {
    // CAPTURAR ERRORES FATALES DE PHP 7+
    ob_clean();
    echo json_encode(['status' => 'error', 'mensaje' => 'Error Crítico del Servidor: ' . $t->getMessage()]);
}
?>