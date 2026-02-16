<?php
// controllers/asesor_listar_colegas.php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([]); exit;
}

try {
    // Buscar todos los asesores (rol 2) que esten activos
    // Y EXCLUIMOS al usuario actual ( id_usuario != session )
    $sql = "SELECT u.id_usuario, p.nombre_completo, u.email 
            FROM usuarios u
            JOIN perfil_asesor p ON u.id_usuario = p.id_usuario
            WHERE u.id_rol = 2 
            AND u.estado = 'Activo'
            AND u.id_usuario != ?
            ORDER BY p.nombre_completo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['id_usuario']]);
    
    $colegas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($colegas);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>