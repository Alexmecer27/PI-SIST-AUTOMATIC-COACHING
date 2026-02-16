<?php
// systems/asesor/panel.php

// 1. Configuración de Sesión
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();
header('Content-Type: text/html; charset=utf-8');
require '../../config/db.php';

// 2. Seguridad
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../../views/login.php");
    exit;
}

// 3. Cargas Iniciales
try {
    // Exámenes activos
    $stmtExamenes = $pdo->query("SELECT id_examen, titulo FROM examenes WHERE estado = 'Activo'");
    $examenes = $stmtExamenes->fetchAll(PDO::FETCH_ASSOC);

    // Códigos (Míos + Compartidos)
    $sqlCodigos = "SELECT DISTINCT s.id_sesion, s.codigo_acceso, s.fecha_creacion, s.id_asesor 
                   FROM sesiones s
                   JOIN sesion_asesores sa ON s.id_sesion = sa.id_sesion
                   WHERE sa.id_asesor = ?
                   ORDER BY s.fecha_creacion DESC";
    $stmtCodigos = $pdo->prepare($sqlCodigos);
    $stmtCodigos->execute([$_SESSION['id_usuario']]);
    $misCodigos = $stmtCodigos->fetchAll(PDO::FETCH_ASSOC);

    // Empresas
    $stmtEmpresas = $pdo->query("SELECT * FROM empresas ORDER BY nombre_empresa ASC");
    $listaEmpresas = $stmtEmpresas->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Asesor | Coaching Lift</title>
    <link rel="icon" type="image/png" href="../../assets/img/lojo.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

    <style>
        :root { --primary: #2C3E50; --secondary: #34495E; --accent: #F3C623; --accent-dark: #FA812F; --bg-light: #FEF3E2; --white: #ffffff; --text-dark: #333; --green: #27ae60; --red: #c0392b; }
        body { background-color: var(--bg-light); font-family: 'Poppins', sans-serif; margin: 0; color: var(--text-dark); padding-bottom: 40px; }

        /* NAVBAR */
        .navbar { background: var(--white); padding: 15px 20px; box-shadow: 0 4px 15px rgba(250, 129, 47, 0.1); display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--accent-dark); position: sticky; top: 0; z-index: 100; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--primary); }
        .btn-logout { border: 2px solid var(--accent-dark); color: var(--accent-dark); padding: 6px 15px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: 0.3s; }
        .btn-logout:hover { background: var(--accent-dark); color: var(--white); }

        /* LAYOUT */
        .container { max-width: 1400px; margin: 20px auto; padding: 0 15px; display: grid; gap: 20px; grid-template-columns: 1fr; }
        @media (min-width: 1000px) { .container { grid-template-columns: 320px 1fr; align-items: start; } }

        /* CARDS */
        .card { background: var(--white); border-radius: 12px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-top: 4px solid var(--accent-dark); }
        .card h3 { margin-top: 0; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; font-size: 1.1rem; }

        /* INPUTS */
        label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--primary); font-size: 0.85rem; }
        select, input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; background: #fafafa; font-family: inherit; margin-bottom: 15px; font-size: 0.95rem; }
        .btn-block { width: 100%; background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 100%); color: var(--white); font-weight: 700; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; transition: 0.2s; }
        .btn-block:hover { transform: translateY(-2px); }

        /* TABLA */
        .header-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .table-wrapper { width: 100%; overflow-x: auto; border-radius: 8px; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        th { background: var(--primary); color: var(--white); padding: 12px; text-align: left; font-size: 0.85rem; white-space: nowrap; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 0.85rem; vertical-align: middle; color: #555; }
        
        /* BOTONES TABLA */
        .btn-icon { padding: 7px 12px; border: none; border-radius: 6px; cursor: pointer; color: white; margin-left: 3px; font-size: 0.9rem; transition: 0.2s; }
        .btn-edit { background-color: var(--secondary); } 
        .btn-prac { background-color: var(--accent-dark); }
        .btn-disabled { background-color: #ddd; color: #999; cursor: not-allowed; }
        .btn-invite { background: #3498db; color: white; border: none; border-radius: 6px; padding: 10px 15px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 0.9rem; }

        /* MODALES */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal-content { background: white; width: 90%; max-width: 400px; border-radius: 15px; padding: 25px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.3); animation: slideUp 0.3s; }
        .btn-close-modal { position: absolute; top: 10px; right: 10px; border: none; background: none; font-size: 1.5rem; cursor: pointer; color: #777; }
        
        /* ESTILOS PARA LA LISTA DESPLEGABLE PERSONALIZADA */
        .custom-dropdown { position: relative; width: 100%; margin-bottom: 20px; }
        .dropdown-results { 
            display: none; 
            position: absolute; 
            top: 100%; 
            left: 0; 
            width: 100%; 
            max-height: 180px; 
            overflow-y: auto; 
            background: white; 
            border: 1px solid #ccc; 
            border-radius: 0 0 8px 8px; 
            z-index: 10; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        }
        .dropdown-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; font-size: 0.9rem; transition: background 0.2s; }
        .dropdown-item:hover { background-color: var(--bg-light); color: var(--accent-dark); font-weight: bold; }
        .dropdown-item:last-child { border-bottom: none; }

        /* ALERTA */
        .alert-box { background: white; padding: 25px; border-radius: 12px; width: 85%; max-width: 350px; text-align: center; box-shadow: 0 15px 40px rgba(0,0,0,0.4); border-top: 5px solid var(--accent-dark); }
        .alert-btn { background: var(--primary); color: white; padding: 8px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 15px; }

        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .code-result { margin-top: 15px; padding: 10px; background: #fff3e0; border: 1px dashed orange; border-radius: 8px; text-align: center; display: none; }
        .scroll-list { max-height: 200px; overflow-y: auto; background: #fafafa; border-radius: 8px; border: 1px solid #eee; }
        .list-item { padding: 8px 12px; border-bottom: 1px solid #eee; display: flex; gap: 8px; align-items: center; font-size: 0.85rem; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">
            <img src="../../assets/img/logo.png" style="height:40px;" alt="Logo">
            <span>Panel Asesor</span>
        </div>
        <a href="../../controllers/auth_logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Salir
        </a>
    </nav>

    <div class="container">
        
        <aside>
            <div class="card">
                <h3><i class="fas fa-qrcode"></i> Nueva Evaluación</h3>
                <form id="formGenerar">
                    <label>Curso:</label>
                    <select id="selExamen">
                        <?php foreach ($examenes as $ex): ?>
                            <option value="<?php echo $ex['id_examen']; ?>"><?php echo htmlspecialchars($ex['titulo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label>Tipo de Evento:</label>
                    <select id="selTipo">
                        <option value="Curso">Curso Regular</option>
                        <option value="Prueba de Selección">Prueba de Selección</option>
                    </select>
                    
                    <button type="button" class="btn-block" onclick="generarCodigo()">
                        <i class="fas fa-magic"></i> Generar Código
                    </button>
                </form>
                
                <div id="resultadoCodigo" class="code-result">
                    <h2 id="txtCodigo" style="margin:0; color:var(--primary); font-size:1.5rem;"></h2>
                    <small style="color:#c0392b;">Expira: <span id="txtExpira"></span></small>
                </div>
            </div>

            <div class="card" style="margin-top:20px;">
                <h3><i class="fas fa-building"></i> Empresas</h3>
                <div style="display:flex; gap:5px;">
                    <input type="text" id="txtNuevaEmpresa" placeholder="Nueva empresa...">
                    <button onclick="agregarEmpresa()" class="btn-block" style="width:auto;"><i class="fas fa-plus"></i></button>
                </div>
                <div id="listaEmpresasDiv" class="scroll-list">
                    <?php foreach ($listaEmpresas as $emp): ?>
                        <div class="list-item"><i class="far fa-building"></i> <?php echo htmlspecialchars($emp['nombre_empresa']); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <main>
            <div class="card">
                <div class="header-actions">
                    <h3 style="margin:0; border:none; flex:1;"><i class="fas fa-users"></i> Resultados</h3>
                    
                    <div style="display:flex; gap:5px; flex-wrap:wrap;">
                        <select id="filtroCodigo" style="margin:0; min-width:200px;">
                            <option value="" disabled selected>Selecciona código...</option>
                            <?php foreach ($misCodigos as $sesion): 
                                $esMio = ($sesion['id_asesor'] == $_SESSION['id_usuario']);
                                $icono = $esMio ? '' : '🤝 ';
                            ?>
                                <option value="<?php echo $sesion['codigo_acceso']; ?>">
                                    <?php echo $icono . $sesion['codigo_acceso']; ?> (<?php echo date('d/m', strtotime($sesion['fecha_creacion'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button onclick="abrirModalInvitar()" class="btn-invite" title="Asignar a otro asesor">
                            <i class="fas fa-user-plus"></i>
                        </button>

                        <button onclick="exportarExcel()" style="background:#27ae60; color:white; border:none; padding:10px 15px; border-radius:8px; cursor:pointer;">
                            <i class="fas fa-file-excel"></i>
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table id="tablaAlumnos">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Participante</th>
                                <th>Empresa</th>
                                <th style="text-align:center;">Int.</th>
                                <th>Teoría (100)</th>
                                <th>Práctica (100)</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAlumnos">
                            <tr><td colspan="7" style="color:#999; padding:20px; text-align:center;">Selecciona un código para ver datos.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div style="text-align:right; font-size:0.7rem; color:#aaa; margin-top:5px;">
                    <i class="fas fa-sync-alt fa-spin"></i> Actualizando en tiempo real
                </div>
            </div>
        </main>
    </div>

    <div id="modalPractica" class="modal-overlay">
        <div class="modal-content" style="text-align:center;">
            <button class="btn-close-modal" onclick="cerrarModal('modalPractica')">&times;</button>
            <div id="modalLoading" style="padding:20px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--accent-dark);"></i></div>
            <div id="modalContent" style="display:none;">
                <img id="imgPerfil" src="" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid var(--accent); margin-bottom:10px;">
                <h3 id="nombreParticipante" style="margin:0; color:var(--primary);"></h3>
                <p id="empresaParticipante" style="color:#666; font-size:0.9rem; margin-bottom:20px;"></p>
                <div style="text-align:left; background:#f9f9f9; padding:15px; border-radius:8px;">
                    <label>Selecciona Certificación:</label>
                    <select id="selTipoPractica" style="margin-bottom:0;"></select>
                </div>
                <br>
                <button onclick="irAEvaluacion()" class="btn-block">Comenzar Evaluación</button>
            </div>
        </div>
    </div>

    <div id="modalInvitar" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="max-width: 400px; overflow:visible;">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-bottom:1px solid #eee;">
                <h3 style="margin:0;">Invitar Colaborador</h3>
                <button onclick="cerrarModalInvitar()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            
            <div class="modal-body" style="padding:20px;">
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">
                    Busca al asesor por nombre o correo para darle acceso.
                </p>

                <label style="display:block; margin-bottom:5px; font-weight:bold;">Buscar Asesor:</label>
                
                <div class="custom-dropdown">
                    <input type="text" id="txtBuscarAsesor" placeholder="Escribe para buscar..." autocomplete="off"
                           onkeyup="filtrarAsesores()" onfocus="mostrarListaAsesores()"
                           style="width:100%; margin-bottom:0; padding:12px;">
                    
                    <div id="dropdownResultados" class="dropdown-results">
                        </div>
                    
                    <input type="hidden" id="idAsesorSeleccionado">
                </div>

                <button onclick="enviarInvitacion()" class="btn-block" style="margin-top:20px;">
                    <i class="fas fa-paper-plane"></i> Enviar Invitación
                </button>
            </div>
        </div>
    </div>

    <div id="webAlert" class="modal-overlay" style="z-index:3000;">
        <div class="alert-box">
            <div id="alertIcon" style="font-size:2.5rem; margin-bottom:10px;"></div>
            <h3 id="alertTitle" style="margin:0; color:var(--primary);"></h3>
            <p id="alertMessage" style="color:#666; font-size:0.9rem; margin:10px 0;"></p>
            <button class="alert-btn" onclick="cerrarModal('webAlert')">Entendido</button>
        </div>
    </div>

    <script>
        // VARIABLES GLOBALES (Fuera de cualquier función)
        let intervaloMonitoreo;
        let datosAlumnosGlobal = [];
        let usuarioActivo = null;
        let sesionActiva = null;
        let asesoresGlobal = [];

        // 1. INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function() {
            const filtro = document.getElementById('filtroCodigo');
            if(filtro) {
                // Si ya hay un valor seleccionado (por recarga o caché), cargar de una vez
                if(filtro.value) iniciarMonitoreo();
                
                // Listener para cuando cambie
                filtro.addEventListener('change', iniciarMonitoreo);
            }

            // Cerrar lista de búsqueda si clic fuera
            document.addEventListener('click', function(e) {
                const drop = document.getElementById('dropdownResultados');
                const input = document.getElementById('txtBuscarAsesor');
                if (drop.style.display === 'block' && e.target !== input && e.target !== drop) {
                    drop.style.display = 'none';
                }
            });
        });

        // 2. MONITOREO
        function iniciarMonitoreo() {
            cargarAlumnos();
            if(intervaloMonitoreo) clearInterval(intervaloMonitoreo);
            intervaloMonitoreo = setInterval(cargarAlumnos, 5000);
        }

        function cargarAlumnos() {
            const codigo = document.getElementById('filtroCodigo').value;
            if(!codigo) return;

            fetch(`../../controllers/asesor_lista_alumnos.php?codigo=${codigo}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'error' || !Array.isArray(data)) {
                    console.error("Error en datos:", data.mensaje || data);
                    return;
                }

                datosAlumnosGlobal = data;
                const tbody = document.getElementById('tbodyAlumnos');

                if(data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="color:#999; padding:20px; text-align:center;">No hay participantes en este código.</td></tr>';
                    return;
                }

                let html = '';
                data.forEach(al => {
                    // COLORES DE NOTAS
                    let notaTeoHtml = al.estado_teoria === 'En Progreso' 
                        ? `<span style="color:#3498db; font-weight:bold; font-size:0.8rem;">En Curso...</span>` 
                        : `<strong style="color:${Math.round(al.nota_teoria) >= 80 ? '#27ae60' : '#c0392b'}">${Math.round(al.nota_teoria)}/100</strong>`;

                    let notaPracHtml = al.nota_practica !== null 
                        ? `<strong style="color:${Math.round(al.nota_practica) >= 80 ? '#27ae60' : '#c0392b'}">${Math.round(al.nota_practica)}/100</strong>` 
                        : `<span style="color:#ccc;">Pendiente</span>`;

                    // BOTONES
                    let btnEditarHtml, btnCrearHtml;
                    if (al.nota_practica !== null) {
                        btnEditarHtml = `<button onclick="irAEvaluacionDirecta('${al.cedula}', '${al.id_tipo_practica}')" class="btn-icon btn-edit" title="Corregir"><i class="fas fa-pen"></i></button>`;
                        btnCrearHtml = `<button class="btn-icon btn-disabled" disabled><i class="fas fa-check"></i></button>`;
                    } else {
                        btnEditarHtml = `<button class="btn-icon btn-disabled" disabled><i class="fas fa-pen"></i></button>`;
                        btnCrearHtml = `<button onclick="abrirModalPractica('${al.cedula}')" class="btn-icon btn-prac" title="Evaluar"><i class="fas fa-clipboard-check"></i></button>`;
                    }

                    html += `
                        <tr>
                            <td><strong>${al.cedula}</strong></td>
                            <td>${al.nombre_completo}</td>
                            <td>${al.nombre_empresa}</td>
                            <td style="text-align:center;">${al.intentos_count}</td>
                            <td>${notaTeoHtml}</td>
                            <td>${notaPracHtml}</td>
                            <td style="text-align:right; white-space:nowrap;">${btnEditarHtml} ${btnCrearHtml}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            })
            .catch(err => console.error("Fallo fetch tabla:", err));
        }

        // 3. GENERAR CÓDIGO
        function generarCodigo() {
            const btn = document.querySelector('#formGenerar button');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            btn.disabled = true;
            
            const examenSelect = document.getElementById('selExamen');
            const payload = {
                id_examen: examenSelect.value,
                tipo_evento: document.getElementById('selTipo').value,
                nombre_curso: examenSelect.options[examenSelect.selectedIndex].text
            };

            fetch('../../controllers/asesor_crear_sesion.php', { 
                method: 'POST', 
                body: JSON.stringify(payload) 
            })
            .then(r => r.json())
            .then(d => {
                if(d.status === 'success'){
                    document.getElementById('resultadoCodigo').style.display = 'block';
                    document.getElementById('txtCodigo').innerText = d.codigo;
                    document.getElementById('txtExpira').innerText = d.expira;
                    
                    // Agregar al select
                    const sel = document.getElementById('filtroCodigo');
                    const op = document.createElement('option');
                    op.value = d.codigo;
                    op.text = `${d.codigo} (Nuevo)`;
                    op.selected = true;
                    sel.prepend(op);
                    
                    iniciarMonitoreo();
                } else {
                    mostrarAlerta("Error", d.error || "No se pudo generar.", "error");
                }
            })
            .catch(e => mostrarAlerta("Error", "Fallo de red.", "error"))
            .finally(() => { 
                btn.innerHTML = '<i class="fas fa-magic"></i> Generar Código'; 
                btn.disabled = false; 
            });
        }

        // 4. INVITAR ASESOR (BUSCADOR INTERNO MEJORADO)
        let codigoParaInvitar = null;
        let listaColegasCache = []; 

        function abrirModalInvitar(codigoSesion) {
            codigoParaInvitar = document.getElementById('filtroCodigo').value;
            
            if(!codigoParaInvitar) {
                mostrarAlerta("Atención", "Selecciona un código primero.", "info");
                return;
            }

            document.getElementById('modalInvitar').style.display = 'flex';
            
            const input = document.getElementById('txtBuscarAsesor');
            const listaDiv = document.getElementById('dropdownResultados');
            
            input.value = ''; 
            document.getElementById('idAsesorSeleccionado').value = '';
            input.placeholder = "Cargando lista...";
            listaDiv.innerHTML = '';
            listaDiv.style.display = 'none';

            // Cargar lista desde la base de datos
            fetch('../../controllers/asesor_listar_colegas.php')
            .then(res => res.json())
            .then(data => {
                input.placeholder = "Escribe el nombre del asesor...";
                listaColegasCache = data; 
                renderizarLista(data);
            })
            .catch(err => {
                console.error(err);
                input.placeholder = "Error al cargar lista";
            });
        }

        function renderizarLista(datos) {
            const listaDiv = document.getElementById('dropdownResultados');
            listaDiv.innerHTML = '';
            
            if(datos.length === 0) {
                listaDiv.innerHTML = '<div class="dropdown-item" style="color:#999;">No hay resultados</div>';
                return;
            }

            datos.forEach(asesor => {
                const div = document.createElement('div');
                div.className = 'dropdown-item';
                div.innerHTML = `<strong>${asesor.nombre_completo}</strong> <br><small>${asesor.email}</small>`;
                div.onclick = function() {
                    seleccionarAsesor(asesor.id_usuario, asesor.nombre_completo);
                };
                listaDiv.appendChild(div);
            });
        }

        function mostrarListaAsesores() {
            const listaDiv = document.getElementById('dropdownResultados');
            if(listaColegasCache.length > 0) {
                listaDiv.style.display = 'block';
            }
        }

        function filtrarAsesores() {
            const texto = document.getElementById('txtBuscarAsesor').value.toLowerCase();
            const filtrados = listaColegasCache.filter(a => 
                a.nombre_completo.toLowerCase().includes(texto) || 
                a.email.toLowerCase().includes(texto)
            );
            renderizarLista(filtrados);
            document.getElementById('dropdownResultados').style.display = 'block';
        }

        function seleccionarAsesor(id, nombre) {
            document.getElementById('txtBuscarAsesor').value = nombre;
            document.getElementById('idAsesorSeleccionado').value = id;
            document.getElementById('dropdownResultados').style.display = 'none';
        }

        function cerrarModalInvitar() {
            document.getElementById('modalInvitar').style.display = 'none';
        }

        function enviarInvitacion() {
            const idAsesor = document.getElementById('idAsesorSeleccionado').value;
            const nombreAsesor = document.getElementById('txtBuscarAsesor').value;
            
            if(!idAsesor) {
                alert("Por favor selecciona un asesor de la lista.");
                return;
            }

            if(confirm(`¿Confirmas invitar a: ${nombreAsesor} al código ${codigoParaInvitar}?`)) {
                
                // --- CAMBIO AQUÍ: Ahora sí llamamos al archivo que guarda en BD ---
                const btn = document.querySelector('#modalInvitar button.btn-block');
                const textoOriginal = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

                fetch('../../controllers/asesor_guardar_invitacion.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ 
                        codigo: codigoParaInvitar, 
                        id_invitado: idAsesor 
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        mostrarAlerta("¡Éxito!", `Ahora ${nombreAsesor} colabora en este código.`, "success");
                        cerrarModalInvitar();
                    } else {
                        mostrarAlerta("Error", data.mensaje, "error");
                    }
                })
                .catch(err => {
                    console.error(err);
                    mostrarAlerta("Error", "Fallo de conexión.", "error");
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                });
            }
        }

        // 5. EVALUACIONES
        function abrirModalPractica(cedula) {
            usuarioActivo = cedula;
            sesionActiva = document.getElementById('filtroCodigo').value;
            document.getElementById('modalPractica').style.display = 'flex';
            document.getElementById('modalContent').style.display = 'none';
            document.getElementById('modalLoading').style.display = 'block';

            fetch(`../../controllers/asesor_get_tipos_practica.php?cedula=${cedula}&codigo=${sesionActiva}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('modalLoading').style.display = 'none';
                if (data.status === 'success') {
                    document.getElementById('modalContent').style.display = 'block';
                    document.getElementById('imgPerfil').src = `../../assets/uploads/${data.alumno.foto_perfil}`;
                    document.getElementById('nombreParticipante').innerText = data.alumno.nombre_completo;
                    document.getElementById('empresaParticipante').innerText = data.alumno.nombre_empresa;
                    const sel = document.getElementById('selTipoPractica');
                    sel.innerHTML = '';
                    data.opciones.forEach(op => sel.innerHTML += `<option value="${op.id_tipo_practica}">${op.nombre_certificacion}</option>`);
                } else {
                    mostrarAlerta("Aviso", data.mensaje, "error");
                    cerrarModal('modalPractica');
                }
            });
        }

        function irAEvaluacion() {
            const idTipo = document.getElementById('selTipoPractica').value;
            location.href = `evaluar_practica.php?cedula=${usuarioActivo}&codigo=${sesionActiva}&tipo=${idTipo}`;
        }

        function irAEvaluacionDirecta(cedula, idTipo) {
            const codigo = document.getElementById('filtroCodigo').value;
            location.href = `evaluar_practica.php?cedula=${cedula}&codigo=${codigo}&tipo=${idTipo}`;
        }

        // 6. UTILIDADES
        function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function agregarEmpresa() {
            const val = document.getElementById('txtNuevaEmpresa').value.trim();
            if(val) fetch('../../controllers/asesor_add_empresa.php', { method:'POST', body:JSON.stringify({nombre_empresa:val}) })
                .then(r=>r.json()).then(d=>{ if(d.status==='success') {
                    const div = document.createElement('div'); div.className='list-item'; div.innerHTML=`<i class="far fa-building"></i> ${val}`;
                    document.getElementById('listaEmpresasDiv').prepend(div);
                    document.getElementById('txtNuevaEmpresa').value='';
                }});
        }

        function exportarExcel() { mostrarAlerta("Info", "Exportar disponible en versión completa.", "info"); }

        function mostrarAlerta(titulo, mensaje, tipo) {
            const modal = document.getElementById('webAlert');
            const icon = document.getElementById('alertIcon');
            document.getElementById('alertTitle').innerText = titulo;
            document.getElementById('alertMessage').innerText = mensaje;
            
            if(tipo === 'error') icon.innerHTML = '<i class="fas fa-times-circle" style="color:var(--red)"></i>';
            else if(tipo === 'success') icon.innerHTML = '<i class="fas fa-check-circle" style="color:var(--green)"></i>';
            else icon.innerHTML = '<i class="fas fa-info-circle" style="color:var(--accent)"></i>';
            
            modal.style.display = 'flex';
        }
    </script>
</body>
</html>