<?php
// systems/admin/home.php
session_start();
// Solo entra si es id_rol = 1 (Admin)
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header("Location: ../../views/login.php"); exit;
}
require '../../config/db.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración | Coaching Lift</title>
    <link rel="icon" type="image/png" href="../../assets/img/lojo.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --primary: #2C3E50; --accent: #FA812F; --bg: #FEF3E2; --white: #fff; --green: #27ae60; --red: #c0392b; --blue: #34495E; }
        body { background-color: var(--bg); font-family: 'Poppins', sans-serif; margin: 0; padding-bottom: 50px; color: #333; }
        
        .navbar { background: var(--white); padding: 15px 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--accent); }
        .navbar img { height: 45px; }
        .btn-logout { border: 2px solid var(--accent); color: var(--accent); padding: 6px 15px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-logout:hover { background: var(--accent); color: white; }

        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; display: grid; gap: 30px; grid-template-columns: 1fr; }
        @media(min-width: 950px) { .container { grid-template-columns: 40% 60%; align-items: start; } }

        .card { background: var(--white); border-radius: 12px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-top: 5px solid var(--accent); }
        .card h3 { margin-top: 0; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 10px; }

        /* FORMULARIOS */
        label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--primary); font-size: 0.85rem; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 10px; font-family: inherit; box-sizing: border-box; }
        .btn-block { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-block:hover { background: var(--accent); }

        /* TABLA ASESORES */
        .table-scroll { max-height: 400px; overflow-y: auto; margin-top: 20px; border: 1px solid #eee; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; text-align: left; padding: 12px; font-size: 0.85rem; color: #666; position: sticky; top: 0; }
        td { border-bottom: 1px solid #eee; padding: 10px; font-size: 0.85rem; }
        
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .active { background-color: var(--green); }
        .inactive { background-color: var(--red); }

        .btn-toggle { background: none; border: 1px solid #ccc; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 0.75rem; transition: 0.2s; }
        .btn-toggle:hover { background: #333; color: white; }

        /* --- ESTILOS NUEVOS PARA LA LISTA COMPACTA (ACORDEÓN) --- */
        .result-container { margin-top: 25px; display: block; animation: fadeIn 0.3s; }

        .accordion-item {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: 0.2s;
            border-left: 5px solid #ccc; /* Default color */
        }
        
        .accordion-item.aprobado { border-left-color: var(--green); }
        .accordion-item.reprobado { border-left-color: var(--red); }

        .accordion-header {
            padding: 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }
        .accordion-header:hover { background: #f0f0f0; }

        .accordion-content {
            display: none; /* Oculto por defecto */
            padding: 20px;
            border-top: 1px solid #eee;
            background: #fff;
            animation: slideDown 0.3s ease-out;
        }

        .show-details { display: block; } /* Clase para mostrar */

        /* ESTILOS INTERNOS DEL DETALLE */
        .score-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0; }
        .score-item { background: white; padding: 10px; border-radius: 8px; text-align: center; border: 1px solid #ddd; }
        .score-val { display: block; font-size: 1.4rem; font-weight: 800; margin: 5px 0; }
        .score-label { font-size: 0.75rem; text-transform: uppercase; color: #999; font-weight: bold; }
        
        .pass { color: var(--green); border-bottom: 3px solid var(--green); }
        .fail { color: var(--red); border-bottom: 3px solid var(--red); }

        .btn-print { 
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--green); color: white; padding: 10px; 
            text-decoration: none; border-radius: 6px; font-weight: bold; 
            text-align: center; transition: 0.2s; flex: 1; font-size: 0.9rem;
        }
        .btn-print:hover { opacity: 0.9; }
        .btn-carnet { background-color: var(--blue); }

        @keyframes fadeIn { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }
        @keyframes slideDown { from{opacity:0; transform:translateY(-10px);} to{opacity:1; transform:translateY(0);} }
    </style>
</head>
<body>

    <nav class="navbar">
        <div style="display:flex; align-items:center; gap:15px;">
            <img src="../../assets/img/logo.png" alt="Logo">
            <span style="font-weight:700; color:var(--primary);">Panel Administrador</span>
        </div>
        <a href="../../controllers/auth_logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </nav>

    <div class="container">
        
        <div class="card">
            <h3><i class="fas fa-user-tie"></i> Gestión de Asesores</h3>
            
            <form id="formAsesor">
                <label>Nombre Completo:</label>
                <input type="text" id="txtNombre" placeholder="Ej: Juan Pérez" required>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div><label>Cédula:</label><input type="text" id="txtCedula" required></div>
                    <div><label>Teléfono:</label><input type="text" id="txtTel"></div>
                </div>

                <label>Correo (Login):</label>
                <input type="email" id="txtEmail" placeholder="asesor@empresa.com" required>
                
                <label>Contraseña:</label>
                <input type="password" id="txtPass" required>
                
                <button type="button" class="btn-block" onclick="crearAsesor()">
                    <i class="fas fa-plus-circle"></i> Registrar Asesor
                </button>
            </form>

            <div class="table-scroll">
                <table id="tablaAsesores">
                    <thead><tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Acción</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px;">
                <h3 style="margin:0; border:none; padding:0;"><i class="fas fa-certificate"></i> Monitor de Evaluaciones</h3>
                <span id="liveIndicator" style="font-size:0.8rem; color:green; display:none;"><i class="fas fa-circle fa-beat"></i> En vivo</span>
            </div>

            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="flex: 1; min-width: 200px;">
                    <label>Filtrar por Código:</label>
                    <select id="selectCodigo" onchange="buscarCertificado()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; background:white;">
                        <option value="">-- Ver Recientes (Todos) --</option>
                        <?php
                        try {
                            $sqlCodes = "SELECT DISTINCT s.codigo_acceso, ex.titulo, s.fecha_creacion 
                                         FROM sesiones s 
                                         JOIN examenes ex ON s.id_examen = ex.id_examen 
                                         ORDER BY s.fecha_creacion DESC LIMIT 50";
                            $stmtCodes = $pdo->query($sqlCodes);
                            while($row = $stmtCodes->fetch(PDO::FETCH_ASSOC)){
                                $fecha = date('d/m', strtotime($row['fecha_creacion']));
                                echo "<option value='{$row['codigo_acceso']}'>{$row['codigo_acceso']} - {$row['titulo']} ($fecha)</option>";
                            }
                        } catch(Exception $e) {}
                        ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label>Buscar Cédula:</label>
                    <input type="text" id="txtBusqueda" placeholder="Ingrese Cédula..." onkeyup="buscarCertificado()" style="margin-bottom:0;">
                </div>
            </div>

            <div id="resultadosContainer" class="result-container">
                </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            listarAsesores();
            buscarCertificado(); // Carga inicial
            setInterval(buscarCertificado, 5000); // Auto-refrescar
        });

        function listarAsesores() {
            fetch('../../controllers/admin_asesores.php?accion=listar')
            .then(res => res.json())
            .then(data => {
                const tbody = document.querySelector('#tablaAsesores tbody');
                tbody.innerHTML = '';
                if(data.length === 0) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px;">No hay asesores.</td></tr>'; return; }
                data.forEach(u => {
                    const isActivo = u.estado === 'Activo';
                    const estadoHtml = isActivo ? '<span class="status-dot active"></span> Activo' : '<span class="status-dot inactive"></span> Inactivo';
                    tbody.innerHTML += `<tr>
                        <td>${u.nombre_completo || 'Sin nombre'}</td>
                        <td style="font-size:0.8rem;">${u.email}</td>
                        <td>${estadoHtml}</td>
                        <td><button class="btn-toggle" onclick="toggleAsesor(${u.id_usuario}, '${u.estado}')">${isActivo ? 'Desactivar' : 'Activar'}</button></td>
                    </tr>`;
                });
            });
        }

        function crearAsesor() {
            const nom = document.getElementById('txtNombre').value;
            const ced = document.getElementById('txtCedula').value;
            const tel = document.getElementById('txtTel').value;
            const email = document.getElementById('txtEmail').value;
            const pass = document.getElementById('txtPass').value;
            if(!nom || !email || !pass) return alert("Completa campos");

            fetch('../../controllers/admin_asesores.php?accion=crear', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ nombre: nom, cedula: ced, telefono: tel, email: email, password: pass })
            }).then(res=>res.json()).then(data => {
                if(data.status==='success'){ alert("Asesor creado"); document.getElementById('formAsesor').reset(); listarAsesores(); }
                else alert("Error: "+data.mensaje);
            });
        }
        
        function toggleAsesor(id, est) {
            if(!confirm("¿Cambiar estado?")) return;
            fetch('../../controllers/admin_asesores.php?accion=toggle', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_usuario: id, estado_actual: est })
            }).then(() => listarAsesores());
        }

        // --- LÓGICA MONITOR (MODIFICADA PARA LISTA COMPACTA) ---
        function buscarCertificado() {
            const cedula = document.getElementById('txtBusqueda').value;
            const codigo = document.getElementById('selectCodigo').value;
            const indicador = document.getElementById('liveIndicator');
            const container = document.getElementById('resultadosContainer');

            if(cedula === '' && codigo === '') indicador.style.display = 'inline-block';
            else indicador.style.display = 'none';

            fetch(`../../controllers/admin_buscar_certificado.php?q=${cedula}&codigo=${codigo}`)
            .then(res => res.json())
            .then(data => {
                // Guardamos los IDs que estaban abiertos para restaurarlos
                const abiertos = [];
                document.querySelectorAll('.accordion-content.show-details').forEach(el => {
                   abiertos.push(el.id);
                });

                container.innerHTML = ''; 

                if(data.status === 'error') {
                    container.innerHTML = `<p style="color:red; text-align:center;">${data.mensaje}</p>`;
                    return;
                }
                
                if (data.cantidad === 0) {
                    container.innerHTML = '<p style="text-align:center; color:#777; padding:20px;">Esperando actividad reciente...</p>';
                    return;
                }

                container.innerHTML = `<p style="margin-bottom:15px; font-size:0.9rem; color:#666;">Mostrando <strong>${data.cantidad}</strong> resultados.</p>`;

                data.lista.forEach((item, index) => {
                    const d = item.datos;
                    const n = item.notas;
                    const claseItem = item.aprobado_final ? 'aprobado' : 'reprobado';
                    const uniqueId = 'detail-' + index + '-' + d.cedula; // ID único para el acordeón
                    
                    // Estado visual para el encabezado (Badge pequeño)
                    const badge = item.aprobado_final 
                        ? '<span style="background:#d4edda; color:#155724; padding:2px 8px; border-radius:10px; font-size:0.7rem; font-weight:bold;">APROBADO</span>'
                        : '<span style="background:#f8d7da; color:#721c24; padding:2px 8px; border-radius:10px; font-size:0.7rem; font-weight:bold;">REPROBADO</span>';

                    // Lógica de botones y contenido detallado
                    let contenidoAccion = '';
                    const teoClass = n.teoria_ok ? 'pass' : 'fail';
                    const pracClass = n.practica_ok ? 'pass' : 'fail';
                    const teoIcon = n.teoria_ok ? '✓' : '✕';
                    const pracIcon = n.practica_ok ? '✓' : '✕';

                    if (d.tipo_evento === 'Prueba de Selección') {
                         let colorBtn = item.aprobado_final ? '#27ae60' : '#e67e22';
                         let textoEstado = item.aprobado_final ? 'APTO' : 'NO APTO';
                         contenidoAccion = `
                             <div style="margin-top:10px; text-align:center;">
                                 <div style="margin-bottom:5px; font-weight:bold; color:${item.aprobado_final ? 'green':'red'}">RESULTADO: ${textoEstado}</div>
                                 <a href="informe_print.php?cedula=${d.cedula}&codigo=${d.codigo_acceso}" target="_blank" class="btn-print" style="background-color:${colorBtn};">
                                     <i class="fas fa-file-contract"></i> INFORME TÉCNICO
                                 </a>
                             </div>`;
                    } else {
                        if(item.aprobado_final) {
                            contenidoAccion = `
                                <div style="display:flex; gap:10px; margin-top:10px;">
                                    <a href="certificado_print.php?cedula=${d.cedula}&codigo=${d.codigo_acceso}" target="_blank" class="btn-print"><i class="fas fa-certificate"></i> Certificado</a>
                                    <a href="carnet_print.php?cedula=${d.cedula}&codigo=${d.codigo_acceso}" target="_blank" class="btn-print btn-carnet"><i class="fas fa-id-card"></i> Carnet</a>
                                </div>`;
                        } else {
                            contenidoAccion = `
                                <div style="margin-top:10px; text-align:center;">
                                    <div style="margin-bottom:5px; font-weight:bold; color:var(--red);">ESTADO: NO APROBADO</div>
                                    <a href="informe_print.php?cedula=${d.cedula}&codigo=${d.codigo_acceso}" target="_blank" class="btn-print" style="background-color:var(--red);"><i class="fas fa-file-contract"></i> INFORME DE REPROBACIÓN</a>
                                </div>`;
                        }
                    }

                    // HTML ACORDEÓN
                    const htmlItem = `
                    <div class="accordion-item ${claseItem}">
                        <div class="accordion-header" onclick="toggleDetails('${uniqueId}')">
                            <div style="flex:1;">
                                <strong style="font-size:1rem; color:#333;">${d.nombre_completo}</strong>
                                <span style="font-size:0.85rem; color:#666;"> - ${d.cedula}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:15px;">
                                <span style="font-size:0.75rem; color:#888;">${d.codigo_acceso}</span>
                                ${badge}
                                <i class="fas fa-chevron-down" style="color:#aaa;"></i>
                            </div>
                        </div>

                        <div id="${uniqueId}" class="accordion-content">
                            <p style="margin:0 0 10px 0; color:#666; font-size:0.9rem;">
                                <strong>Empresa:</strong> ${d.nombre_empresa} <br>
                                <strong>Examen:</strong> ${d.examen}
                            </p>
                            
                            <div class="score-grid">
                                <div class="score-item ${teoClass}">
                                    <span class="score-label">Teoría</span>
                                    <span class="score-val">${n.teoria}</span>
                                    <span>${teoIcon} (Min: 80)</span>
                                </div>
                                <div class="score-item ${pracClass}">
                                    <span class="score-label">Práctica</span>
                                    <span class="score-val">${n.practica}</span>
                                    <span>${pracIcon} (Min: 80)</span>
                                </div>
                            </div>
                            
                            ${contenidoAccion}
                        </div>
                    </div>`;
                    
                    container.innerHTML += htmlItem;
                });

                // Restaurar los que estaban abiertos (si siguen en la lista)
                abiertos.forEach(id => {
                    const el = document.getElementById(id);
                    if(el) el.classList.add('show-details');
                });
            })
            .catch(err => console.error(err));
        }

        // Función para abrir/cerrar acordeón
        function toggleDetails(id) {
            const content = document.getElementById(id);
            if (content.classList.contains('show-details')) {
                content.classList.remove('show-details');
            } else {
                // Opcional: Cerrar otros abiertos para mantener limpieza
                // document.querySelectorAll('.accordion-content').forEach(el => el.classList.remove('show-details'));
                content.classList.add('show-details');
            }
        }
    </script>
</body>
</html>