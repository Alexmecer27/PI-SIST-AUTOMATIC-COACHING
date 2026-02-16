<?php
// controllers/admin_buscar_certificado.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// 1. Seguridad: Solo Admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit;
}

// 2. Recibir Par芍metros (C谷dula o C車digo)
// Nota: Ahora aceptamos que lleguen vac赤os para el modo "Monitor en Vivo"
$cedula = $_GET['q'] ?? '';      // B迆squeda por C谷dula (compatible con tu c車digo anterior)
$codigo = $_GET['codigo'] ?? ''; // Nuevo Filtro por C車digo

try {
    // 3. Construcci車n Din芍mica de la Consulta
    // Usamos WHERE 1=1 para poder concatenar condiciones AND f芍cilmente
    $sql = "SELECT 
                p.nombre_completo, 
                p.cedula, 
                e.nombre_empresa, 
                ex.titulo AS nombre_curso, 
                s.codigo_acceso,
                s.tipo_evento, 
                i.estado,
                MAX(i.nota) as nota_teoria,
                MAX(pr.nota_final) as nota_practica,
                MAX(pr.observaciones) as observaciones

            FROM intentos i
            JOIN sesiones s ON i.id_sesion = s.id_sesion
            JOIN examenes ex ON s.id_examen = ex.id_examen
            JOIN perfil_participante p ON i.id_usuario = p.id_usuario
            JOIN empresas e ON p.id_empresa = e.id_empresa
            LEFT JOIN practica_resultados pr ON i.id_usuario = pr.id_usuario AND i.id_sesion = pr.id_sesion
            
            WHERE 1=1"; 

    $params = [];

    // Si hay c谷dula, filtramos
    if (!empty($cedula)) {
        $sql .= " AND p.cedula LIKE ?";
        $params[] = "%$cedula%";
    }

    // Si hay c車digo, filtramos
    if (!empty($codigo)) {
        $sql .= " AND s.codigo_acceso = ?";
        $params[] = $codigo;
    }

    // 4. Ordenamiento y L赤mite
    // Ordenamos por fecha de inicio (m芍s recientes primero) para el monitor
    $sql .= " GROUP BY p.id_usuario, s.id_sesion 
              ORDER BY i.fecha_inicio DESC";

    // Si NO hay filtros (Monitor en Vivo), limitamos a 10 resultados
    if (empty($cedula) && empty($codigo)) {
        $sql .= " LIMIT 10";
    } else {
        // Si hay b迆squeda, mostramos m芍s resultados
        $sql .= " LIMIT 50";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Procesar Datos
    $listaFinal = [];
    foreach ($resultados as $data) {
        
        $notaTeoria = round($data['nota_teoria']);
        $notaPractica = ($data['nota_practica'] !== null) ? round($data['nota_practica']) : 0;
        
        // L車gica de aprobaci車n
        $teoriaOk = ($notaTeoria >= 80);
        // La pr芍ctica cuenta si existe nota y es mayor a 80
        $practicaOk = ($data['nota_practica'] !== null && $notaPractica >= 80);
        
        // Aprobado final requiere ambas
        $aprobadoFinal = ($teoriaOk && $practicaOk);

        $listaFinal[] = [
            'datos' => [
                'nombre_completo' => $data['nombre_completo'],
                'cedula' => $data['cedula'],
                'nombre_empresa' => $data['nombre_empresa'],
                'curso' => $data['nombre_curso'], 
                'examen' => $data['nombre_curso'], // Enviamos duplicado para compatibilidad con JS
                'codigo_acceso' => $data['codigo_acceso'],
                'tipo_evento' => $data['tipo_evento']
            ],
            'notas' => [
                'teoria' => $notaTeoria,
                'practica' => ($data['nota_practica'] !== null) ? $notaPractica : '-', // Guion si no hay nota
                'teoria_ok' => $teoriaOk,
                'practica_ok' => $practicaOk
            ],
            'feedback' => [
                'observaciones' => $data['observaciones'] ?? 'Sin observaciones registradas.'
            ],
            'aprobado_final' => $aprobadoFinal,
            'estado' => $data['estado'] ?? 'Finalizado'
        ];
    }

    // 6. Respuesta JSON
    echo json_encode([
        'status' => 'success', 
        'cantidad' => count($listaFinal), 
        'lista' => $listaFinal
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Error DB: ' . $e->getMessage()]);
}
?>