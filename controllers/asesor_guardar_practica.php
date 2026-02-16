<?php
// controllers/asesor_guardar_practica.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cedula = $input['cedula'];
$codigo_sesion = $input['codigo_sesion'];
$id_tipo = $input['id_tipo'];
$respuestas = $input['respuestas']; 
$observacion = $input['observacion'] ?? '';
$equipos_extra = $input['equipos_extra'] ?? []; 

try {
    // 1. IDs
    $stmtIds = $pdo->prepare("SELECT p.id_usuario, s.id_sesion 
                              FROM perfil_participante p
                              JOIN intentos i ON p.id_usuario = i.id_usuario
                              JOIN sesiones s ON i.id_sesion = s.id_sesion
                              WHERE p.cedula = ? AND s.codigo_acceso = ? LIMIT 1");
    $stmtIds->execute([$cedula, $codigo_sesion]);
    $data = $stmtIds->fetch(PDO::FETCH_ASSOC);

    if (!$data) throw new Exception("Datos inválidos.");

    // 2. CALCULAR NOTA
    $suma = 0;
    $total_items = count($respuestas);
    if ($total_items == 0) throw new Exception("Sin respuestas.");

    foreach ($respuestas as $puntos) {
        $val = (int)$puntos;
        if ($val < 0 || $val > 5) throw new Exception("Valor inválido.");
        $suma += $val;
    }

    $promedio = $suma / $total_items; 
    $nota_final_100 = round(($promedio / 5) * 100, 2); 
    $aprobado = ($nota_final_100 >= 80) ? 1 : 0;
    $json_respuestas = json_encode($respuestas);

    // --- CORRECCIÓN AQUÍ: NOMBRE DE LA TABLA 'practica_tipos' ---
    
    // Obtener nombre del tipo principal
    // CAMBIADO: 'tipos_practica' -> 'practica_tipos'
    $stmtNombre = $pdo->prepare("SELECT nombre_certificacion FROM practica_tipos WHERE id_tipo_practica = ?");
    $stmtNombre->execute([$id_tipo]);
    $nombrePrincipal = $stmtNombre->fetchColumn();

    // Si por alguna razón falla la consulta (ej. ID no existe), usamos un texto genérico para no romper
    if (!$nombrePrincipal) $nombrePrincipal = "Equipo Principal";

    // Combinar principal con extras
    $listaFinal = [$nombrePrincipal];
    if (!empty($equipos_extra) && is_array($equipos_extra)) {
        foreach ($equipos_extra as $extra) {
            // Evitar duplicados
            if ($extra !== $nombrePrincipal) {
                $listaFinal[] = $extra;
            }
        }
    }
    // Convertir a texto
    $tipos_equipos_texto = implode(" | ", $listaFinal);
    // ---------------------------------------------

    // 3. GUARDAR
    $stmtCheck = $pdo->prepare("SELECT id_practica FROM practica_resultados WHERE id_usuario = ? AND id_sesion = ?");
    $stmtCheck->execute([$data['id_usuario'], $data['id_sesion']]);
    $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        $sql = "UPDATE practica_resultados SET 
                id_asesor = ?, id_tipo_practica = ?, nota_final = ?, aprobado = ?, observaciones = ?, detalle_json = ?, fecha_evaluacion = NOW(), tipos_equipos = ?
                WHERE id_practica = ?";
        $pdo->prepare($sql)->execute([
            $_SESSION['id_usuario'], $id_tipo, $nota_final_100, $aprobado, $observacion, $json_respuestas, $tipos_equipos_texto, $existe['id_practica']
        ]);
        $mensaje = "Evaluación actualizada correctamente.";
    } else {
        $sql = "INSERT INTO practica_resultados 
                (id_usuario, id_sesion, id_asesor, id_tipo_practica, nota_final, aprobado, observaciones, detalle_json, tipos_equipos) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $data['id_usuario'], $data['id_sesion'], $_SESSION['id_usuario'], $id_tipo,
            $nota_final_100, $aprobado, $observacion, $json_respuestas, $tipos_equipos_texto
        ]);
        $mensaje = "Evaluación guardada correctamente.";
    }

    echo json_encode(['status' => 'success', 'nota' => $nota_final_100, 'aprobado' => $aprobado, 'mensaje' => $mensaje]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>