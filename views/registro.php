<?php

require '../config/db.php';

$empresas = []; 
try {
    $stmt = $pdo->query("SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silencioso
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Coaching Lift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>
<body>
    <div class="container">
        
        <div class="logo-container">
            <img src="../assets/img/logo.png" alt="Logo Coaching Lift">
        </div>

        <h2>Crear Cuenta</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    $err = $_GET['error'];
                    if ($err == 'cedula_repetida') {
                        echo "⚠️ Error: Esta cédula ya está registrada en el sistema.";
                    } elseif ($err == 'email_repetido') {
                        echo "⚠️ Error: Este correo electrónico ya está en uso.";
                    } elseif ($err == 'codigo_invalido') {
                        echo "⛔ Error: El código de Asesor es incorrecto.";
                    } elseif ($err == 'falta_foto') {
                        echo "📷 Error: Debes subir una foto de perfil.";
                    } elseif ($err == 'error_tecnico') {
                        echo "❌ Error del sistema. Inténtalo de nuevo.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <form action="../controllers/auth_registro.php" method="POST" enctype="multipart/form-data" id="formRegistro">
            
            <div class="form-group">
                <label>Quiero registrarme como:</label>
                <select name="rol_seleccionado" id="rolSelect" onchange="cambiarFormulario()">
                    <option value="Participante" selected>Participante</option>
                    <option value="Asesor">Asesor (Interno)</option>
                </select>
            </div>

            <div id="alerta-asesor" class="alert" style="display: none;">
                <strong>Zona Restringida:</strong> Solo para personal autorizado. Se requerirá código de acceso al final.
            </div>

            <hr>

            <div class="form-group">
                <label>Nombre Completo:</label>
                <input type="text" name="nombre_completo" id="nombre" required>
            </div>

            <div class="form-group">
                <label>Cédula / Identificación:</label>
                <input type="text" name="cedula" id="cedula" required pattern="[0-9]*" title="Solo números">
            </div>

            <div class="form-group">
                <label>Correo Electrónico:</label>
                <input type="email" name="correo" id="correo" required>
            </div>

            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" required>
            </div>

            <div id="campos-participante">
                <div class="form-group">
                    <label>Cargo:</label>
                    <input type="text" name="cargo" id="cargo" class="input-participante" required>
                </div>

                <div class="form-group">
                    <label>Años de Experiencia:</label>
                    <input type="number" name="anios_experiencia" id="anios" class="input-participante" min="0" required>
                </div>

                <div class="form-group">
                    <label>Empresa:</label>
                    <select name="id_empresa" id="empresa" class="input-participante" required>
                        <option value="" disabled selected>Seleccione empresa...</option>
                        <?php if (count($empresas) > 0): ?>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id_empresa']; ?>">
                                    <?php echo htmlspecialchars($empresa['nombre_empresa']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No hay empresas registradas</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Foto de Perfil:</label>
                    <input type="file" name="foto_perfil" id="fotoInput" class="input-participante" accept="image/*" required>
                </div>
            </div>

            <div id="campos-asesor" style="display: none;">
                <div class="form-group">
                    <label>Fecha de Nacimiento:</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nac" class="input-asesor">
                </div>

                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="tel" name="telefono" id="telefono" class="input-asesor">
                </div>

                <div class="form-group">
                    <label style="color: var(--accent-dark);">Código Maestro:</label>
                    <input type="password" name="codigo_registro" class="input-asesor" placeholder="Ingrese código de seguridad">
                </div>
            </div>

            <button type="submit">Completar Registro</button>
        </form>

        <div class="link-text">
            ¿Ya tienes cuenta? <a href="login.php">Inicia Sesión aquí</a>
        </div>
    </div>

    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirma tus Datos</h3>
                <p>Verifica que todo esté correcto antes de guardar.</p>
            </div>

            <div class="data-summary">
                <div class="data-row"><strong>Rol:</strong> <span id="conf-rol"></span></div>
                <div class="data-row"><strong>Nombre:</strong> <span id="conf-nombre"></span></div>
                <div class="data-row"><strong>Cédula:</strong> <span id="conf-cedula"></span></div>
                <div class="data-row"><strong>Correo:</strong> <span id="conf-correo"></span></div>

                <div id="resumen-participante">
                    <div class="data-row"><strong>Cargo:</strong> <span id="conf-cargo"></span></div>
                    <div class="data-row"><strong>Experiencia:</strong> <span id="conf-anios"></span></div>
                    <div class="data-row"><strong>Empresa ID:</strong> <span id="conf-empresa"></span></div>
                    
                    <p style="text-align:center; margin-top:10px; color:var(--secondary);">Previsualización de Foto:</p>
                    <img id="conf-foto-preview" class="img-preview" src="" alt="Vista previa">
                </div>

                <div id="resumen-asesor" style="display:none;">
                    <div class="data-row"><strong>Teléfono:</strong> <span id="conf-telefono"></span></div>
                    <div class="data-row"><strong>Fecha Nac.:</strong> <span id="conf-nacimiento"></span></div>
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="cerrarModal()">Corregir</button>
                <button type="button" class="btn-confirm" onclick="enviarFormularioReal()">Confirmar y Guardar</button>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('formRegistro');
        const modal = document.getElementById('modalConfirmacion');
        let isConfirmed = false; // Bandera para saber si ya confirmamos

        // 1. Interceptar el envío del formulario
        form.addEventListener('submit', function(event) {
            if (!isConfirmed) {
                event.preventDefault(); // Detener el envío real
                mostrarModal(); // Mostrar la confirmación
            }
            // Si isConfirmed es true, deja pasar el evento y el formulario se envía
        });

        function mostrarModal() {
            // Obtener valores básicos
            const rol = document.getElementById('rolSelect').value;
            document.getElementById('conf-rol').textContent = rol;
            document.getElementById('conf-nombre').textContent = document.getElementById('nombre').value;
            document.getElementById('conf-cedula').textContent = document.getElementById('cedula').value;
            document.getElementById('conf-correo').textContent = document.getElementById('correo').value;

            // Mostrar/Ocultar secciones según el rol
            const resPart = document.getElementById('resumen-participante');
            const resAsesor = document.getElementById('resumen-asesor');

            if (rol === 'Participante') {
                resPart.style.display = 'block';
                resAsesor.style.display = 'none';

                // Llenar datos Participante
                document.getElementById('conf-cargo').textContent = document.getElementById('cargo').value;
                document.getElementById('conf-anios').textContent = document.getElementById('anios').value + " años";
                
                // Obtener texto del select de empresa (no el ID)
                const selectEmpresa = document.getElementById('empresa');
                const textoEmpresa = selectEmpresa.options[selectEmpresa.selectedIndex].text;
                document.getElementById('conf-empresa').textContent = textoEmpresa;

                // PREVISUALIZAR IMAGEN
                const fileInput = document.getElementById('fotoInput');
                const imgPreview = document.getElementById('conf-foto-preview');
                
                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgPreview.src = e.target.result; // Poner la imagen leída en el src
                    }
                    reader.readAsDataURL(fileInput.files[0]);
                } else {
                    imgPreview.src = ''; // Si no hay foto
                }

            } else {
                // Llenar datos Asesor
                resPart.style.display = 'none';
                resAsesor.style.display = 'block';

                document.getElementById('conf-telefono').textContent = document.getElementById('telefono').value;
                document.getElementById('conf-nacimiento').textContent = document.getElementById('fecha_nac').value;
            }

            // Mostrar la modal con animación
            modal.classList.add('active');
        }

        function cerrarModal() {
            modal.classList.remove('active');
        }

        function enviarFormularioReal() {
            isConfirmed = true; // Cambiamos bandera
            form.submit(); // Disparamos el envío manual (ahora pasará el addEventListener)
        }

        // Lógica original de cambio de formulario (Participante/Asesor)
        function cambiarFormulario() {
            const rol = document.getElementById('rolSelect').value;
            const divParticipante = document.getElementById('campos-participante');
            const divAsesor = document.getElementById('campos-asesor');
            const alertaAsesor = document.getElementById('alerta-asesor');
            const inputsPart = document.querySelectorAll('.input-participante');
            const inputsAsesor = document.querySelectorAll('.input-asesor');

            if (rol === 'Participante') {
                divParticipante.style.display = 'block';
                divAsesor.style.display = 'none';
                alertaAsesor.style.display = 'none';
                inputsPart.forEach(input => input.setAttribute('required', 'required'));
                inputsAsesor.forEach(input => input.removeAttribute('required'));
            } else {
                divParticipante.style.display = 'none';
                divAsesor.style.display = 'block';
                alertaAsesor.style.display = 'block';
                inputsAsesor.forEach(input => input.setAttribute('required', 'required'));
                inputsPart.forEach(input => input.removeAttribute('required'));
            }
        }
    </script>
</body>
</html>