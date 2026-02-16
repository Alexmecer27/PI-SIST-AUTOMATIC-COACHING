<?php
// controllers/asesor_get_colaboradores.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit;
}

try {
    // CORRECCIÓN DEFINITIVA:
    // Unimos 'usuarios' (u) con 'perfil_asesor' (pa)
    // Buscamos el nombre en 'pa'. Si no existe, usamos el email de 'u'.
    
    $sql = "SELECT 
                u.id_usuario, 
                u.email,
                COALESCE(pa.nombre_completo, u.email) as nombre_real
            FROM usuarios u
            LEFT JOIN perfil_asesor pa ON u.id_usuario = pa.id_usuario
            WHERE u.id_rol = 2          -- Solo Asesores
            AND u.id_usuario != ?       -- Excluirme a mí mismo
            ORDER BY nombre_real ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['id_usuario']]);
    $asesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($asesores as $row) {
        // Formato para la lista: "Juan Perez (juan@email.com)"
        // Si el nombre es igual al email (porque no tiene perfil), solo mostramos el email
        $etiqueta = ($row['nombre_real'] !== $row['email']) 
                    ? $row['nombre_real'] . " (" . $row['email'] . ")" 
                    : $row['email'];

        $data[] = [
            'id_usuario' => $row['id_usuario'],
            'texto_mostrar' => $etiqueta
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => "Error BD: " . $e->getMessage()]);
}
?>