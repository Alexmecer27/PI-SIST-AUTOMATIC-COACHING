<?php
// systems/admin/certificado_print.php
session_start();
// FORZAR CODIFICACIÓN UTF-8 DESDE EL SERVIDOR
header('Content-Type: text/html; charset=UTF-8');

require '../../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    die("Acceso denegado.");
}

$cedula = $_GET['cedula'] ?? '';
$codigo = $_GET['codigo'] ?? '';

if (empty($cedula) || empty($codigo)) die("Faltan datos.");

try {
    // Aseguramos que la conexión a la BD también sea UTF-8 (por si acaso)
    $pdo->exec("SET NAMES 'utf8'");

    $sql = "SELECT 
                p.nombre_completo, 
                p.cedula, 
                p.foto_perfil,
                e.nombre_empresa, 
                ex.titulo AS nombre_curso, 
                i.fecha_inicio,
                s.codigo_acceso,
                MAX(pr.tipos_equipos) as tipos_equipos
            FROM intentos i
            JOIN sesiones s ON i.id_sesion = s.id_sesion
            JOIN examenes ex ON s.id_examen = ex.id_examen
            JOIN perfil_participante p ON i.id_usuario = p.id_usuario
            JOIN empresas e ON p.id_empresa = e.id_empresa
            LEFT JOIN practica_resultados pr ON i.id_usuario = pr.id_usuario AND i.id_sesion = pr.id_sesion
            WHERE p.cedula = ? AND s.codigo_acceso = ? 
            GROUP BY i.id_intento
            ORDER BY i.fecha_inicio DESC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cedula, $codigo]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) die("Certificado no encontrado.");

    // Fechas
    $fechaObj = DateTime::createFromFormat('Y-m-d H:i:s', $data['fecha_inicio']);
    $dia = $fechaObj->format('d');
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $mes = $meses[(int)$fechaObj->format('m')];
    $anio = $fechaObj->format('Y');
    $fechaTexto = "$dia de $mes de $anio";

    $fechaExpObj = clone $fechaObj;
    $fechaExpObj->modify('+3 years');
    $fechaExpiracion = $fechaExpObj->format('d/m/Y');

} catch (Exception $e) { die("Error DB: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado - <?php echo htmlspecialchars($data['nombre_completo']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        @page { size: A4 landscape; margin: 0; }
        
        body { 
            margin: 0; padding: 0; 
            font-family: 'Arimo', sans-serif, Arial;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        .hoja-a4 {
            width: 297mm;
            height: 210mm;
            position: relative;
            padding: 15mm 20mm;
            box-sizing: border-box;
            
            /* IMAGEN DE FONDO */
            background-image: url('../admin/img/certificado_1.png'); 
            
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
            z-index: 1;
        }

        /* --- ESTILOS IGUALES A LOS ANTERIORES --- */
        .codigo-top { position: absolute; top: 15mm; left: 20mm; font-weight: bold; font-size: 14pt; color: #E67E22; font-style: italic; z-index: 2; }
        
        .foto-container { text-align: center; margin-bottom: 5mm; margin-top: 5mm; position: relative; z-index: 2; }
        .foto-redonda { width: 35mm; height: 35mm; border-radius: 50%; object-fit: cover; border: 3px solid #E67E22; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: white; }

        .contenido-central { text-align: center; margin-top: 5mm; position: relative; z-index: 2; }
        
        .nombre-alumno { font-size: 24pt; font-weight: 700; margin-bottom: 5px; color: #000; }
        .cedula-alumno { font-size: 16pt; font-weight: 700; margin-bottom: 15px; }
        .empresa-linea { font-size: 14pt; margin-bottom: 5px; }
        .accion-linea { font-size: 14pt; margin-bottom: 10px; }
        
        .titulo-curso { 
            font-size: 26pt; font-weight: 800; text-transform: uppercase; 
            margin-bottom: 15px; letter-spacing: 1px; color: #000; 
        }

        .descripcion-parrafo { font-size: 11pt; max-width: 80%; margin: 0 auto 20px auto; line-height: 1.4; }
        .fecha-linea { font-size: 12pt; margin-bottom: 5px; }
        .detalles-linea { font-size: 12pt; margin-bottom: 30px; }

        .footer-grid { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20mm; padding: 0 10mm; position: relative; z-index: 2; }
        .footer-left { text-align: left; font-weight: bold; font-size: 11pt; }
        .footer-right { text-align: right; font-size: 10pt; max-width: 50%; }
        .autorizado-titulo { font-weight: bold; font-style: italic; font-size: 11pt; margin-bottom: 2px; }
        .autorizado-lista { font-weight: normal; font-style: italic; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="position:fixed; top:10px; right:10px; z-index:1000;">
        <button onclick="window.print()" style="padding:10px 20px; font-weight:bold; cursor:pointer; background:#27ae60; color:white; border:none; border-radius:5px;">IMPRIMIR CERTIFICADO</button>
    </div>

    <div class="hoja-a4">
        
        <div class="codigo-top">
            <?php echo htmlspecialchars($data['codigo_acceso']); ?>
        </div>

        <div class="foto-container">
            <?php if (!empty($data['foto_perfil'])): ?>
                <img src="../../assets/uploads/<?php echo htmlspecialchars($data['foto_perfil']); ?>" class="foto-redonda">
            <?php else: ?>
                <div style="height:35mm;"></div> 
            <?php endif; ?>
        </div>

        <div class="contenido-central">
            
            <div class="nombre-alumno">
                <?php echo htmlspecialchars($data['nombre_completo']); ?>
            </div>

            <div class="cedula-alumno">
                C.C. <?php echo htmlspecialchars($data['cedula']); ?>
            </div>

            <div class="empresa-linea">
                De la empresa <strong><?php echo htmlspecialchars($data['nombre_empresa']); ?></strong>
            </div>

            <div class="accion-linea">
                Asisti&oacute; y aprob&oacute; al curso de:
            </div>

            <div class="titulo-curso">
                <?php echo htmlspecialchars($data['nombre_curso']); ?>
            </div>

            <div class="descripcion-parrafo">
                Abarcando temas de: Normas de Seguridad OSHA 1910.178, ANSI/ITSDF B56.1, inspecci&oacute;n de equipos montacargas y t&eacute;cnicas para prevenir accidentes.
            </div>

            <div class="fecha-linea">
                Impartido el d&iacute;a <strong><?php echo $fechaTexto; ?></strong>
            </div>

            <div class="detalles-linea">
                Con una intensidad horaria de <strong>8 horas</strong> &nbsp;&nbsp;&nbsp;&nbsp; Modalidad: <strong>Te&oacute;rico - Pr&aacute;ctico</strong>
            </div>

        </div>

        <div class="footer-grid">
            <div class="footer-left">
                Fecha de Expiraci&oacute;n:<br>
                <?php echo $fechaExpiracion; ?>
            </div>

            <div class="footer-right">
                <div class="autorizado-titulo">Autorizado para Montacargas:</div>
                <div class="autorizado-lista">
                    <?php 
                    $equipos = !empty($data['tipos_equipos']) ? $data['tipos_equipos'] : "Clase I, IV y V";
                    echo str_replace(" | ", ", ", htmlspecialchars($equipos));
                    ?>
                </div>
            </div>
        </div>

    </div>

</body>
</html>