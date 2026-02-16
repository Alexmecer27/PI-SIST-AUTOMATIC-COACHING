<?php
// controllers/asesor_lista_alumnos.php

// 1. LIMPIEZA DE BÚFER
ob_start();

session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$codigo = $_GET['codigo'] ?? '';

try {
    if (empty($codigo)) {
        echo json_encode([]);
        exit;
    }

    $pdo->exec("SET NAMES 'utf8mb4'");

    // 2. CONSULTA SQL CORREGIDA (Sin i.intentos_count)
    $sql = "SELECT 
                p.id_usuario,
                p.cedula, 
                p.nombre_completo, 
                e.nombre_empresa, 
                i.nota AS nota_teoria,
                i.estado AS estado_teoria, 
                -- i.intentos_count (QUITADO: Esto se calcula en PHP, no en SQL)
                pr.nota_final AS nota_practica,
                pr.id_tipo_practica
            FROM intentos i
            JOIN sesiones s ON i.id_sesion = s.id_sesion
            JOIN perfil_participante p ON i.id_usuario = p.id_usuario
            JOIN empresas e ON p.id_empresa = e.id_empresa
            LEFT JOIN practica_resultados pr ON i.id_usuario = pr.id_usuario AND i.id_sesion = pr.id_sesion
            WHERE s.codigo_acceso = ?
            ORDER BY i.fecha_inicio ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$codigo]);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. PROCESAMIENTO Y CONTEO
    $alumnosMap = [];
    foreach ($raw_data as $row) {
        $cedula = $row['cedula'];
        
        // Si no existe, inicializamos
        if (!isset($alumnosMap[$cedula])) {
            $alumnosMap[$cedula] = [
                'cedula' => $row['cedula'],
                'nombre_completo' => $row['nombre_completo'],
                'nombre_empresa' => $row['nombre_empresa'],
                'intentos_count' => 0, // Aquí iniciamos el contador en 0
                'nota_teoria' => 0,
                'estado_teoria' => 'En Progreso',
                'nota_practica' => null,
                'id_tipo_practica' => null
            ];
        }

        // Actualizamos con la info más reciente
        $alumnosMap[$cedula]['nota_teoria'] = $row['nota_teoria'];
        $alumnosMap[$cedula]['estado_teoria'] = $row['estado_teoria'];
        
        if ($row['nota_practica'] !== null) {
            $alumnosMap[$cedula]['nota_practica'] = $row['nota_practica'];
            $alumnosMap[$cedula]['id_tipo_practica'] = $row['id_tipo_practica'];
        }
        
        // Aquí es donde sucede la magia del conteo:
        $alumnosMap[$cedula]['intentos_count']++;
    }

    // Limpiar y Enviar
    ob_clean();
    echo json_encode(array_values($alumnosMap));

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>