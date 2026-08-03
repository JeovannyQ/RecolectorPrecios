<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Recolector de Precios</title>
    <!-- Google Fonts Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #090d16;
            --bg-card: #131c2e;
            --bg-input: #1b273e;
            --border-color: #263552;
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --accent-glow: rgba(2, 132, 199, 0.35);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --error: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-primary);
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(2, 132, 199, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(16, 185, 129, 0.12) 0%, transparent 45%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(12px);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-box {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #0284c7, #10b981);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 10px 20px var(--accent-glow);
        }

        .logo-icon svg {
            width: 36px;
            height: 36px;
            fill: none;
            stroke: #ffffff;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: none;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text-primary);
            font-size: 1rem;
            outline: none;
            transition: all 0.2s ease;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 16px var(--accent-glow);
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px var(--accent-glow);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .demo-info {
            margin-top: 24px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px dashed var(--border-color);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .demo-info code {
            color: #38bdf8;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-box">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <h1>Recolector de Precios</h1>
            <p class="subtitle">Monitoreo Semanal de Precios Agrícolas</p>
        </div>

        <div id="alertBox" class="alert alert-danger"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ej. admin" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" id="btnSubmit" class="btn-submit">
                <span>Ingresar al Sistema</span>
            </button>
        </form>

        <div class="demo-info">
            Acceso inicial: Usuario <code>admin</code> | Clave <code>admin123</code>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('alertBox');
            const btnSubmit = document.getElementById('btnSubmit');
            
            alertBox.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = 'Verificando...';

            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value.trim();

            try {
                const response = await fetch('api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ usuario, password })
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    alertBox.textContent = data.error || 'Error de inicio de sesión';
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                alertBox.textContent = 'Error de conexión con el servidor';
                alertBox.style.display = 'block';
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = 'Ingresar al Sistema';
            }
        });
    </script>
</body>
</html>
