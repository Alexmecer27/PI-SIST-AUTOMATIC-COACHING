<?php
// controllers/admin_get_intentos.php
session_start();
require '../config/db.php'; // Asegúrate de que la ruta a db.php sea correcta
header('Content-Type: application/json; charset=utf-8');

// Seguridad: Solo Admin
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode([]); exit;
}

// 1. Recibir Filtros del Javascript
$busqueda = $_GET['busqueda'] ?? ''; // Cédula
$codigo   = $_GET['codigo'] ?? '';   // Código del curso

try {
    // Consulta Base
    $sql = "SELECT 
                p.nombre_completo, 
                p.cedula, 
                e.nombre_empresa, 
                ex.titulo AS examen,
                s.codigo_acceso,
                i.nota, 
                i.estado, 
                i.fecha_inicio,
                i.fecha_fin,
                pr.aprobado as aprobado_final,
                s.tipo_evento
            FROM intentos i
            JOIN sesiones s ON i.id_sesion = s.id_sesion
            JOIN examenes ex ON s.id_examen = ex.id_examen
            JOIN perfil_participante p ON i.id_usuario = p.id_usuario
            JOIN empresas e ON p.id_empresa = e.id_empresa
            LEFT JOIN practica_resultados pr ON i.id_usuario = pr.id_usuario AND i.id_sesion = pr.id_sesion
            WHERE 1=1";

    $params = [];

    // 2. Aplicar Filtros Dinámicos
    if (!empty($busqueda)) {
        $sql .= " AND p.cedula LIKE ?";
        $params[] = "%$busqueda%";
    }

    if (!empty($codigo)) {
        $sql .= " AND s.codigo_acceso = ?";
        $params[] = $codigo;
    }

    // 3. Ordenamiento y Límite
    $sql .= " ORDER BY i.fecha_inicio DESC";
    
    // Si NO hay filtros, mostramos solo los últimos 10 (Modo Monitor)
    if (empty($busqueda) && empty($codigo)) {
        $sql .= " LIMIT 10"; 
    } else {
        // Si hay filtros, mostramos más resultados
        $sql .= " LIMIT 50";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>