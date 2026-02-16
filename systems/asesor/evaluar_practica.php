<?php
// systems/asesor/evaluar_practica.php
session_start();
require '../../config/db.php'; 

if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../../views/login.php"); exit;
}

$cedula = $_GET['cedula'] ?? '';
$codigo = $_GET['codigo'] ?? '';
$tipo = $_GET['tipo'] ?? '';

// OBTENER NOMBRE DEL TIPO ACTUAL PARA EL PHP (Para ocultarlo en los checkboxes)
$nombre_tipo_actual = "Evaluación Actual"; 
try {
    if($tipo) {
// CORRECCIÓN: Usar 'practica_tipos'
$stmtName = $pdo->prepare("SELECT nombre_certificacion FROM practica_tipos WHERE id_tipo_practica = ?");
$stmtName->execute([$tipo]);
        $resName = $stmtName->fetchColumn();
        if($resName) $nombre_tipo_actual = $resName;
    }
} catch(Exception $e) { /* Ignorar error */ }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluar Práctica | Coaching Lift</title>
    <link rel="icon" type="image/png" href="../../assets/img/lojo.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- ESTILOS CORPORATIVOS --- */
        :root { 
            --primary: #2C3E50; 
            --accent: #F3C623; 
            --accent-dark: #FA812F; 
            --bg: #FEF3E2; 
            --white: #fff; 
            --green: #27ae60; 
            --red: #c0392b; 
        }
        
        body { background-color: var(--bg); font-family: 'Poppins', sans-serif; margin: 0; padding-bottom: 50px; color: #333; }
        
        /* NAVBAR */
        .navbar { background: var(--white); padding: 15px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--accent-dark); position: sticky; top: 0; z-index: 100; }
        .navbar img { height: 40px; }
        .btn-back { color: var(--primary); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { color: var(--accent-dark); }

        .container { max-width: 900px; margin: 30px auto; padding: 0 15px; }

        /* PERFIL ESTUDIANTE */
        .profile-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 25px;
            border-left: 6px solid var(--accent);
            animation: fadeIn 0.5s ease-out;
        }
        .profile-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent-dark); }
        .profile-info h2 { margin: 0; color: var(--primary); font-size: 1.3rem; }
        .profile-info p { margin: 5px 0 0 0; color: #666; font-size: 0.9rem; }

        /* FORMULARIO CHECKLIST */
        .checklist-container { background: var(--white); border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .checklist-header { background: var(--primary); color: white; padding: 15px 20px; display: flex; justify-content: space-between; font-weight: 600; align-items: center; }
        
        .item-row { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; flex-direction: column; gap: 10px; transition: 0.2s; }
        .item-row:hover { background: #fafafa; }
        .item-text { font-size: 0.95rem; font-weight: 500; line-height: 1.4; flex: 1; color: #444; }
        
        /* ESCALA 0-5 */
        .rating-group { display: flex; gap: 5px; justify-content: flex-end; flex-wrap: wrap; margin-top: 5px; }
        .rating-label { 
            display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; 
            width: 40px; height: 50px; border-radius: 8px; border: 1px solid #ddd; transition: 0.2s; background: #fff;
        }
        .rating-label span { font-size: 1rem; font-weight: bold; color: #777; }
        .rating-label small { font-size: 0.6rem; color: #aaa; margin-top: -2px; text-transform: uppercase; }
        .rating-label input { display: none; }
        
        /* INTERACCIÓN RADIOS */
        .rating-label:hover { background: #f0f0f0; border-color: #bbb; transform: translateY(-2px); }
        .rating-label:has(input:checked) { background: var(--accent-dark); border-color: var(--accent-dark); color: white; transform: scale(1.1); box-shadow: 0 4px 10px rgba(250, 129, 47, 0.3); z-index: 2; }
        .rating-label:has(input:checked) span, .rating-label:has(input:checked) small { color: white; }

        /* OBSERVACIONES */
        .form-obs { padding: 25px; background: #f9f9f9; border-top: 1px solid #eee; }
        .form-obs label { font-weight: bold; color: var(--primary); display: block; margin-bottom: 10px; }
        .form-obs textarea { width: 100%; padding: 15px; border: 1px solid #ccc; border-radius: 10px; font-family: inherit; resize: vertical; outline: none; transition: 0.3s; }
        .form-obs textarea:focus { border-color: var(--accent-dark); background: white; box-shadow: 0 0 0 3px rgba(250, 129, 47, 0.1); }

        .btn-preview { 
            width: 100%; padding: 18px; background: var(--primary); color: white; border: none; 
            font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s;
            display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-preview:hover { background: var(--secondary); letter-spacing: 1px; }

        /* MODAL PREVISUALIZACIÓN */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 3000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-box { background: white; width: 90%; max-width: 480px; border-radius: 15px; overflow: hidden; animation: slideUp 0.3s; box-shadow: 0 25px 50px rgba(0,0,0,0.5); border-top: 6px solid var(--accent-dark); }
        .modal-body { padding: 30px; text-align: center; }
        
        .preview-score { font-size: 4rem; font-weight: 800; color: var(--accent-dark); line-height: 1; margin: 10px 0; }
        .preview-status { font-size: 1.4rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; }
        .status-aprobado { color: var(--green); }
        .status-reprobado { color: var(--red); }
        
        .preview-obs { background: #f1f2f6; padding: 15px; border-radius: 8px; text-align: left; font-size: 0.9rem; color: #555; border-left: 4px solid var(--accent); margin-bottom: 25px; max-height: 120px; overflow-y: auto; }

        .modal-actions { display: flex; gap: 10px; }
        .btn-modal { flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: 0.2s; }
        .btn-edit { background: #e0e0e0; color: #333; }
        .btn-edit:hover { background: #dcdcdc; }
        .btn-confirm { background: var(--green); color: white; }
        .btn-confirm:hover { background: #219150; }

        /* ALERTAS PEQUEÑAS */
        .alert-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 4000; display: none; align-items: center; justify-content: center; }
        .alert-box { background: white; padding: 25px; border-radius: 12px; width: 85%; max-width: 350px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .alert-btn { background: var(--primary); color: white; padding: 8px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        @media (min-width: 768px) {
            .item-row { flex-direction: row; align-items: center; justify-content: space-between; }
            .rating-group { width: auto; margin-top: 0; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="panel.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        <img src="../../assets/img/logo.png" alt="Logo">
    </nav>

    <div class="container">
        
        <div id="loading" style="text-align:center; padding:60px;">
            <i class="fas fa-spinner fa-spin" style="font-size:3rem; color:var(--accent-dark);"></i>
            <p style="margin-top:15px; color:#777;">Cargando evaluación...</p>
        </div>

        <div id="profileArea" class="profile-card" style="display:none;">
            <img id="imgPerfil" src="" alt="Foto" class="profile-img">
            <div class="profile-info">
                <h2 id="txtNombre"></h2>
                <p><i class="fas fa-building"></i> <span id="txtEmpresa"></span></p>
                <p><i class="far fa-id-card"></i> <?php echo htmlspecialchars($cedula); ?></p>
            </div>
        </div>

        <div id="evaluationArea" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 id="tituloCertificacion" style="margin:0; color:var(--primary); font-size:1.1rem; text-transform:uppercase;">...</h3>
                <span id="modeLabel" style="background:#e0e0e0; padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:bold; color:#555;">NUEVA EVALUACIÓN</span>
            </div>

            <form id="formPractica">
                <div class="checklist-container">
                    <div class="checklist-header">
                        <span><i class="fas fa-list-check"></i> Ítems a Evaluar</span>
                        <span style="font-size:0.8rem; font-weight:normal; opacity:0.8;">0 (Malo) a 5 (Excelente)</span>
                    </div>
                    
                    <div id="listaItems"></div>

                    <div class="form-obs">
                        <label>Observaciones Finales:</label>
                        <textarea id="txtObservacion" rows="3" placeholder="Comentarios obligatorios si reprueba, u opcionales..."></textarea>
                    </div>

                    <div style="padding: 20px;">
                        <div class="card" style="margin-top: 20px; border-left: 5px solid #8e44ad; background-color: #f4ecf7; padding: 15px; border-radius: 8px;">
                            <h3 style="color: #8e44ad; margin-top:0; font-size: 1.1rem;"><i class="fas fa-plus-circle"></i> Certificación Multi-Equipo (Opcional)</h3>
                            <p style="font-size:0.9rem; color:#555;">
                                El participante ya está siendo evaluado para: <strong><?php echo htmlspecialchars($nombre_tipo_actual); ?></strong>.
                                <br>Si validó su experiencia en otros equipos, márquelos aquí para incluirlos en el certificado:
                            </p>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; margin-top:15px;">
                                <?php
                                $todosLosTipos = [
                                    'Clase I, IV y V (Montacargas Contrapesado)',
                                    'Clase II (Pasillo Angosto / Eléctrico)',
                                    'Clase III (Transpaletas Eléctricas / Manuales)'
                                ];

                                foreach ($todosLosTipos as $t) {
                                    // Comparamos para no mostrar el actual
                                    if (stripos($nombre_tipo_actual, $t) === false && $t != $nombre_tipo_actual) {
                                        echo "
                                        <label style='display:flex; align-items:center; gap:10px; background:white; padding:10px; border-radius:6px; cursor:pointer; border:1px solid #ddd;'>
                                            <input type='checkbox' name='equipos_extra[]' value='$t' style='transform:scale(1.2);'>
                                            <span style='font-weight:600; color:#333; font-size:0.9rem;'>$t</span>
                                        </label>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-preview" onclick="previsualizar()">
                        <i class="fas fa-eye"></i> PREVISUALIZAR RESULTADO
                    </button>
                </div>
            </form>
        </div>

    </div>

    <div id="modalPreview" class="modal-overlay">
        <div class="modal-box">
            <div style="padding:15px; background:#f8f9fa; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:bold; color:var(--primary);">Confirmar Resultado</span>
                <span onclick="cerrarPreview()" style="cursor:pointer; font-size:1.5rem; color:#999;">&times;</span>
            </div>
            <div class="modal-body">
                <p style="color:#666; margin:0;">Nota Final Calculada:</p>
                
                <div class="preview-score">
                    <span id="prevNota">0</span><small style="font-size:1.5rem; color:#ccc; font-weight:400;">/100</small>
                </div>
                
                <div id="prevStatus" class="preview-status"></div>

                <div style="text-align:left; font-size:0.85rem; font-weight:bold; color:var(--primary); margin-bottom:5px;">Observaciones:</div>
                <div id="prevObs" class="preview-obs"></div>

                <div class="modal-actions">
                    <button class="btn-modal btn-edit" onclick="cerrarPreview()">
                        <i class="fas fa-pen"></i> Corregir
                    </button>
                    <button id="btnConfirmarSave" class="btn-modal btn-confirm" onclick="guardarDefinitivo()">
                        <i class="fas fa-save"></i> Guardar Definitivo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="webAlert" class="alert-overlay">
        <div class="alert-box">
            <div id="alertIcon" style="font-size:2.5rem; margin-bottom:10px;"></div>
            <h3 id="alertTitle" style="margin:0; color:var(--primary);"></h3>
            <p id="alertMessage" style="color:#666; font-size:0.9rem; margin:10px 0;"></p>
            <button id="alertBtnAction" class="alert-btn" onclick="cerrarAlerta()">OK</button>
        </div>
    </div>

    <script>
        const ID_TIPO = "<?php echo $tipo; ?>";
        const CEDULA = "<?php echo $cedula; ?>";
        const CODIGO = "<?php echo $codigo; ?>";
        
        let notaCalculada = 0;
        let respuestasTemp = {};

        document.addEventListener('DOMContentLoaded', () => {
            if(!ID_TIPO || !CEDULA || !CODIGO) { 
                mostrarAlerta("Error", "Faltan datos. Regresa al panel.", "error", true); 
                return; 
            }

            fetch(`../../controllers/asesor_get_checklist.php?id_tipo=${ID_TIPO}&cedula=${CEDULA}&codigo=${CODIGO}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';

                if (data.status === 'success') {
                    // 1. Mostrar Formulario
                    document.getElementById('evaluationArea').style.display = 'block';
                    document.getElementById('tituloCertificacion').innerText = data.titulo;
                    
                    // 2. Llenar Perfil
                    if(data.perfil) {
                        document.getElementById('profileArea').style.display = 'flex';
                        document.getElementById('txtNombre').innerText = data.perfil.nombre;
                        document.getElementById('txtEmpresa').innerText = data.perfil.empresa;
                        document.getElementById('imgPerfil').src = `../../assets/uploads/${data.perfil.foto}`;
                    }

                    // 3. Renderizar Items
                    renderizarItems(data.preguntas);

                    // 4. DETECTAR SI ES EDICIÓN
                    if (data.respuestas_guardadas) {
                        document.getElementById('modeLabel').innerText = "MODO EDICIÓN (CORRECCIÓN)";
                        document.getElementById('modeLabel').style.background = "#fff3cd"; 
                        document.getElementById('modeLabel').style.color = "#856404";

                        for (const [idPregunta, valor] of Object.entries(data.respuestas_guardadas)) {
                            const radio = document.querySelector(`input[name="p_${idPregunta}"][value="${valor}"]`);
                            if (radio) radio.checked = true;
                        }
                        document.getElementById('txtObservacion').value = data.observacion_guardada || '';
                        
                        mostrarAlerta("Modo Edición", "Se han cargado las respuestas previas para que puedas corregirlas.", "info");
                    }

                } else {
                    mostrarAlerta("Error", data.mensaje, "error", true);
                }
            })
            .catch(err => {
                console.error(err);
                mostrarAlerta("Error", "No se pudo conectar con el servidor.", "error", true);
            });
        });

        function renderizarItems(items) {
            const container = document.getElementById('listaItems');
            let html = '';
            items.forEach(item => {
                html += `
                <div class="item-row">
                    <div class="item-text">${item.texto_item}</div>
                    <div class="rating-group">
                        ${generarRadios(item.id_pregunta_practica)}
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }

        function generarRadios(id) {
            let radios = '';
            for (let i = 0; i <= 5; i++) {
                radios += `
                <label class="rating-label">
                    <input type="radio" name="p_${id}" value="${i}">
                    <span>${i}</span>
                    <small>${i===0?'Malo':(i===5?'Exc.':'')}</small>
                </label>`;
            }
            return radios;
        }

        function previsualizar() {
            const radios = document.querySelectorAll('input[type="radio"]:checked');
            const totalItems = document.querySelectorAll('.item-row').length;
            
            if (radios.length < totalItems) {
                mostrarAlerta("Incompleto", `Faltan responder ${totalItems - radios.length} ítems.`, "error");
                return;
            }

            respuestasTemp = {};
            let suma = 0;

            radios.forEach(r => {
                const val = parseInt(r.value);
                respuestasTemp[r.name.replace('p_', '')] = val;
                suma += val;
            });

            const promedio = suma / totalItems; 
            notaCalculada = Math.round((promedio / 5) * 100);

            document.getElementById('prevNota').innerText = notaCalculada;
            
            const statusDiv = document.getElementById('prevStatus');
            if (notaCalculada >= 80) {
                statusDiv.innerText = "APROBADO";
                statusDiv.className = "preview-status status-aprobado";
                statusDiv.innerHTML += ' <i class="fas fa-check-circle"></i>';
            } else {
                statusDiv.innerText = "NO APROBADO";
                statusDiv.className = "preview-status status-reprobado";
                statusDiv.innerHTML += ' <i class="fas fa-times-circle"></i>';
            }

            const obs = document.getElementById('txtObservacion').value.trim();
            document.getElementById('prevObs').innerText = obs || "(Sin observaciones)";

            document.getElementById('modalPreview').style.display = 'flex';
        }

        function cerrarPreview() { document.getElementById('modalPreview').style.display = 'none'; }

        function guardarDefinitivo() {
            const btn = document.getElementById('btnConfirmarSave');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            btn.disabled = true;

            const obs = document.getElementById('txtObservacion').value;

            // --- RECOLECTAR CHECKBOXES DE EQUIPOS EXTRA ---
            const equiposExtra = [];
            document.querySelectorAll('input[name="equipos_extra[]"]:checked').forEach(chk => {
                equiposExtra.push(chk.value);
            });

            fetch('../../controllers/asesor_guardar_practica.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cedula: CEDULA,
                    codigo_sesion: CODIGO,
                    id_tipo: ID_TIPO,
                    respuestas: respuestasTemp,
                    observacion: obs,
                    equipos_extra: equiposExtra // <-- Enviamos los extras aquí
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    cerrarPreview();
                    mostrarAlerta("¡Guardado!", "La evaluación se ha registrado correctamente.", "success", true);
                } else {
                    mostrarAlerta("Error", data.mensaje, "error");
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Guardar Definitivo';
                }
            })
            .catch(err => {
                mostrarAlerta("Error de Red", "Intenta de nuevo.", "error");
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Guardar Definitivo';
            });
        }

        function mostrarAlerta(t, m, tipo, redirigir = false) {
            const modal = document.getElementById('webAlert');
            const icon = document.getElementById('alertIcon');
            const btn = document.getElementById('alertBtnAction');

            document.getElementById('alertTitle').innerText = t;
            document.getElementById('alertMessage').innerText = m;

            if(tipo === 'error') {
                icon.innerHTML = '<i class="fas fa-times-circle" style="color:var(--red)"></i>';
            } else if(tipo === 'info') {
                icon.innerHTML = '<i class="fas fa-info-circle" style="color:var(--accent)"></i>';
            } else {
                icon.innerHTML = '<i class="fas fa-check-circle" style="color:var(--green)"></i>';
            }

            btn.onclick = redirigir ? function() { window.location.href = 'panel.php'; } : cerrarAlerta;
            modal.style.display = 'flex';
        }

        function cerrarAlerta() { document.getElementById('webAlert').style.display = 'none'; }
    </script>
</body>
</html>