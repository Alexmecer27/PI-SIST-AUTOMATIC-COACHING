<?php
// controllers/asesor_update_teoria.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$accion = $input['accion']; // 'update' o 'delete'
$id_intento = $input['id_intento'];
$nueva_nota = $input['nueva_nota'] ?? 0;

try {
    if ($accion === 'update') {
        // Validar nota
        if (!is_numeric($nueva_nota) || $nueva_nota < 0 || $nueva_nota > 100) {
            throw new Exception("La nota debe ser un número entre 0 y 100.");
        }

        $sql = "UPDATE intentos SET nota = ?, estado = 'Finalizado' WHERE id_intento = ?";
        $pdo->prepare($sql)->execute([$nueva_nota, $id_intento]);
        
        echo json_encode(['status' => 'success', 'mensaje' => 'Nota actualizada correctamente.']);

    } elseif ($accion === 'delete') {
        // Eliminar intento (Permite al alumno rendir de nuevo)
        $sql = "DELETE FROM intentos WHERE id_intento = ?";
        $pdo->prepare($sql)->execute([$id_intento]);

        echo json_encode(['status' => 'success', 'mensaje' => 'Intento eliminado. El alumno puede rendir de nuevo.']);
    } else {
        throw new Exception("Acción no válida.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
?>