<?php
// controllers/asesor_get_tipos_practica.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// Recibimos la cédula para identificar al usuario y su sesión actual
$cedula = $_GET['cedula'] ?? '';
$codigo_sesion = $_GET['codigo'] ?? '';

if (empty($cedula) || empty($codigo_sesion)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos']);
    exit;
}

try {
    // 1. Averiguar qué examen (curso) está tomando este alumno con este código
    $sqlInfo = "SELECT s.id_examen, s.id_sesion, p.nombre_completo, p.foto_perfil, e.nombre_empresa, ex.titulo
                FROM sesiones s
                JOIN intentos i ON s.id_sesion = i.id_sesion
                JOIN perfil_participante p ON i.id_usuario = p.id_usuario
                JOIN empresas e ON p.id_empresa = e.id_empresa
                JOIN examenes ex ON s.id_examen = ex.id_examen
                WHERE s.codigo_acceso = ? AND p.cedula = ?
                LIMIT 1";
    
    $stmt = $pdo->prepare($sqlInfo);
    $stmt->execute([$codigo_sesion, $cedula]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Alumno no encontrado en este curso.']);
        exit;
    }

    // 2. Buscar las opciones de práctica disponibles para ESE examen
    $stmtTipos = $pdo->prepare("SELECT id_tipo_practica, nombre_certificacion FROM practica_tipos WHERE id_examen = ?");
    $stmtTipos->execute([$info['id_examen']]);
    $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'alumno' => $info, // Devolvemos info para llenar la modal (Foto, nombre)
        'opciones' => $tipos
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>