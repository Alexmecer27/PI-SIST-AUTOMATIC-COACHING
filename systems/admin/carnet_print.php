<?php
// systems/admin/carnet_print.php
session_start();
require '../../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    die("Acceso denegado.");
}

$cedula = $_GET['cedula'] ?? '';
$codigo = $_GET['codigo'] ?? '';

if (empty($cedula) || empty($codigo)) die("Faltan datos.");

try {
    $sql = "SELECT 
                p.nombre_completo, 
                p.cedula, 
                p.foto_perfil,
                e.nombre_empresa, 
                ex.titulo AS nombre_curso, 
                i.fecha_inicio,
                s.codigo_acceso
            FROM intentos i
            JOIN sesiones s ON i.id_sesion = s.id_sesion
            JOIN examenes ex ON s.id_examen = ex.id_examen
            JOIN perfil_participante p ON i.id_usuario = p.id_usuario
            JOIN empresas e ON p.id_empresa = e.id_empresa
            WHERE p.cedula = ? AND s.codigo_acceso = ? 
            GROUP BY i.id_intento
            ORDER BY i.fecha_inicio DESC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cedula, $codigo]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) die("Datos no encontrados.");

    // Fechas (Vigencia 3 años)
    $fechaInicioObj = new DateTime($data['fecha_inicio']);
    $fechaExpObj = clone $fechaInicioObj;
    $fechaExpObj->modify('+3 years');

    $fechaExpedicion = $fechaInicioObj->format('d/m/Y');
    $fechaExpiracion = $fechaExpObj->format('d/m/Y');

} catch (Exception $e) { die("Error DB: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carnet - <?php echo htmlspecialchars($data['nombre_completo']); ?></title>
    <style>
        @page { margin: 0; }
        body { 
            margin: 0; padding: 20px; 
            font-family: Arial, Helvetica, sans-serif; 
            background: #eee;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        /* --- CONTENEDOR GENERAL --- */
        .carnet-card {
            width: 85.6mm;
            height: 54mm;
            position: relative;
            background-size: 100% 100%; 
            background-repeat: no-repeat;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        /* --- FRENTE (carnet_1.jpg) --- */
        .front {
            background-image: url('../admin/img/carnet_1.png'); 
        }

        /* 1. CÓDIGO (MOVIDO MÁS A LA IZQUIERDA) */
        .codigo-top-right {
            position: absolute;
            top: 7mm;
            right: 15mm;   /* AUMENTADO: Antes 12mm -> Ahora 22mm (Más a la izquierda) */
            font-size: 8pt;
            font-weight: bold;
            color: #E67E22; 
            font-style: italic;
            text-align: right;
            z-index: 10;
        }

        /* 2. FOTO */
        .foto-participante {
            position: absolute;
            top: 14mm;   
            left: 3mm;   
            width: 22mm; 
            height: 28mm;
            border: 2px solid #E67E22; 
            border-radius: 4px;
            object-fit: cover;
            background: white;
        }

        /* 3. DATOS DE TEXTO (MOVIDO MÁS ABAJO) */
        .datos-front {
            position: absolute;
            top: 15mm;      /* AUMENTADO: Antes 12mm -> Ahora 18mm (Más abajo) */
            left: 28mm;     
            width: 56mm;    
            text-align: left;
        }

        .curso-titulo {
            font-size: 7pt;
            font-weight: bold;
            color: #E67E22; 
            text-transform: uppercase;
            line-height: 1;
            margin-bottom: 5px;
            white-space: normal; 
        }

        .fila-dato {
            font-size: 7pt;
            margin-bottom: 2px;
            display: flex; 
            align-items: flex-start; 
        }

        .label {
            font-weight: bold;
            color: #E67E22;
            min-width: 45px; 
            margin-right: 2px;
            flex-shrink: 0; 
        }

        .valor {
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            white-space: normal; 
            word-wrap: break-word;
            line-height: 1.1;
        }

        /* --- REVERSO (carnet_2.jpg) --- */
        .back {
            background-image: url('../admin/img/carnet_2.png'); 
        }

        .titulo-seguridad {
            position: absolute;
            top: 8mm;
            left: 2mm;
            width: auto;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
            color: black;
            text-transform: uppercase;
        }

        .checklist-container {
            position: absolute;
            top: 12mm;
            left: 2mm; 
            right: 5mm;
            text-align: left; 
        }

        .checklist-item {
            font-size: 7pt;
            margin-bottom: 2px;
            color: #333;
            display: flex;
            align-items: flex-start;
        }

        .check-icon {
            color: #000;
            font-weight: bold;
            margin-right: 4px;
        }

        /* IMPRESIÓN */
        @media print {
            body { background: white; padding: 0; display: block; }
            .no-print { display: none; }
            .carnet-card { 
                box-shadow: none; 
                margin-bottom: 0; 
                page-break-inside: avoid;
                float: left; 
                margin-right: 5mm;
                margin-bottom: 5mm;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:right; margin-bottom:10px; width: 180mm;">
        <button onclick="window.print()" style="padding:10px 20px; font-weight:bold; cursor:pointer; background:#E67E22; color:white; border:none; border-radius:5px;">IMPRIMIR CARNETS</button>
    </div>

    <div class="carnet-card front">
        
        <div class="codigo-top-right">
            <?php echo htmlspecialchars($data['codigo_acceso']); ?>
        </div>

        <?php if (!empty($data['foto_perfil'])): ?>
            <img src="../../assets/uploads/<?php echo htmlspecialchars($data['foto_perfil']); ?>" class="foto-participante">
        <?php endif; ?>

        <div class="datos-front">
            <div class="curso-titulo">
                <?php echo htmlspecialchars($data['nombre_curso']); ?>
            </div>

            <div class="fila-dato">
                <span class="label">EMPRESA:</span>
                <span class="valor"><?php echo htmlspecialchars(mb_strimwidth($data['nombre_empresa'], 0, 40, "...")); ?></span>
            </div>
            
            <div class="fila-dato">
                <span class="label">NOMBRE:</span>
                <span class="valor"><?php echo htmlspecialchars($data['nombre_completo']); ?></span>
            </div>

            <div class="fila-dato">
                <span class="label">CEDULA:</span>
                <span class="valor"><?php echo htmlspecialchars($data['cedula']); ?></span>
            </div>

            <div class="fila-dato">
                <span class="label">F. EXPED:</span>
                <span class="valor"><?php echo $fechaExpedicion; ?></span>
            </div>

            <div class="fila-dato">
                <span class="label">F. VENCE:</span>
                <span class="valor"><?php echo $fechaExpiracion; ?></span>
            </div>
        </div>
    </div>

    <div class="carnet-card back">
        
        <div class="titulo-seguridad">ANTES DE PONER EL EQUIPO EN MARCHA</div>

        <div class="checklist-container">
            <div class="checklist-item"><span class="check-icon">✓</span> Revise el sistema de levantamiento</div>
            <div class="checklist-item"><span class="check-icon">✓</span> Revise luces, pito, cinturón de seguridad.</div>
            <div class="checklist-item"><span class="check-icon">✓</span> Revise los frenos de pedal y emergencia</div>
            <div class="checklist-item"><span class="check-icon">✓</span> Verifique el estado del sistema de dirección</div>
            <div class="checklist-item"><span class="check-icon">✓</span> Verifique los controles de mando</div>
            <div class="checklist-item"><span class="check-icon">✓</span> Pregúntese ¿Está usted preparado? ¿Seguro?</div>
        </div>

    </div>

</body>
</html>