<?php
// views/aula_examen.php
session_start();
if (!isset($_SESSION['id_sesion_activa'])) { 
    header("Location: ../systems/participante/home.php"); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación | Coaching Lift</title>
    <link rel="icon" type="image/png" href="../assets/img/lojo.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2C3E50;
            --accent: #F3C623;
            --accent-dark: #FA812F;
            --background: #FEF3E2;
            --white: #ffffff;
            --green: #27ae60;
            --red: #c0392b;
        }

        body { background-color: var(--background); font-family: 'Poppins', sans-serif; margin: 0; padding-bottom: 80px; color: #333; }

        .timer-bar {
            position: fixed; top: 0; left: 0; width: 100%;
            background: var(--primary); color: var(--white);
            padding: 15px; text-align: center; font-weight: 700; z-index: 500;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); border-bottom: 4px solid var(--accent-dark);
        }

        .container { max-width: 800px; margin: 100px auto 40px auto; padding: 20px; }

        .header-exam { text-align: center; margin-bottom: 30px; }
        .header-exam img { height: 60px; margin-bottom: 10px; }
        .header-exam h2 { color: var(--primary); margin: 0; }

        .question-card {
            background: var(--white); padding: 25px; margin-bottom: 25px;
            border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 6px solid var(--accent-dark);
        }
        
        .question-title { font-weight: 700; font-size: 1.1rem; color: var(--primary); margin-bottom: 15px; display: flex; gap: 10px; }
        .question-img { max-width: 100%; border-radius: 8px; margin: 10px 0; border: 1px solid #ddd; }

        .options-list { list-style: none; padding: 0; margin: 0; }
        .options-list li { margin-bottom: 10px; }

        .option-label {
            display: flex; align-items: center; gap: 10px; padding: 12px 15px;
            background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; transition: 0.2s;
        }
        .option-label:hover { background: #fff8e1; border-color: var(--accent); }
        .option-label:has(input:checked) { border-color: var(--accent-dark); background: #fff3e0; }
        input[type="radio"] { accent-color: var(--accent-dark); transform: scale(1.2); }

        .btn-finish {
            width: 100%; padding: 18px; font-size: 1.2rem; background: var(--primary); color: var(--white);
            border: none; border-radius: 12px; cursor: pointer; font-weight: bold; box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
        }
        .btn-finish:hover { background: #34495E; transform: translateY(-2px); }

        /* MODAL */
        .custom-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(44, 62, 80, 0.9); z-index: 2000;
            display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px);
        }
        .custom-box {
            background: var(--white); padding: 30px; border-radius: 15px;
            width: 90%; max-width: 450px; text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.4); border-top: 6px solid var(--accent-dark);
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .box-title { font-size: 1.5rem; color: var(--primary); margin: 10px 0; }
        .box-text { color: #555; margin-bottom: 20px; }
        
        /* CORRECCIÓN DE LA NOTA GRANDE */
        .box-score { font-size: 3.5rem; font-weight: 800; color: var(--accent-dark); margin: 10px 0; }
        .box-score small { font-size: 1.5rem; color: #999; font-weight: 600; }

        .btn-modal { padding: 12px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white; margin: 5px; width: 100%; margin-bottom: 10px; font-size: 1rem; }
        .btn-retry { background: var(--accent); color: var(--primary); }
        .btn-save { background: var(--primary); }
        .btn-close { background: #95a5a6; }
    </style>
</head>
<body>

    <div id="timerDisplay" class="timer-bar"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>

    <div class="container">
        <div class="header-exam">
            <img src="../assets/img/logo.png" alt="Logo">
            <h2>Evaluación Teórica</h2>
            <p>Lee con atención y selecciona la mejor respuesta.</p>
        </div>
        
        <form id="quizForm">
            <div id="questions-container"></div>
            <br>
            <button id="btnTerminar" type="button" onclick="previsualizarNota()" class="btn-finish">
                TERMINAR Y VER NOTA <i class="fas fa-check-circle"></i>
            </button>
        </form>
    </div>

    <div id="modalMsg" class="custom-overlay">
        <div class="custom-box">
            <div id="modalIcon" style="font-size: 3rem; margin-bottom:10px;"></div>
            <h3 id="modalTitle" class="box-title"></h3>
            
            <div id="modalScoreArea" style="display:none;">
                <div class="box-score">
                    <span id="txtNota">0</span><small>/100</small>
                </div>
            </div>
            
            <p id="modalBody" class="box-text"></p>
            <div id="modalButtons"></div>
        </div>
    </div>

    <script>
        let tiempoRestante = 0; 
        let intervalo;
        let idIntentoActual = 0;
        let preguntasCargadas = [];
        let notaCalculadaTemp = 0;
        
        // 1. NUEVA VARIABLE GLOBAL PARA GUARDAR LAS RESPUESTAS
        let respuestasParaGuardar = []; 

        document.addEventListener('DOMContentLoaded', function() { iniciarExamen(); });

        function iniciarExamen() {
            fetch('../controllers/quiz_init.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'error') {
                        mostrarModal("Error", data.mensaje, "error", true);
                        return;
                    }
                    idIntentoActual = data.id_intento;
                    tiempoRestante = data.tiempo_restante;
                    preguntasCargadas = data.preguntas;
                    renderizarPreguntas(data.preguntas);
                    iniciarReloj();
                })
                .catch(err => mostrarModal("Error", "No se pudo cargar el examen.", "error", true));
        }

        function renderizarPreguntas(preguntas) {
            const container = document.getElementById('questions-container');
            let html = '';
            if(preguntas.length === 0) { container.innerHTML = '<p style="text-align:center">No hay preguntas.</p>'; return; }

            preguntas.forEach((preg, index) => {
                html += `
                <div class="question-card">
                    <div class="question-title"><span>${index + 1}.</span> <span>${preg.enunciado}</span></div>
                    ${preg.imagen_url ? `<img src="../assets/uploads/preguntas/${preg.imagen_url}" class="question-img">` : ''}
                    <ul class="options-list">`;
                preg.opciones.forEach(op => {
                    html += `<li><label class="option-label"><input type="radio" name="p_${preg.id_pregunta}" value="${op.id_opcion}"><span>${op.texto_opcion}</span></label></li>`;
                });
                html += `</ul></div>`;
            });
            container.innerHTML = html;
        }

        function iniciarReloj() {
            const display = document.getElementById('timerDisplay');
            intervalo = setInterval(() => {
                if (tiempoRestante <= 0) {
                    clearInterval(intervalo);
                    mostrarModal("Tiempo Agotado", "Se enviará tu examen automáticamente.", "info", false);
                    setTimeout(autoGuardarPorTiempo, 2000);
                    return;
                }
                let m = Math.floor(tiempoRestante / 60);
                let s = Math.floor(tiempoRestante % 60);
                display.innerHTML = `⏱️ ${m}m ${s}s`;
                if (tiempoRestante < 300) display.style.backgroundColor = "#e74c3c";
                tiempoRestante--;
            }, 1000);
        }

        function previsualizarNota() {
            const btn = document.getElementById('btnTerminar');
            btn.innerHTML = 'Calculando...'; btn.disabled = true;

            const respuestas = [];
            preguntasCargadas.forEach(preg => {
                const selected = document.querySelector(`input[name="p_${preg.id_pregunta}"]:checked`);
                if (selected) respuestas.push({ id_pregunta: preg.id_pregunta, id_opcion: selected.value });
            });
            
            // 2. GUARDAMOS LAS RESPUESTAS EN LA VARIABLE GLOBAL
            respuestasParaGuardar = respuestas;

            fetch('../controllers/quiz_previsualizar.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_intento: idIntentoActual, respuestas: respuestas })
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = 'TERMINAR Y VER NOTA'; btn.disabled = false;
                if (data.status === 'success') {
                    notaCalculadaTemp = data.nota;
                    mostrarModalDecision(data.nota);
                } else { mostrarModal("Error", data.mensaje, "error", false); }
            });
        }

        function mostrarModal(titulo, mensaje, tipo, forzarSalida) {
            const modal = document.getElementById('modalMsg');
            const btns = document.getElementById('modalButtons');
            document.getElementById('modalTitle').innerText = titulo;
            document.getElementById('modalBody').innerHTML = mensaje;
            document.getElementById('modalScoreArea').style.display = 'none';

            let iconHtml = tipo === 'error' ? '<i class="fas fa-times-circle" style="color:#e74c3c;"></i>' : '<i class="fas fa-info-circle" style="color:#3498db;"></i>';
            document.getElementById('modalIcon').innerHTML = iconHtml;

            if(forzarSalida) btns.innerHTML = `<button class="btn-modal btn-close" onclick="window.location.href='../systems/participante/home.php'">Salir</button>`;
            else btns.innerHTML = `<button class="btn-modal btn-close" onclick="document.getElementById('modalMsg').style.display='none'">Cerrar</button>`;
            modal.style.display = 'flex';
        }

        function mostrarModalDecision(nota) {
            const modal = document.getElementById('modalMsg');
            document.getElementById('modalIcon').innerHTML = '<i class="fas fa-clipboard-check" style="color:var(--accent-dark);"></i>';
            document.getElementById('modalTitle').innerText = "Resultado Preliminar";
            document.getElementById('modalBody').innerText = "¿Qué deseas hacer?";
            
            document.getElementById('modalScoreArea').style.display = 'block';
            document.getElementById('txtNota').innerText = nota; 

            const btns = document.getElementById('modalButtons');
            btns.innerHTML = `
                <button class="btn-modal btn-retry" onclick="confirmarAccion('retry')">🔄 Guardar y Reintentar</button>
                <button class="btn-modal btn-save" onclick="confirmarAccion('guardar')">💾 Guardar y Finalizar</button>
            `;
            modal.style.display = 'flex';
        }

        function confirmarAccion(accion) {
            // 3. AQUÍ ESTABA EL ERROR: AGREGAMOS 'respuestas' AL ENVÍO
            fetch('../controllers/quiz_confirmar.php', {
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    accion: accion, 
                    id_intento: idIntentoActual, 
                    nota: notaCalculadaTemp,
                    respuestas: respuestasParaGuardar // <--- ¡ESTO FALTABA!
                })
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') {
                    if (data.accion_siguiente === 'reload') window.location.reload();
                    else window.location.href = '../systems/participante/home.php';
                } else alert(data.mensaje);
            });
        }

        function autoGuardarPorTiempo() {
             // Lógica automática de guardado...
             // Asegúrate de implementar esto también enviando respuestasParaGuardar si lo usas
        }
    </script>
</body>
</html>