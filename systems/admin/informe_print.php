<?php
// systems/admin/informe_print.php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) die("Acceso denegado.");

$cedula = $_GET['cedula'] ?? '';
$codigo = $_GET['codigo'] ?? '';

try {
    // 1. OBTENER DATOS PRINCIPALES
    // Traemos datos del participante, sesión y notas generales
    $sql = "SELECT 
                p.id_usuario, s.id_sesion,
                p.nombre_completo, p.cedula, p.cargo, 
                e.nombre_empresa, ex.titulo AS nombre_curso, 
                s.fecha_creacion, s.tipo_evento,
                MAX(i.nota) as nota_teoria,
                MAX(pr.nota_final) as nota_practica,
                MAX(pr.observaciones) as observaciones,
                MAX(pr.detalle_json) as detalle_json,
                MAX(pr.tipos_equipos) as tipos_equipos
            FROM intentos i
            JOIN sesiones s ON i.id_sesion = s.id_sesion
            JOIN examenes ex ON s.id_examen = ex.id_examen
            JOIN perfil_participante p ON i.id_usuario = p.id_usuario
            JOIN empresas e ON p.id_empresa = e.id_empresa
            LEFT JOIN practica_resultados pr ON i.id_usuario = pr.id_usuario AND i.id_sesion = pr.id_sesion
            WHERE p.cedula = ? AND s.codigo_acceso = ? 
            GROUP BY p.id_usuario, s.id_sesion LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cedula, $codigo]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) die("Datos no encontrados.");

    // 2. CÁLCULOS DE NOTAS Y ESTADO
    $notaT = round($data['nota_teoria']);
    $notaP = ($data['nota_practica'] !== null) ? round($data['nota_practica']) : 0;
    
    $teoriaOk = ($notaT >= 80);
    $practicaOk = ($data['nota_practica'] !== null && $notaP >= 80);
    $aprobado = ($teoriaOk && $practicaOk);

    $estadoTexto = ($data['tipo_evento'] === 'Prueba de Selección') 
                   ? ($aprobado ? "APTO" : "NO APTO") 
                   : ($aprobado ? "APROBADO" : "NO APROBADO");
    
    $colorEstado = $aprobado ? "#27ae60" : "#c0392b";

    // ---------------------------------------------------------
    // 3. PROCESAR ERRORES PRÁCTICOS (Checklist Operativo)
    // ---------------------------------------------------------
    $erroresPracticos = [];
    if (!empty($data['detalle_json'])) {
        $respuestas = json_decode($data['detalle_json'], true);
        if (is_array($respuestas) && count($respuestas) > 0) {
            $idsPreguntas = array_keys($respuestas);
            if(!empty($idsPreguntas)) {
                $placeholders = implode(',', array_fill(0, count($idsPreguntas), '?'));
                $sqlItems = "SELECT id_pregunta_practica, texto_item FROM practica_preguntas WHERE id_pregunta_practica IN ($placeholders)";
                $stmtIt = $pdo->prepare($sqlItems);
                $stmtIt->execute($idsPreguntas);
                $itemsDB = $stmtIt->fetchAll(PDO::FETCH_KEY_PAIR);

                foreach ($respuestas as $idPregunta => $puntaje) {
                    if ($puntaje < 5) { // Si la nota es menor a 5, es una falla
                        $texto = $itemsDB[$idPregunta] ?? "Ítem Práctico #$idPregunta";
                        $erroresPracticos[] = [ 'item' => $texto, 'nota' => $puntaje ];
                    }
                }
            }
        }
    }

    // ---------------------------------------------------------
    // 4. PROCESAR ERRORES TEÓRICOS (Preguntas del Examen)
    // ---------------------------------------------------------
    $erroresTeoricos = [];
    if ($data['id_usuario'] && $data['id_sesion']) {
        // Consultamos la tabla donde guardaste los resultados (resultados_detalle)
        // y filtramos donde la opción elegida NO es la correcta (es_correcta = 0)
        $sqlErr = "SELECT p.enunciado 
                   FROM resultados r
                   JOIN resultados_detalle rd ON r.id_resultado = rd.id_resultado
                   JOIN preguntas p ON rd.id_pregunta = p.id_pregunta
                   JOIN opciones o ON rd.id_opcion_seleccionada = o.id_opcion
                   WHERE r.id_usuario = ? 
                     AND r.id_sesion = ?
                     AND o.es_correcta = 0
                   ORDER BY r.id_resultado DESC"; // Trae las del último intento
        
        $stmtErr = $pdo->prepare($sqlErr);
        $stmtErr->execute([$data['id_usuario'], $data['id_sesion']]);
        $erroresTeoricos = $stmtErr->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe - <?php echo $estadoTexto; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; padding: 40px; color: #333; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { height: 60px; margin-bottom: 10px; }
        h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        h2 { margin: 5px 0 0; font-size: 16px; color: #666; font-weight: normal; }
        
        .info-box { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .label { font-weight: bold; color: #555; }

        .result-section { text-align: center; margin: 30px 0; padding: 20px; border: 2px solid <?php echo $colorEstado; ?>; border-radius: 10px; background-color: <?php echo $aprobado ? '#eafaf1' : '#fdedec'; ?>; }
        .status-big { font-size: 40px; font-weight: bold; color: <?php echo $colorEstado; ?>; letter-spacing: 2px; }
        
        .obs-box { margin-top: 30px; }
        .obs-title { background: #333; color: white; padding: 10px; font-weight: bold; }
        .obs-content { border: 1px solid #333; padding: 20px; min-height: 100px; font-size: 14px; line-height: 1.5; white-space: pre-wrap; }

        .footer { margin-top: 80px; display: flex; justify-content: space-between; text-align: center; }
        .firmas { border-top: 1px solid #333; width: 40%; padding-top: 10px; font-size: 14px; }
        
        .page-break { page-break-before: always; margin-top: 50px; }
        
        /* ESTILOS PARA LAS TABLAS DE ERRORES */
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9rem; }
        .detail-table th { background: #333; color: white; padding: 10px; text-align: left; }
        .detail-table td { border-bottom: 1px solid #ddd; padding: 8px; vertical-align: top; }
        .score-bad { color: #c0392b; font-weight: bold; text-align: center; }
        
        .error-item { margin-bottom: 10px; padding: 10px; border-bottom: 1px solid #eee; color: #c0392b; display: flex; align-items: flex-start; }
        .error-item i { margin-right: 10px; margin-top: 4px; }
        .error-section-title { border-bottom: 2px solid #333; color: #333; margin-top: 40px; padding-bottom: 5px; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .result-section { -webkit-print-color-adjust: exact; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom:20px;">
        <button onclick="window.print()" style="padding:10px 20px; font-weight:bold; cursor:pointer;">IMPRIMIR INFORME</button>
    </div>

    <div class="header">
        <img src="../../assets/img/logo.png" alt="Logo" class="logo">
        <h1>Informe de Resultados</h1>
        <h2><?php echo htmlspecialchars($data['tipo_evento']); ?></h2>
    </div>

    <div class="info-box">
        <div class="row">
            <div><span class="label">Participante:</span> <?php echo htmlspecialchars($data['nombre_completo']); ?></div>
            <div><span class="label">Cédula:</span> <?php echo htmlspecialchars($data['cedula']); ?></div>
        </div>
        <div class="row">
            <div><span class="label">Empresa:</span> <?php echo htmlspecialchars($data['nombre_empresa']); ?></div>
            <div><span class="label">Fecha:</span> <?php echo date('d/m/Y', strtotime($data['fecha_creacion'])); ?></div>
        </div>
        <div style="margin-top:10px;">
            <span class="label">Curso:</span> <?php echo htmlspecialchars($data['nombre_curso']); ?>
            <?php if(!empty($data['tipos_equipos'])): ?>
                <br><span class="label" style="font-size:0.85rem; color:#666;">Equipos: <?php echo str_replace(" | ", ", ", $data['tipos_equipos']); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
        <thead>
            <tr style="background:#eee;">
                <th style="padding:10px; border:1px solid #ccc;">Módulo</th>
                <th style="padding:10px; border:1px solid #ccc;">Puntaje</th>
                <th style="padding:10px; border:1px solid #ccc;">Mínimo</th>
                <th style="padding:10px; border:1px solid #ccc;">Estado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:10px; border:1px solid #ccc; text-align:center;">Teórico</td>
                <td style="padding:10px; border:1px solid #ccc; text-align:center;"><strong><?php echo $notaT; ?>/100</strong></td>
                <td style="padding:10px; border:1px solid #ccc; text-align:center;">80/100</td>
                <td style="padding:10px; border:1px solid #ccc; text-align:center; color:<?php echo $teoriaOk?'green':'red';?>"><?php echo $teoriaOk?'APROBADO':'REPROBADO';?></td>
            </tr>
            <tr>
                <td style="padding:10px; border:1px solid #ccc; text-align:center;">Práctico</td>
                <td style="padding:10px; border:1px solid #ccc; text-align:center;"><strong><?php echo $notaP; ?>/100</strong></td>
                <td style="padding:10px; border:1px solid #ccc; text-align:center;">80/100</td>
                <td style="padding:10px; border:1px solid #ccc; text-align:center; color:<?php echo $practicaOk?'green':'red';?>"><?php echo $practicaOk?'APROBADO':'REPROBADO';?></td>
            </tr>
        </tbody>
    </table>

    <div class="result-section">
        <div>RESULTADO FINAL</div>
        <div class="status-big"><?php echo $estadoTexto; ?></div>
    </div>

    <div class="obs-box">
        <div class="obs-title">OBSERVACIONES</div>
        <div class="obs-content">
            <?php echo nl2br(htmlspecialchars($data['observaciones'] ?? 'Sin observaciones.')); ?>
        </div>
    </div>

    <div class="footer">
        <div class="firmas"><br><br>__________________________<br><strong>Instructor Evaluador</strong></div>
        <div class="firmas"><br><br>__________________________<br><strong>Coordinación Académica</strong></div>
    </div>

    <?php if (!$aprobado || !empty($erroresTeoricos) || !empty($erroresPracticos)): ?>
    
    <div class="page-break"></div>
    <div class="header">
        <h1>Anexo: Justificación de Resultados</h1>
        <h2>Detalle de Deficiencias Detectadas</h2>
    </div>

        <h3 style="border-bottom:2px solid #333; color:#333;">1. Deficiencias en Evaluación Práctica</h3>
    <?php if (!empty($erroresPracticos)): ?>
        <table class="detail-table">
            <thead><tr><th style="width:75%;">Ítem Evaluado</th><th style="width:25%; text-align:center;">Nota</th></tr></thead>
            <tbody>
                <?php foreach ($erroresPracticos as $err): ?>
                <tr>
                    <td><?php echo htmlspecialchars($err['item']); ?></td>
                    <td class="score-bad"><?php echo $err['nota']; ?> / 5</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="font-style:italic; color:#666;">No se registraron fallas críticas en el checklist práctico.</p>
    <?php endif; ?>

    <?php if (!empty($erroresTeoricos)): ?>
        <h3 class="error-section-title">2. Deficiencias en Evaluación Teórica</h3>
        <div style="margin-bottom:15px;">
            El participante obtuvo <strong><?php echo $notaT; ?>/100</strong>. Respondió incorrectamente a las siguientes preguntas:
        </div>
        <div style="border:1px solid #ddd; border-radius:5px; padding:15px; background:#fff5f5;">
            <?php foreach ($erroresTeoricos as $txtPregunta): ?>
                <div class="error-item">
                    <i class="fas fa-times-circle"></i>
                    <div><?php echo htmlspecialchars($txtPregunta); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($notaT < 100 && empty($erroresPracticos)): ?>
         <h3 class="error-section-title">2. Deficiencias en Evaluación Teórica</h3>
         <p style="font-style:italic; color:#777;">* No hay detalle de preguntas disponible para este intento.</p>
    <?php endif; ?>

    <?php endif; ?>

</body>
</html>