<?php
// systems/participante/home.php
session_start();
require '../../config/db.php';

// 1. Seguridad
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 3) {
    header("Location: ../../views/login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

try {
    // Datos del Participante
    $sqlPerfil = "SELECT p.nombre_completo, p.foto_perfil, e.nombre_empresa 
                  FROM perfil_participante p
                  JOIN empresas e ON p.id_empresa = e.id_empresa
                  WHERE p.id_usuario = ?";
    $stmt = $pdo->prepare($sqlPerfil);
    $stmt->execute([$id_usuario]);
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Bienvenido | Coaching Lift</title>
    
    <link rel="icon" type="image/png" href="../../assets/img/lojo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- PALETA DE COLORES CORPORATIVA --- */
        :root {
            --primary: #2C3E50;         /* Azul Oscuro (Textos, Títulos) */
            --secondary: #34495E;       /* Azul Grisáceo */
            --accent: #F3C623;          /* Amarillo */
            --accent-light: #FFB22C;    /* Naranja Claro */
            --accent-dark: #FA812F;     /* NARANJA FUERTE (Predominante) */
            --background-light: #FEF3E2;/* Crema Suave (Fondo) */
            --text-dark: #333;
            --white: #ffffff;
            --gray: #7f8c8d;
        }

        * { box-sizing: border-box; }

        body { 
            background-color: var(--background-light); 
            font-family: 'Poppins', sans-serif; /* Fuente moderna */
            margin: 0; padding: 0;
            color: var(--text-dark);
        }

        /* --- HEADER / NAVBAR --- */
        .dashboard-header {
            background: var(--white);
            padding: 15px 30px;
            box-shadow: 0 4px 15px rgba(250, 129, 47, 0.15); /* Sombra naranja suave */
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border-bottom: 3px solid var(--accent-dark); /* Línea naranja corporativa */
            position: sticky; top: 0; z-index: 100;
        }

        .user-section {
            display: flex; align-items: center; gap: 15px;
        }

        .profile-pic {
            width: 55px; height: 55px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 3px solid var(--accent); /* Borde amarillo/naranja */
        }

        .user-info h3 { margin: 0; font-size: 1.1rem; color: var(--primary); font-weight: 700; }
        .user-info p { margin: 0; color: var(--gray); font-size: 0.85rem; }

        /* Botón Salir (Estilo Outline Naranja) */
        .btn-logout {
            color: var(--accent-dark);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 18px;
            border: 2px solid var(--accent-dark);
            border-radius: 50px;
            transition: 0.3s;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-logout:hover {
            background: var(--accent-dark);
            color: var(--white);
        }

        /* --- CONTENEDOR PRINCIPAL --- */
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh; /* Centrado vertical */
            padding: 20px;
        }

        /* --- CAJA DE CÓDIGO (CARD) --- */
        .code-box {
            background: var(--white);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.1);
            text-align: center;
            width: 100%;
            max-width: 480px;
            border-top: 8px solid var(--accent-dark); /* Techo naranja */
            animation: slideUp 0.5s ease-out;
        }

        .code-box h2 { 
            color: var(--primary); 
            margin-bottom: 10px; 
            font-size: 1.8rem;
        }
        
        .code-box p { 
            color: var(--secondary); 
            margin-bottom: 30px; 
            font-size: 0.95rem; 
        }

        /* Input Grande y Moderno */
        .input-code {
            width: 100%;
            padding: 15px;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 4px;
            text-transform: uppercase;
            border: 2px solid #ddd;
            border-radius: 12px;
            margin-bottom: 25px;
            transition: 0.3s;
            background: #fafafa;
            color: var(--primary);
            font-weight: bold;
        }

        .input-code:focus {
            border-color: var(--accent-dark);
            background: var(--white);
            outline: none;
            box-shadow: 0 0 0 4px rgba(250, 129, 47, 0.2); /* Resplandor naranja */
        }

        /* Botón Principal (Naranja Predominante) */
        .btn-ingresar {
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent-light) 100%);
            color: var(--white);
            border: none;
            padding: 16px;
            font-size: 1.1rem;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            font-weight: 700;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; justify-content: center; align-items: center; gap: 10px;
            box-shadow: 0 4px 15px rgba(250, 129, 47, 0.4);
        }

        .btn-ingresar:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(250, 129, 47, 0.6);
        }
        
        .btn-ingresar:active { transform: scale(0.98); }

        /* --- LOGO EN MOVIL --- */
        .logo-nav { height: 50px; } /* Ajuste altura logo */

        /* --- RESPONSIVIDAD (MÓVIL) --- */
        @media (max-width: 600px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
            }
            .user-section {
                flex-direction: column;
            }
            .btn-logout {
                width: 100%;
                justify-content: center;
            }
            .code-box {
                padding: 25px;
            }
            .input-code {
                font-size: 1.2rem;
            }
        }

        /* --- ALERTAS WEB (MODAL) --- */
        .custom-alert-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(44, 62, 80, 0.7); /* Fondo oscuro azulado */
            z-index: 2000;
            display: none; align-items: center; justify-content: center;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s;
        }

        .custom-alert-box {
            background: var(--white); padding: 30px; border-radius: 15px;
            width: 90%; max-width: 400px; text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
            border-top: 5px solid var(--accent-dark);
        }

        .alert-icon { font-size: 3.5rem; margin-bottom: 15px; }
        .alert-error { color: #e74c3c; }   /* Rojo para error (Universal) */
        .alert-success { color: #27ae60; } /* Verde para éxito */
        
        .alert-btn {
            margin-top: 20px; padding: 12px 30px; border-radius: 8px; border: none;
            cursor: pointer; font-weight: bold; 
            background: var(--primary); color: var(--white);
            transition: 0.2s;
        }
        .alert-btn:hover { background: var(--secondary); }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    </style>
</head>
<body>
    
    <header class="dashboard-header">
        <div class="user-section">
            <img src="../../assets/uploads/<?php echo htmlspecialchars($perfil['foto_perfil']); ?>" 
                 alt="Foto Perfil" class="profile-pic">
            
            <div class="user-info">
                <h3>Hola, <?php echo htmlspecialchars($perfil['nombre_completo']); ?></h3>
                <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($perfil['nombre_empresa']); ?></p>
            </div>
        </div>
        
        <img src="../../assets/img/logo.png" alt="Coaching Lift" class="logo-nav">

        <a href="../../controllers/auth_logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> <span>Salir</span>
        </a>
    </header>

    <div class="main-container">
        
        <div class="code-box">
            <div style="background:var(--background-light); width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px auto;">
                <i class="fas fa-qrcode" style="font-size:2.5rem; color:var(--accent-dark);"></i>
            </div>

            <h2>Evaluación en Línea</h2>
            <p>Introduce el código de acceso proporcionado por tu instructor para desbloquear el examen.</p>
            
            <input type="text" id="inputCodigo" class="input-code" placeholder="CÓDIGO AQUÍ" maxlength="20">
            
            <button id="btnValidar" onclick="validarCodigo()" class="btn-ingresar">
                INICIAR AHORA <i class="fas fa-arrow-right"></i>
            </button>
        </div>

    </div>

    <div id="webAlert" class="custom-alert-overlay">
        <div class="custom-alert-box">
            <div id="alertIcon" class="alert-icon"></div>
            <h3 id="alertTitle" style="color:var(--primary); margin:0 0 10px 0;"></h3>
            <p id="alertMessage" style="color:#666; line-height:1.5;"></p>
            <button class="alert-btn" onclick="cerrarAlerta()">Entendido</button>
        </div>
    </div>

    <script>
        const input = document.getElementById('inputCodigo');
        const modal = document.getElementById('webAlert');

        // Función Alerta
        function mostrarAlerta(titulo, mensaje, tipo) {
            const icon = document.getElementById('alertIcon');
            const title = document.getElementById('alertTitle');
            const msg = document.getElementById('alertMessage');

            title.innerText = titulo;
            msg.innerText = mensaje;
            
            if (tipo === 'error') {
                icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                icon.className = 'alert-icon alert-error';
            } else {
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                icon.className = 'alert-icon alert-success';
            }

            modal.style.display = 'flex';
        }

        function cerrarAlerta() {
            modal.style.display = 'none';
        }

        // Lógica Principal
        function validarCodigo() {
            const codigo = input.value.trim().toUpperCase();
            
            if (codigo === "") {
                mostrarAlerta("Atención", "Por favor, escribe el código de la evaluación.", "error");
                return;
            }

            // Efecto de carga
            const btn = document.getElementById('btnValidar');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validando...';
            btn.disabled = true;

            fetch('../../controllers/quiz_validar_codigo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ codigo: codigo })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // ÉXITO: Redirigimos
                    window.location.href = `../../views/aula_examen.php?id_examen=${data.id_examen}`;
                } else {
                    // ERROR
                    mostrarAlerta("Error de Acceso", data.mensaje, "error");
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                mostrarAlerta("Error de Red", "No pudimos conectar con el servidor.", "error");
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }

        // Permitir tecla Enter
        input.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                validarCodigo();
            }
        });
    </script>
</body>
</html>