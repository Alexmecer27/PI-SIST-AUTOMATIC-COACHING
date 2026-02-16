<?php
// controllers/asesor_get_checklist.php
ob_start();
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$id_tipo = $_GET['id_tipo'] ?? 0;
$cedula = $_GET['cedula'] ?? '';
$codigo = $_GET['codigo'] ?? '';

try {
    if (!$id_tipo || !$cedula || !$codigo) throw new Exception("Faltan datos.");
    $pdo->exec("SET NAMES 'utf8mb4'");

    // 1. Datos Perfil
    $stmtData = $pdo->prepare("SELECT p.id_usuario, p.nombre_completo, p.foto_perfil, e.nombre_empresa, s.id_sesion 
                               FROM perfil_participante p
                               JOIN empresas e ON p.id_empresa = e.id_empresa
                               JOIN intentos i ON p.id_usuario = i.id_usuario
                               JOIN sesiones s ON i.id_sesion = s.id_sesion
                               WHERE p.cedula = ? AND s.codigo_acceso = ? LIMIT 1");
    $stmtData->execute([$cedula, $codigo]);
    $perfil = $stmtData->fetch(PDO::FETCH_ASSOC);

    if (!$perfil) throw new Exception("Participante no encontrado.");

    // 2. BUSCAR DATOS PREVIOS (PARA EDICIÓN)
    $stmtCheck = $pdo->prepare("SELECT nota_final, aprobado, observaciones, detalle_json FROM practica_resultados 
                                WHERE id_usuario = ? AND id_sesion = ?");
    $stmtCheck->execute([$perfil['id_usuario'], $perfil['id_sesion']]);
    $resultadoPrevio = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    // 3. Cargar Preguntas
    $stmtTipo = $pdo->prepare("SELECT nombre_certificacion FROM practica_tipos WHERE id_tipo_practica = ?");
    $stmtTipo->execute([$id_tipo]);
    $tipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);

    $stmtPreg = $pdo->prepare("SELECT id_pregunta_practica, texto_item FROM practica_preguntas WHERE id_tipo_practica = ? ORDER BY orden ASC");
    $stmtPreg->execute([$id_tipo]);
    $preguntas = $stmtPreg->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'perfil' => [
            'nombre' => $perfil['nombre_completo'],
            'empresa' => $perfil['nombre_empresa'],
            'foto' => $perfil['foto_perfil']
        ],
        'titulo' => $tipo['nombre_certificacion'],
        'preguntas' => $preguntas,
        // DATOS PARA EDICIÓN (Si existen, se envían; si no, van null)
        'respuestas_guardadas' => $resultadoPrevio ? json_decode($resultadoPrevio['detalle_json']) : null,
        'observacion_guardada' => $resultadoPrevio ? $resultadoPrevio['observaciones'] : ''
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>