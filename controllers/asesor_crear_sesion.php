<?php
// controllers/asesor_crear_sesion.php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['status' => 'error', 'error' => 'No autorizado']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_examen = $input['id_examen'];
$tipo_evento = $input['tipo_evento'];
$nombre_curso = $input['nombre_curso'] ?? 'CURSO';

try {
    // INICIAR TRANSACCIÓN
    $pdo->beginTransaction();

    // 1. GENERAR CÓDIGO (Lógica de siglas)
    $prefijo_tipo = ($tipo_evento === 'Curso') ? 'C' : 'S';
    $curso_limpio = strtoupper(preg_replace("/[^A-Za-z0-9]/", '', $nombre_curso));
    $siglas_curso = substr(str_pad($curso_limpio, 4, 'X'), 0, 4);
    $prefijo_final = $prefijo_tipo . $siglas_curso; 

    $codigo_final = '';
    $unico = false;
    
    // Intentar hasta 10 veces para evitar duplicados
    for ($i=0; $i<10; $i++) {
        $numeros = random_int(10000, 99999);
        $codigo_temp = $prefijo_final . "-" . $numeros; 
        
        $stmtCheck = $pdo->prepare("SELECT id_sesion FROM sesiones WHERE codigo_acceso = ?");
        $stmtCheck->execute([$codigo_temp]);
        if ($stmtCheck->rowCount() == 0) {
            $codigo_final = $codigo_temp;
            $unico = true;
            break;
        }
    }

    if (!$unico) throw new Exception("No se pudo generar un código único.");

    // 2. INSERTAR SESIÓN
    // IMPORTANTE: Aseguramos estado 'Activo' y fecha de expiración correcta
    $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $sqlSesion = "INSERT INTO sesiones (codigo_acceso, id_examen, id_asesor, tipo_evento, fecha_expiracion, estado, fecha_creacion) 
                  VALUES (?, ?, ?, ?, ?, 'Activo', NOW())";
    $stmt = $pdo->prepare($sqlSesion);
    $stmt->execute([$codigo_final, $id_examen, $_SESSION['id_usuario'], $tipo_evento, $expira]);
    
    $id_sesion = $pdo->lastInsertId();

    // 3. AUTO-ASIGNAR PERMISO AL CREADOR (Vital para que aparezca en el panel)
    $sqlPermiso = "INSERT INTO sesion_asesores (id_sesion, id_asesor) VALUES (?, ?)";
    $stmtPermiso = $pdo->prepare($sqlPermiso);
    $stmtPermiso->execute([$id_sesion, $_SESSION['id_usuario']]);

    // CONFIRMAR TRANSACCIÓN
    $pdo->commit();

    echo json_encode([
        'status' => 'success', 
        'codigo' => $codigo_final, 
        'expira' => date('d/m H:i', strtotime($expira))
    ]);

} catch (Exception $e) {
    // SI FALLA, DESHACER TODO
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => "Error DB: " . $e->getMessage()]);
}
?>