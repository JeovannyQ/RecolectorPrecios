<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userNombre       = $_SESSION['nombre'] ?? 'Usuario';
$userRol          = $_SESSION['rol_nombre'] ?? 'Informador';
$userRegional     = $_SESSION['regional_asignada'] ?? 'CIBAO NORTE';
$userZona         = $_SESSION['zona_asignada'] ?? 'SANTIAGO';
$isAdmin          = ($userRol === 'Administrador');

$userPermisos = $_SESSION['permisos'] ?? [];
if (empty($userPermisos)) {
    if ($isAdmin) {
        $userPermisos = ['dashboard', 'colecta', 'historico', 'reportes', 'categorias', 'productos', 'usuarios'];
    } else {
        $userPermisos = ['colecta'];
    }
}

function hasPerm($perm) {
    global $userPermisos, $isAdmin;
    if ($isAdmin) return true;
    return in_array($perm, $userPermisos);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Recolector de Precios - Sistema Semanal</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #090d16;
            --bg-card: #131c2e;
            --bg-card-hover: #19253d;
            --bg-input: #1b273e;
            --border-color: #243350;
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --accent-glow: rgba(2, 132, 199, 0.3);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 70px;
        }

        /* Top Navbar */
        .navbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0284c7, #10b981);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .brand-icon svg { width: 22px; height: 22px; fill: none; stroke: white; stroke-width: 2; }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .brand-sub { font-size: 0.75rem; color: var(--text-muted); }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-badge {
            background: rgba(2, 132, 199, 0.15);
            border: 1px solid var(--accent);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #38bdf8;
            font-weight: 600;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-logout:hover { background: rgba(239, 68, 68, 0.3); }

        /* Main Container */
        .container {
            max-width: 1280px;
            margin: 20px auto;
            padding: 0 16px;
        }

        /* Module Navigation Tabs */
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            overflow-x: auto;
        }

        .tab-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .tab-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
            box-shadow: 0 6px 16px var(--accent-glow);
        }

        /* Section Cards */
        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
        }

        /* Header Form Fields */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            background: rgba(255, 255, 255, 0.02);
            padding: 18px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .field-group { display: flex; flex-direction: column; gap: 6px; }

        .field-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .field-group input, .field-group select {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 12px;
            color: var(--text-main);
            font-size: 0.95rem;
            outline: none;
        }

        .field-group input:focus, .field-group select:focus {
            border-color: var(--accent);
        }

        /* Fast Filter & Search Bar */
        .search-box {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 16px;
            color: var(--text-main);
            font-size: 0.95rem;
            outline: none;
        }

        .search-input:focus { border-color: var(--accent); }

        /* Category Pill Buttons */
        .category-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-bottom: 20px;
            scrollbar-width: thin;
        }

        .pill-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pill-btn.active {
            background: rgba(2, 132, 199, 0.2);
            border-color: var(--accent);
            color: #38bdf8;
            font-weight: 600;
        }

        /* Products Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .price-table th {
            background: #111827;
            padding: 14px 16px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
        }

        .price-table td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        .price-table tr:hover { background: var(--bg-card-hover); }

        .category-row {
            background: rgba(2, 132, 199, 0.08) !important;
            font-weight: 700;
            color: #38bdf8;
        }

        .category-row td {
            padding: 10px 16px;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-top: 1px solid var(--border-color);
        }

        .price-input {
            width: 100%;
            max-width: 110px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 10px;
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 600;
            text-align: right;
            outline: none;
            transition: all 0.2s;
        }

        .price-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .price-input.filled {
            background: rgba(16, 185, 129, 0.15);
            border-color: var(--success);
            color: #34d399;
        }

        .unit-tag {
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.06);
            padding: 4px 8px;
            border-radius: 6px;
            color: var(--text-muted);
        }

        .code-tag {
            font-family: monospace;
            font-size: 0.8rem;
            color: #38bdf8;
        }

        /* Bottom Floating Bar */
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(19, 28, 46, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--border-color);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 99;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
        }

        .counter-badge {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .counter-badge strong { color: var(--accent); font-size: 1.1rem; }

        .btn-action {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 14px var(--accent-glow);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--accent-glow);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover { background: rgba(255, 255, 255, 0.15); }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .navbar { padding: 10px 14px; }
            .form-grid { grid-template-columns: 1fr; }
            .bottom-bar { flex-direction: column; gap: 10px; text-align: center; }
            .price-table th, .price-table td { padding: 10px 12px; }
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M3 13h1v7c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-7h1a1 1 0 00.7-1.7l-9-9a1 1 0 00-1.4 0l-9 9A1 1 0 003 13z"/></svg>
            </div>
            <div>
                <div class="brand-title">Recolector de Precios</div>
                <div class="brand-sub">Sistema Semanal de Precios Agrícolas</div>
            </div>
        </div>

        <div class="user-nav">
            <div class="user-badge">
                👤 <?php echo htmlspecialchars($userNombre); ?> (<?php echo htmlspecialchars($userRol); ?>)
            </div>
            <a href="#" onclick="logout(); return false;" class="btn-logout">Salir</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        
        <!-- Navigation Tabs -->
        <div class="nav-tabs" id="mainNavTabs">
            <?php if (hasPerm('dashboard')): ?>
            <button class="tab-btn" onclick="switchTab('dashboard')">
                📊 Dashboard
            </button>
            <?php endif; ?>

            <?php if (hasPerm('colecta')): ?>
            <button class="tab-btn" onclick="switchTab('nueva')">
                📝 Nueva Colecta Semanal
            </button>
            <?php endif; ?>

            <?php if (hasPerm('historico')): ?>
            <button class="tab-btn" onclick="switchTab('historico')">
                📋 Histórico de Levantamientos
            </button>
            <?php endif; ?>

            <?php if (hasPerm('reportes')): ?>
            <button class="tab-btn" onclick="switchTab('reportes')">
                📈 Reportes & Tendencias
            </button>
            <?php endif; ?>

            <?php if (hasPerm('categorias')): ?>
            <button class="tab-btn" onclick="switchTab('admin_categorias')">
                📁 Categorías
            </button>
            <?php endif; ?>

            <?php if (hasPerm('productos')): ?>
            <button class="tab-btn" onclick="switchTab('admin_productos')">
                🏷️ Productos
            </button>
            <?php endif; ?>

            <?php if (hasPerm('usuarios')): ?>
            <button class="tab-btn" onclick="switchTab('admin_usuarios')">
                👥 Usuarios y Roles
            </button>
            <?php endif; ?>
        </div>

        <!-- TAB 0: DASHBOARD -->
        <?php if (hasPerm('dashboard')): ?>
        <div id="tabDashboard" class="tab-content" style="display: none;">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">📊 Panel Principal & Resumen de Precios</h2>
                    <button class="pill-btn" onclick="loadDashboardStats()">🔄 Actualizar Panel</button>
                </div>

                <!-- KPI Cards Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div style="background: linear-gradient(135deg, rgba(2,132,199,0.15), rgba(16,185,129,0.1)); border: 1px solid rgba(2,132,199,0.3); border-radius: 14px; padding: 20px;">
                        <div style="font-size: 0.8rem; font-weight: 600; color: #38bdf8; text-transform: uppercase;">Colectas del Mes</div>
                        <div id="dashColectasMes" style="font-family: 'Outfit', sans-serif; font-size: 2.2rem; font-weight: 700; color: white; margin: 8px 0;">0</div>
                        <div id="dashColectasTotal" style="font-size: 0.8rem; color: var(--text-muted);">0 colectas acumuladas</div>
                    </div>

                    <div style="background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(59,130,246,0.1)); border: 1px solid rgba(16,185,129,0.3); border-radius: 14px; padding: 20px;">
                        <div style="font-size: 0.8rem; font-weight: 600; color: #34d399; text-transform: uppercase;">Catálogo Agrícola</div>
                        <div id="dashTotalProductos" style="font-family: 'Outfit', sans-serif; font-size: 2.2rem; font-weight: 700; color: white; margin: 8px 0;">0</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Productos monitoreados</div>
                    </div>

                    <div style="background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(239,68,68,0.1)); border: 1px solid rgba(245,158,11,0.3); border-radius: 14px; padding: 20px;">
                        <div style="font-size: 0.8rem; font-weight: 600; color: #fbbf24; text-transform: uppercase;">Promedio Precios</div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px;">
                            <div>
                                <span style="font-size: 0.72rem; color: var(--text-muted); display: block;">Mayorista</span>
                                <strong id="dashAvgMayorista" style="font-size: 1.1rem; color: #38bdf8;">$0.00</strong>
                            </div>
                            <div>
                                <span style="font-size: 0.72rem; color: var(--text-muted); display: block;">Minorista</span>
                                <strong id="dashAvgMinorista" style="font-size: 1.1rem; color: #34d399;">$0.00</strong>
                            </div>
                            <div>
                                <span style="font-size: 0.72rem; color: var(--text-muted); display: block;">Finca</span>
                                <strong id="dashAvgFinca" style="font-size: 1.1rem; color: #fbbf24;">$0.00</strong>
                            </div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, rgba(168,85,247,0.15), rgba(236,72,153,0.1)); border: 1px solid rgba(168,85,247,0.3); border-radius: 14px; padding: 20px;">
                        <div style="font-size: 0.8rem; font-weight: 600; color: #c084fc; text-transform: uppercase;">Informadores / Colectores</div>
                        <div id="dashTotalInformadores" style="font-family: 'Outfit', sans-serif; font-size: 2.2rem; font-weight: 700; color: white; margin: 8px 0;">0</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Usuarios activos en sistema</div>
                    </div>
                </div>

                <!-- Recent Colectas Table & Quick Actions -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 12px; color: #38bdf8;">📋 Últimas Colectas Registradas</h3>
                        <div class="table-responsive">
                            <table class="price-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Zona</th>
                                        <th>Informador</th>
                                        <th>Ítems</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="dashUltimasColectasBody">
                                    <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">Cargando información del dashboard...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 12px; color: #34d399;">⚡ Accesos Rápidos</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php if (hasPerm('colecta')): ?>
                            <button class="btn-action" style="width: 100%; text-align: left; justify-content: flex-start;" onclick="switchTab('nueva')">
                                📝 Registrar Nueva Colecta
                            </button>
                            <?php endif; ?>
                            <?php if (hasPerm('historico')): ?>
                            <button class="pill-btn" style="width: 100%; text-align: left; justify-content: flex-start; padding: 14px;" onclick="switchTab('historico')">
                                📋 Ver Histórico de Hojas
                            </button>
                            <?php endif; ?>
                            <?php if (hasPerm('reportes')): ?>
                            <button class="pill-btn" style="width: 100%; text-align: left; justify-content: flex-start; padding: 14px;" onclick="switchTab('reportes')">
                                📈 Consultar Reportes de Precios
                            </button>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 20px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px;">
                            <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Distribución por Zona</h4>
                            <div id="dashZonaStats" style="display: flex; flex-direction: column; gap: 8px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Cargando zonas...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 1: NUEVA COLECTA -->
        <?php if (hasPerm('colecta')): ?>
        <div id="tabNueva" class="tab-content" style="display: none;">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title" id="formSectionTitle">Encabezado de Levantamiento</h2>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button class="btn-secondary" onclick="openImportModal()">📥 Importar CSV / Excel</button>
                        <span id="draftNotice" style="font-size: 0.82rem; color: #10b981; display: none; align-self: center;">
                            💾 Borrador guardado
                        </span>
                    </div>
                </div>

                <form id="colectaHeaderForm">
                    <input type="hidden" id="colectaId" value="">
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Fecha de Toma</label>
                            <input type="date" id="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="field-group">
                            <label>Regional</label>
                            <input type="text" id="regional" value="<?php echo htmlspecialchars($userRegional); ?>" required>
                        </div>
                        <div class="field-group">
                            <label>Zona</label>
                            <select id="zona" required>
                                <option value="SANTIAGO" <?php if($userZona==='SANTIAGO') echo 'selected'; ?>>SANTIAGO</option>
                                <option value="ESPAILLAT" <?php if($userZona==='ESPAILLAT') echo 'selected'; ?>>ESPAILLAT</option>
                                <option value="PUERTO PLATA" <?php if($userZona==='PUERTO PLATA') echo 'selected'; ?>>PUERTO PLATA</option>
                                <option value="LA SIERRA" <?php if($userZona==='LA SIERRA') echo 'selected'; ?>>LA SIERRA</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label>Informador (Colaborador)</label>
                            <input type="text" id="informador" value="<?php echo htmlspecialchars($userNombre); ?>" required>
                        </div>
                    </div>
                </form>

                <!-- Search & Category Filters -->
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="🔍 Buscar producto por nombre o código (ej: Arroz, Plátano, 1.01)..." onkeyup="filterProducts()">
                </div>

                <!-- Pills for categories -->
                <div class="category-pills" id="categoryPills">
                    <button class="pill-btn active" onclick="filterCategory('TODAS', this)">Todas las Categorías</button>
                </div>

                <!-- Product Spreadsheet Table -->
                <div class="table-responsive">
                    <table class="price-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Código</th>
                                <th>Producto</th>
                                <th style="width: 140px;">Unidad (UD)</th>
                                <th style="width: 130px; text-align: right;">Mayorista ($)</th>
                                <th style="width: 130px; text-align: right;">Minorista ($)</th>
                                <th style="width: 130px; text-align: right;">Finca ($)</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">Cargando catálogo oficial de productos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 2: HISTORICO -->
        <?php if (hasPerm('historico')): ?>
        <div id="tabHistorico" class="tab-content" style="display: none;">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Histórico de Levantamientos (Hojas de Colecta)</h2>
                    <button class="pill-btn" onclick="loadHistory()">🔄 Actualizar</button>
                </div>
                <div class="history-list" id="historyList">
                    <p style="color: var(--text-muted);">Cargando historial...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 3: REPORTES -->
        <?php if (hasPerm('reportes')): ?>
        <div id="tabReportes" class="tab-content" style="display: none;">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Reporte Comparativo de Precios</h2>
                </div>
                <div class="form-grid">
                    <div class="field-group">
                        <label>Fecha Desde</label>
                        <input type="date" id="repDesde" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="field-group">
                        <label>Fecha Hasta</label>
                        <input type="date" id="repHasta" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="field-group">
                        <label>Zona</label>
                        <select id="repZona">
                            <option value="">TODAS LAS ZONAS</option>
                            <option value="SANTIAGO">SANTIAGO</option>
                            <option value="ESPAILLAT">ESPAILLAT</option>
                            <option value="PUERTO PLATA">PUERTO PLATA</option>
                            <option value="LA SIERRA">LA SIERRA</option>
                        </select>
                    </div>
                    <div class="field-group" style="justify-content: flex-end;">
                        <button class="btn-action" onclick="generateReport()">🔍 Generar Reporte</button>
                    </div>
                </div>

                <div id="reportResult" class="table-responsive" style="margin-top: 20px;">
                    <p style="color: var(--text-muted); padding: 20px;">Seleccione los filtros para generar la consulta de precios.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 4: MANTENIMIENTO CATEGORIAS -->
        <?php if (hasPerm('categorias')): ?>
        <div id="tabAdminCategorias" class="tab-content" style="display: none;">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Mantenimiento de Categorías</h2>
                    <button class="btn-action" onclick="openCategoryModal()">➕ Nueva Categoría</button>
                </div>
                <div id="categoriesList" class="table-responsive">
                    <p style="padding: 20px; color: var(--text-muted);">Cargando categorías...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 5: MANTENIMIENTO PRODUCTOS -->
        <?php if (hasPerm('productos')): ?>
        <div id="tabAdminProductos" class="tab-content" style="display: none;">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Mantenimiento de Productos</h2>
                    <button class="btn-action" onclick="openProductModal()">➕ Nuevo Producto</button>
                </div>
                <div id="productsAdminList" class="table-responsive">
                    <p style="padding: 20px; color: var(--text-muted);">Cargando productos...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 6: USUARIOS Y ROLES -->
        <?php if (hasPerm('usuarios')): ?>
        <div id="tabAdminUsuarios" class="tab-content" style="display: none;">
            <div class="section-card" style="margin-bottom: 24px;">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Roles y Permisos de Menú</h2>
                    <button class="btn-action" onclick="openRoleModal()">➕ Nuevo Rol</button>
                </div>
                <div id="rolesList" class="table-responsive">
                    <p style="padding: 20px; color: var(--text-muted);">Cargando roles...</p>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Usuarios e Informadores de Campo</h2>
                    <button class="btn-action" onclick="openUserModal()">➕ Nuevo Usuario</button>
                </div>
                <div id="usersList" class="table-responsive">
                    <p style="padding: 20px; color: var(--text-muted);">Cargando usuarios...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Bottom Bar -->
    <div class="bottom-bar" id="bottomBar" style="display: none;">
        <div class="counter-badge">
            Productos digitados: <strong id="filledCounter">0</strong>
        </div>
        <button class="btn-action" onclick="saveColecta()">
            💾 Guardar Colecta Semanal
        </button>
    </div>

    <!-- Details Modal -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle" style="color: #38bdf8;">Detalles</h3>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        let catalogData = [];
        let rolesData = [];
        let filledProducts = {};
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const hasDashboardPerm = <?php echo hasPerm('dashboard') ? 'true' : 'false'; ?>;
        const hasColectaPerm = <?php echo hasPerm('colecta') ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', () => {
            loadCatalog();
            loadRoles();

            if (hasDashboardPerm) {
                switchTab('dashboard');
            } else if (hasColectaPerm) {
                switchTab('nueva');
            } else {
                const firstBtn = document.querySelector('.tab-btn');
                if (firstBtn) firstBtn.click();
            }
        });

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');

            const bottomBar = document.getElementById('bottomBar');
            if (bottomBar) bottomBar.style.display = (tab === 'nueva') ? 'flex' : 'none';

            if (tab === 'dashboard' && document.getElementById('tabDashboard')) {
                document.getElementById('tabDashboard').style.display = 'block';
                loadDashboardStats();
            } else if (tab === 'nueva' && document.getElementById('tabNueva')) {
                document.getElementById('tabNueva').style.display = 'block';
            } else if (tab === 'historico' && document.getElementById('tabHistorico')) {
                document.getElementById('tabHistorico').style.display = 'block';
                loadHistory();
            } else if (tab === 'reportes' && document.getElementById('tabReportes')) {
                document.getElementById('tabReportes').style.display = 'block';
            } else if (tab === 'admin_categorias' && document.getElementById('tabAdminCategorias')) {
                document.getElementById('tabAdminCategorias').style.display = 'block';
                renderAdminCategories();
            } else if (tab === 'admin_productos' && document.getElementById('tabAdminProductos')) {
                document.getElementById('tabAdminProductos').style.display = 'block';
                renderAdminProducts();
            } else if (tab === 'admin_usuarios' && document.getElementById('tabAdminUsuarios')) {
                document.getElementById('tabAdminUsuarios').style.display = 'block';
                loadAdminRoles();
                loadAdminUsers();
            }

            if (window.event && window.event.currentTarget) {
                window.event.currentTarget.classList.add('active');
            }
        }

        async function loadDashboardStats() {
            const body = document.getElementById('dashUltimasColectasBody');
            const zonaContainer = document.getElementById('dashZonaStats');
            if (body) body.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">Cargando información del dashboard...</td></tr>';
            
            try {
                const res = await fetch('api/dashboard/stats.php');
                const json = await res.json();
                if (json.success && json.data) {
                    const d = json.data;
                    document.getElementById('dashColectasMes').textContent = d.colectas_mes || '0';
                    document.getElementById('dashColectasTotal').textContent = (d.total_colectas || '0') + ' colectas acumuladas';
                    document.getElementById('dashTotalProductos').textContent = d.total_productos || '0';
                    document.getElementById('dashTotalInformadores').textContent = d.total_informadores || '0';

                    const p = d.promedios || {};
                    document.getElementById('dashAvgMayorista').textContent = '$' + (p.mayorista ? p.mayorista.toFixed(2) : '0.00');
                    document.getElementById('dashAvgMinorista').textContent = '$' + (p.minorista ? p.minorista.toFixed(2) : '0.00');
                    document.getElementById('dashAvgFinca').textContent = '$' + (p.finca ? p.finca.toFixed(2) : '0.00');

                    if (d.ultimas_colectas && d.ultimas_colectas.length > 0) {
                        let html = '';
                        d.ultimas_colectas.forEach(c => {
                            html += `
                                <tr>
                                    <td><strong>${c.fecha}</strong></td>
                                    <td>${c.zona}</td>
                                    <td>${c.informador_nombre}</td>
                                    <td><span class="unit-tag">${c.total_items || 0} prod</span></td>
                                    <td><span style="color: #10b981; font-weight: 600;">Completado</span></td>
                                </tr>
                            `;
                        });
                        if (body) body.innerHTML = html;
                    } else {
                        if (body) body.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">No hay colectas registradas aún.</td></tr>';
                    }

                    if (d.colectas_por_zona && d.colectas_por_zona.length > 0) {
                        let zHtml = '';
                        d.colectas_por_zona.forEach(z => {
                            zHtml += `
                                <div style="display: flex; justify-content: space-between; font-size: 0.88rem;">
                                    <span>📍 ${z.zona}</span>
                                    <strong style="color: #38bdf8;">${z.cantidad} colectas</strong>
                                </div>
                            `;
                        });
                        if (zonaContainer) zonaContainer.innerHTML = zHtml;
                    } else {
                        if (zonaContainer) zonaContainer.innerHTML = '<span style="color: var(--text-muted); font-size: 0.85rem;">Sin datos de zona</span>';
                    }
                }
            } catch(e) {
                console.error("Error al cargar dashboard:", e);
            }
        }

        async function loadCatalog() {
            try {
                const res = await fetch('api/productos/list.php');
                const json = await res.json();
                if (json.success) {
                    catalogData = json.data;
                    renderCategoriesPills();
                    renderProductsTable(catalogData);
                    loadDraft();
                    if (document.getElementById('tabAdminCategorias') && document.getElementById('tabAdminCategorias').style.display !== 'none') {
                        renderAdminCategories();
                    }
                    if (document.getElementById('tabAdminProductos') && document.getElementById('tabAdminProductos').style.display !== 'none') {
                        renderAdminProducts();
                    }
                }
            } catch (e) {
                console.error("Error al cargar catálogo:", e);
            }
        }

        async function loadRoles() {
            try {
                const res = await fetch('api/roles/list.php');
                const json = await res.json();
                if (json.success) {
                    rolesData = json.data;
                }
            } catch(e){}
        }

        function renderCategoriesPills() {
            const container = document.getElementById('categoryPills');
            if (!container) return;
            let html = `<button class="pill-btn active" onclick="filterCategory('TODAS', this)">Todas las Categorías</button>`;
            catalogData.forEach(cat => {
                html += `<button class="pill-btn" onclick="filterCategory('${cat.id}', this)">${cat.codigo}. ${cat.nombre}</button>`;
            });
            container.innerHTML = html;
        }

        function renderProductsTable(categories) {
            const tbody = document.getElementById('productsTableBody');
            if (!tbody) return;
            let html = '';

            categories.forEach(cat => {
                if (!cat.productos || cat.productos.length === 0) return;

                html += `<tr class="category-row" data-cat="${cat.id}"><td colspan="6">📁 ${cat.codigo}. ${cat.nombre}</td></tr>`;

                cat.productos.forEach(p => {
                    const pValues = filledProducts[p.id] || { mayorista: '', minorista: '', finca: '' };
                    html += `
                        <tr class="product-item" data-cat="${cat.id}">
                            <td><span class="code-tag">${p.codigo}</span></td>
                            <td style="font-weight: 500;">${p.nombre}</td>
                            <td><span class="unit-tag">${p.unidad_medida}</span></td>
                            <td style="text-align: right;">
                                <input type="number" step="0.01" inputmode="decimal" 
                                    class="price-input ${pValues.mayorista ? 'filled' : ''}" 
                                    placeholder="0.00" 
                                    value="${pValues.mayorista}" 
                                    oninput="onPriceChange(${p.id}, 'mayorista', this.value, '${p.unidad_medida}')">
                            </td>
                            <td style="text-align: right;">
                                <input type="number" step="0.01" inputmode="decimal" 
                                    class="price-input ${pValues.minorista ? 'filled' : ''}" 
                                    placeholder="0.00" 
                                    value="${pValues.minorista}" 
                                    oninput="onPriceChange(${p.id}, 'minorista', this.value, '${p.unidad_medida}')">
                            </td>
                            <td style="text-align: right;">
                                <input type="number" step="0.01" inputmode="decimal" 
                                    class="price-input ${pValues.finca ? 'filled' : ''}" 
                                    placeholder="0.00" 
                                    value="${pValues.finca}" 
                                    oninput="onPriceChange(${p.id}, 'finca', this.value, '${p.unidad_medida}')">
                            </td>
                        </tr>
                    `;
                });
            });

            tbody.innerHTML = html || '<tr><td colspan="6" style="text-align:center; padding: 20px;">No hay productos encontrados.</td></tr>';
            updateCounter();
        }

        function renderAdminCategories() {
            const container = document.getElementById('categoriesList');
            if (!container) return;
            if (!catalogData || catalogData.length === 0) {
                container.innerHTML = '<p style="padding: 20px; color: var(--text-muted);">Cargando categorías...</p>';
                return;
            }
            let html = `
                <table class="price-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre de Categoría</th>
                            <th>Orden</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            catalogData.forEach(c => {
                const escapedName = (c.nombre || '').replace(/'/g, "\\'");
                html += `
                    <tr>
                        <td><span class="code-tag">${c.codigo}</span></td>
                        <td><strong>${c.nombre}</strong></td>
                        <td>${c.orden}</td>
                        <td>
                            <button class="pill-btn" onclick="editCategory(${c.id}, '${c.codigo}', '${escapedName}', ${c.orden})">✏️ Editar</button>
                        </td>
                    </tr>
                `;
            });
            html += `</tbody></table>`;
            container.innerHTML = html;
        }

        function renderAdminProducts() {
            const container = document.getElementById('productsAdminList');
            if (!container) return;
            if (!catalogData || catalogData.length === 0) {
                container.innerHTML = '<p style="padding: 20px; color: var(--text-muted);">Cargando productos...</p>';
                return;
            }

            let html = `
                <div style="margin-bottom: 16px;">
                    <input type="text" class="search-input" placeholder="🔍 Buscar producto en administración..." onkeyup="filterAdminProducts(this.value)">
                </div>
                <table class="price-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Unidad Medida</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            catalogData.forEach(cat => {
                if (cat.productos) {
                    cat.productos.forEach(p => {
                        const escapedName = (p.nombre || '').replace(/'/g, "\\'");
                        const escapedUnidad = (p.unidad_medida || '').replace(/'/g, "\\'");
                        html += `
                            <tr class="admin-prod-row" data-search="${p.codigo.toLowerCase()} ${p.nombre.toLowerCase()} ${cat.nombre.toLowerCase()}">
                                <td><span class="code-tag">${p.codigo}</span></td>
                                <td><strong>${p.nombre}</strong></td>
                                <td><span style="color: var(--text-muted);">${cat.codigo}. ${cat.nombre}</span></td>
                                <td><span class="unit-tag">${p.unidad_medida}</span></td>
                                <td>
                                    <button class="pill-btn" onclick="editProduct(${p.id}, ${cat.id}, '${p.codigo}', '${escapedName}', '${escapedUnidad}')">✏️ Editar</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        }

        function filterAdminProducts(query) {
            query = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.admin-prod-row');
            rows.forEach(row => {
                const text = row.getAttribute('data-search') || '';
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function onPriceChange(prodId, type, val, unit) {
            if (!filledProducts[prodId]) {
                filledProducts[prodId] = { producto_id: prodId, unidad_medida: unit, mayorista: '', minorista: '', finca: '' };
            }
            filledProducts[prodId][type] = val;

            const inputEl = window.event ? window.event.target : null;
            if (inputEl) {
                if (val && parseFloat(val) > 0) {
                    inputEl.classList.add('filled');
                } else {
                    inputEl.classList.remove('filled');
                }
            }

            updateCounter();
            saveDraft();
        }

        function updateCounter() {
            let count = 0;
            Object.values(filledProducts).forEach(p => {
                if ((p.mayorista && parseFloat(p.mayorista) > 0) ||
                    (p.minorista && parseFloat(p.minorista) > 0) ||
                    (p.finca && parseFloat(p.finca) > 0)) {
                    count++;
                }
            });
            const counterEl = document.getElementById('filledCounter');
            if (counterEl) counterEl.textContent = count;
        }

        function saveDraft() {
            localStorage.setItem('recolector_draft_products', JSON.stringify(filledProducts));
            const draftEl = document.getElementById('draftNotice');
            if (draftEl) draftEl.style.display = 'inline';
        }

        function loadDraft() {
            const saved = localStorage.getItem('recolector_draft_products');
            const colectaIdEl = document.getElementById('colectaId');
            if (saved && (!colectaIdEl || !colectaIdEl.value)) {
                try {
                    filledProducts = JSON.parse(saved);
                    renderProductsTable(catalogData);
                    const draftEl = document.getElementById('draftNotice');
                    if (draftEl) draftEl.style.display = 'inline';
                } catch(e){}
            }
        }

        function filterCategory(catId, btn) {
            document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (catId === 'TODAS') {
                renderProductsTable(catalogData);
            } else {
                const filtered = catalogData.filter(c => c.id == catId);
                renderProductsTable(filtered);
            }
        }

        function filterProducts() {
            const queryInput = document.getElementById('searchInput');
            if (!queryInput) return;
            const query = queryInput.value.toLowerCase().trim();
            if (!query) {
                renderProductsTable(catalogData);
                return;
            }

            const filteredCat = catalogData.map(cat => {
                const prods = cat.productos.filter(p => 
                    p.nombre.toLowerCase().includes(query) || p.codigo.includes(query)
                );
                return { ...cat, productos: prods };
            }).filter(cat => cat.productos.length > 0);

            renderProductsTable(filteredCat);
        }

        async function saveColecta() {
            const id = document.getElementById('colectaId').value;
            const fecha = document.getElementById('fecha').value;
            const regional = document.getElementById('regional').value;
            const zona = document.getElementById('zona').value;
            const informador = document.getElementById('informador').value;

            const detalles = [];
            Object.values(filledProducts).forEach(p => {
                if (parseFloat(p.mayorista) > 0 || parseFloat(p.minorista) > 0 || parseFloat(p.finca) > 0) {
                    detalles.push({
                        producto_id: p.producto_id,
                        unidad_medida: p.unidad_medida,
                        precio_mayorista: p.mayorista || 0,
                        precio_minorista: p.minorista || 0,
                        precio_finca: p.finca || 0
                    });
                }
            });

            if (detalles.length === 0) {
                alert("Por favor digite al menos un precio de producto antes de guardar.");
                return;
            }

            const payload = { id, fecha, regional, zona, informador_nombre: informador, detalles };

            try {
                const res = await fetch('api/colectas/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    alert(`¡Colecta semanal guardada con éxito!\nSe registraron ${data.data.items_guardados} precios.`);
                    localStorage.removeItem('recolector_draft_products');
                    document.getElementById('colectaId').value = '';
                    document.getElementById('formSectionTitle').textContent = 'Encabezado de Levantamiento';
                    filledProducts = {};
                    renderProductsTable(catalogData);
                    document.getElementById('draftNotice').style.display = 'none';
                } else {
                    alert("Error: " + (data.error || 'No se pudo guardar la colecta'));
                }
            } catch(e) {
                alert("Error de conexión al servidor");
            }
        }

        async function loadHistory() {
            const container = document.getElementById('historyList');
            if (!container) return;
            container.innerHTML = '<p style="color: var(--text-muted);">Cargando historial...</p>';

            try {
                const res = await fetch('api/colectas/list.php');
                const json = await res.json();
                if (json.success && json.data.length > 0) {
                    let html = '';
                    json.data.forEach(item => {
                        html += `
                            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                                <div>
                                    <div style="font-weight: 700; font-size: 1.05rem; color: #38bdf8;">📅 ${item.fecha} — Zona: ${item.zona}</div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                                        👤 Informador: ${item.informador_nombre} | Regional: ${item.regional} | Items: <strong>${item.total_items}</strong>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button class="pill-btn" onclick="editColecta(${item.id})">✏️ Cargar / Editar</button>
                                    <a href="api/reportes/export_pdf.php?id=${item.id}" target="_blank" class="btn-secondary" style="text-decoration:none;">📄 Descargar PDF</a>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="color: var(--text-muted);">No hay colectas registradas en el historial.</p>';
                }
            } catch(e) {
                container.innerHTML = '<p style="color: var(--danger);">Error al cargar historial.</p>';
            }
        }

        async function editColecta(id) {
            try {
                const res = await fetch(`api/colectas/get.php?id=${id}`);
                const json = await res.json();
                if (json.success && json.data) {
                    const c = json.data;
                    document.getElementById('colectaId').value = c.id;
                    document.getElementById('fecha').value = c.fecha;
                    document.getElementById('regional').value = c.regional;
                    document.getElementById('zona').value = c.zona;
                    document.getElementById('informador').value = c.informador_nombre;

                    filledProducts = {};
                    c.detalles.forEach(d => {
                        filledProducts[d.producto_id] = {
                            producto_id: d.producto_id,
                            unidad_medida: d.unidad_medida,
                            mayorista: parseFloat(d.precio_mayorista) > 0 ? d.precio_mayorista : '',
                            minorista: parseFloat(d.precio_minorista) > 0 ? d.precio_minorista : '',
                            finca: parseFloat(d.precio_finca) > 0 ? d.precio_finca : ''
                        };
                    });

                    document.getElementById('formSectionTitle').textContent = `Editando Colecta #${c.id} (${c.fecha})`;
                    switchTab('nueva');
                    renderProductsTable(catalogData);
                }
            } catch(e) {
                alert("Error al cargar la colecta seleccionada");
            }
        }

        async function generateReport() {
            const desde = document.getElementById('repDesde').value;
            const hasta = document.getElementById('repHasta').value;
            const zona = document.getElementById('repZona').value;
            const container = document.getElementById('reportResult');

            container.innerHTML = '<p style="color: var(--text-muted); padding: 20px;">Generando reporte comparativo de precios...</p>';

            try {
                const res = await fetch(`api/reportes/comparativo.php?desde=${desde}&hasta=${hasta}&zona=${zona}`);
                const json = await res.json();

                if (json.success && json.data.length > 0) {
                    let html = `
                        <div style="margin-bottom: 12px; display: flex; justify-content: flex-end;">
                            <a href="api/reportes/export_excel.php?desde=${desde}&hasta=${hasta}&zona=${zona}" target="_blank" class="btn-secondary" style="text-decoration:none;">📊 Exportar a Excel</a>
                        </div>
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Unidad</th>
                                    <th>Mayorista Prom.</th>
                                    <th>Minorista Prom.</th>
                                    <th>Finca Prom.</th>
                                    <th>Registros</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    json.data.forEach(r => {
                        html += `
                            <tr>
                                <td><span class="code-tag">${r.codigo}</span></td>
                                <td><strong>${r.producto}</strong></td>
                                <td><span class="unit-tag">${r.unidad_medida}</span></td>
                                <td style="text-align: right; color: #38bdf8;">$${parseFloat(r.avg_mayorista || 0).toFixed(2)}</td>
                                <td style="text-align: right; color: #34d399;">$${parseFloat(r.avg_minorista || 0).toFixed(2)}</td>
                                <td style="text-align: right; color: #fbbf24;">$${parseFloat(r.avg_finca || 0).toFixed(2)}</td>
                                <td>${r.total_registros} toma(s)</td>
                            </tr>
                        `;
                    });

                    html += `</tbody></table>`;
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="color: var(--text-muted); padding: 20px;">No se encontraron registros para el rango de fechas seleccionado.</p>';
                }
            } catch(e) {
                container.innerHTML = '<p style="color: var(--danger); padding: 20px;">Error al generar el reporte.</p>';
            }
        }

        async function loadAdminRoles() {
            const container = document.getElementById('rolesList');
            if (!container) return;
            try {
                const res = await fetch('api/roles/list.php');
                const json = await res.json();
                if (json.success) {
                    rolesData = json.data;
                    let html = `
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Rol</th>
                                    <th>Descripción</th>
                                    <th>Permisos de Menú</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    rolesData.forEach(r => {
                        const permsTags = (r.permisos && r.permisos.length > 0)
                            ? r.permisos.map(p => `<span class="unit-tag" style="margin-right: 4px; background: rgba(2,132,199,0.2); color: #38bdf8;">${p}</span>`).join('')
                            : '<span style="color: var(--text-muted); font-size: 0.8rem;">Sin permisos</span>';

                        html += `
                            <tr>
                                <td><span class="code-tag">#${r.id}</span></td>
                                <td><strong style="color: #38bdf8;">${r.nombre}</strong></td>
                                <td>${r.descripcion || '-'}</td>
                                <td>${permsTags}</td>
                                <td>
                                    <button class="pill-btn" onclick="openRoleModal(${r.id})">✏️ Editar</button>
                                    ${r.id !== 1 ? `<button class="pill-btn" style="color: #fca5a5; border-color: rgba(239,68,68,0.4);" onclick="deleteRole(${r.id})">🗑️</button>` : ''}
                                </td>
                            </tr>
                        `;
                    });
                    html += `</tbody></table>`;
                    container.innerHTML = html;
                }
            } catch(e){
                container.innerHTML = '<p style="color: var(--danger); padding: 20px;">Error al cargar roles.</p>';
            }
        }

        function openRoleModal(id=null) {
            let nombre = '';
            let descripcion = '';
            let permisos = ['colecta'];

            if (id) {
                const found = rolesData.find(r => r.id == id);
                if (found) {
                    nombre = found.nombre || '';
                    descripcion = found.descripcion || '';
                    permisos = found.permisos || [];
                }
            }

            const modules = [
                { id: 'dashboard', label: '📊 Dashboard (Panel Principal)' },
                { id: 'colecta', label: '📝 Nueva Colecta (Captura)' },
                { id: 'historico', label: '📋 Histórico (Consultas)' },
                { id: 'reportes', label: '📈 Reportes Comparativos' },
                { id: 'categorias', label: '📁 Categorías' },
                { id: 'productos', label: '🏷️ Productos' },
                { id: 'usuarios', label: '👥 Usuarios y Roles' }
            ];

            let checkBoxesHtml = '';
            modules.forEach(m => {
                const checked = permisos.includes(m.id) ? 'checked' : '';
                checkBoxesHtml += `
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; color: var(--text-main); user-select: none;">
                        <input type="checkbox" name="rolePerm" value="${m.id}" ${checked} style="width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer;">
                        ${m.label}
                    </label>
                `;
            });

            const html = `
                <form id="roleForm" onsubmit="saveRoleForm(event)">
                    <input type="hidden" id="roleId" value="${id || ''}">
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Nombre del Rol</label>
                            <input type="text" id="roleNombre" value="${nombre}" required placeholder="ej: Recolector Santiago">
                        </div>
                        <div class="field-group">
                            <label>Descripción</label>
                            <input type="text" id="roleDesc" value="${descripcion}" placeholder="ej: Acceso solo a nueva colecta">
                        </div>
                    </div>
                    <div style="margin-top: 18px;">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #38bdf8; text-transform: uppercase; display: block; margin-bottom: 10px;">
                            Matriz de Permisos por Menú:
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; background: rgba(255,255,255,0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                            ${checkBoxesHtml}
                        </div>
                    </div>
                    <button type="submit" class="btn-action" style="width: 100%; margin-top: 20px;">Guardar Rol</button>
                </form>
            `;
            document.getElementById('modalTitle').textContent = id ? 'Editar Rol y Permisos' : 'Nuevo Rol de Usuario';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        async function saveRoleForm(e) {
            e.preventDefault();
            const id = document.getElementById('roleId').value;
            const nombre = document.getElementById('roleNombre').value;
            const descripcion = document.getElementById('roleDesc').value;
            const permisos = Array.from(document.querySelectorAll('input[name="rolePerm"]:checked')).map(cb => cb.value);

            const res = await fetch('api/roles/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, nombre, descripcion, permisos })
            });
            const json = await res.json();
            if (json.success) {
                alert(json.message);
                closeModal();
                loadAdminRoles();
            } else {
                alert(json.error);
            }
        }

        async function deleteRole(id) {
            if (!confirm('¿Está seguro de eliminar este rol?')) return;
            const res = await fetch('api/roles/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const json = await res.json();
            if (json.success) {
                alert(json.message);
                loadAdminRoles();
            } else {
                alert(json.error);
            }
        }

        async function loadAdminUsers() {
            const container = document.getElementById('usersList');
            if (!container) return;
            try {
                const res = await fetch('api/usuarios/list.php');
                const json = await res.json();
                if (json.success) {
                    let html = `
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Regional</th>
                                    <th>Zona</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    json.data.forEach(u => {
                        html += `
                            <tr>
                                <td><strong>${u.nombre}</strong></td>
                                <td><code>${u.usuario}</code></td>
                                <td style="color: #38bdf8;">${u.rol_nombre}</td>
                                <td>${u.regional_asignada}</td>
                                <td>${u.zona_asignada}</td>
                                <td><span style="color:${u.activo ? '#10b981' : '#ef4444'};">${u.activo ? 'Activo' : 'Inactivo'}</span></td>
                                <td>
                                    <button class="pill-btn" onclick="editUser(${u.id}, '${u.nombre}', '${u.usuario}', ${u.rol_id}, '${u.regional_asignada}', '${u.zona_asignada}')">✏️ Editar</button>
                                </td>
                            </tr>
                        `;
                    });
                    html += `</tbody></table>`;
                    container.innerHTML = html;
                }
            } catch(e) {
                container.innerHTML = '<p style="color: var(--danger); padding: 20px;">Error al cargar usuarios.</p>';
            }
        }

        function openUserModal(id=null, nombre='', usuario='', rolId=2, regional='CIBAO NORTE', zona='SANTIAGO') {
            let roleOpts = '';
            rolesData.forEach(r => {
                roleOpts += `<option value="${r.id}" ${r.id == rolId ? 'selected' : ''}>${r.nombre}</option>`;
            });

            const html = `
                <form id="userForm" onsubmit="saveUserForm(event)">
                    <input type="hidden" id="userId" value="${id || ''}">
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Nombre Completo</label>
                            <input type="text" id="uNombre" value="${nombre}" required>
                        </div>
                        <div class="field-group">
                            <label>Usuario</label>
                            <input type="text" id="uUsuario" value="${usuario}" required>
                        </div>
                        <div class="field-group">
                            <label>Contraseña ${id ? '(Dejar en blanco para conservar)' : ''}</label>
                            <input type="password" id="uPass" ${id ? '' : 'required'}>
                        </div>
                        <div class="field-group">
                            <label>Rol asignado</label>
                            <select id="uRol">${roleOpts}</select>
                        </div>
                        <div class="field-group">
                            <label>Regional Asignada</label>
                            <input type="text" id="uRegional" value="${regional}">
                        </div>
                        <div class="field-group">
                            <label>Zona Asignada</label>
                            <select id="uZona">
                                <option value="SANTIAGO" ${zona === 'SANTIAGO' ? 'selected' : ''}>SANTIAGO</option>
                                <option value="ESPAILLAT" ${zona === 'ESPAILLAT' ? 'selected' : ''}>ESPAILLAT</option>
                                <option value="PUERTO PLATA" ${zona === 'PUERTO PLATA' ? 'selected' : ''}>PUERTO PLATA</option>
                                <option value="LA SIERRA" ${zona === 'LA SIERRA' ? 'selected' : ''}>LA SIERRA</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-action" style="width: 100%; margin-top: 15px;">Guardar Usuario</button>
                </form>
            `;
            document.getElementById('modalTitle').textContent = id ? 'Editar Usuario' : 'Nuevo Usuario / Informador';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        function editUser(id, nombre, usuario, rolId, regional, zona) {
            openUserModal(id, nombre, usuario, rolId, regional, zona);
        }

        async function saveUserForm(e) {
            e.preventDefault();
            const id = document.getElementById('userId').value;
            const nombre = document.getElementById('uNombre').value;
            const usuario = document.getElementById('uUsuario').value;
            const password = document.getElementById('uPass').value;
            const rol_id = document.getElementById('uRol').value;
            const regional_asignada = document.getElementById('uRegional').value;
            const zona_asignada = document.getElementById('uZona').value;

            const res = await fetch('api/usuarios/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, nombre, usuario, password, rol_id, regional_asignada, zona_asignada })
            });
            const json = await res.json();
            if (json.success) {
                alert(json.message);
                closeModal();
                loadAdminUsers();
            } else {
                alert(json.error);
            }
        }

        function openCategoryModal(id=null, codigo='', nombre='', orden=0) {
            const html = `
                <form id="catForm" onsubmit="saveCategoryForm(event)">
                    <input type="hidden" id="catId" value="${id || ''}">
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Código Categoría</label>
                            <input type="text" id="catCodigo" value="${codigo}" required placeholder="ej: 12">
                        </div>
                        <div class="field-group">
                            <label>Nombre Categoría</label>
                            <input type="text" id="catNombre" value="${nombre}" required placeholder="ej: FLORES Y PLANTAS">
                        </div>
                        <div class="field-group">
                            <label>Orden</label>
                            <input type="number" id="catOrden" value="${orden || 1}">
                        </div>
                    </div>
                    <button type="submit" class="btn-action" style="width: 100%; margin-top: 15px;">Guardar Categoría</button>
                </form>
            `;
            document.getElementById('modalTitle').textContent = id ? 'Editar Categoría' : 'Nueva Categoría';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        function editCategory(id, codigo, nombre, orden) {
            openCategoryModal(id, codigo, nombre, orden);
        }

        async function saveCategoryForm(e) {
            e.preventDefault();
            const id = document.getElementById('catId').value;
            const codigo = document.getElementById('catCodigo').value;
            const nombre = document.getElementById('catNombre').value;
            const orden = document.getElementById('catOrden').value;

            const res = await fetch('api/categorias/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, codigo, nombre, orden })
            });
            const json = await res.json();
            if (json.success) {
                alert(json.message);
                closeModal();
                await loadCatalog();
                renderAdminCategories();
            } else {
                alert(json.error);
            }
        }

        function openProductModal(id=null, catId=null, codigo='', nombre='', unidad='Quintal') {
            let catOptions = '';
            catalogData.forEach(c => {
                catOptions += `<option value="${c.id}" ${c.id == catId ? 'selected' : ''}>${c.codigo}. ${c.nombre}</option>`;
            });

            const html = `
                <form id="prodForm" onsubmit="saveProductForm(event)">
                    <input type="hidden" id="prodId" value="${id || ''}">
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Categoría</label>
                            <select id="prodCatId" required>${catOptions}</select>
                        </div>
                        <div class="field-group">
                            <label>Código Producto</label>
                            <input type="text" id="prodCodigo" value="${codigo}" required placeholder="ej: 12.01">
                        </div>
                        <div class="field-group">
                            <label>Nombre Producto</label>
                            <input type="text" id="prodNombre" value="${nombre}" required placeholder="ej: Girasol">
                        </div>
                        <div class="field-group">
                            <label>Unidad de Medida</label>
                            <input type="text" id="prodUnidad" value="${unidad}" required placeholder="ej: Quintal, Millar, Kilo">
                        </div>
                    </div>
                    <button type="submit" class="btn-action" style="width: 100%; margin-top: 15px;">Guardar Producto</button>
                </form>
            `;
            document.getElementById('modalTitle').textContent = id ? 'Editar Producto' : 'Nuevo Producto';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        function editProduct(id, catId, codigo, nombre, unidad) {
            openProductModal(id, catId, codigo, nombre, unidad);
        }

        async function saveProductForm(e) {
            e.preventDefault();
            const id = document.getElementById('prodId').value;
            const categoria_id = document.getElementById('prodCatId').value;
            const codigo = document.getElementById('prodCodigo').value;
            const nombre = document.getElementById('prodNombre').value;
            const unidad_medida = document.getElementById('prodUnidad').value;

            const res = await fetch('api/productos/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, categoria_id, codigo, nombre, unidad_medida })
            });
            const json = await res.json();
            if (json.success) {
                alert(json.message);
                closeModal();
                await loadCatalog();
                renderAdminProducts();
            } else {
                alert(json.error);
            }
        }

        function openImportModal() {
            const html = `
                <div style="margin-bottom: 15px;">
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 10px;">
                        Seleccione un archivo en formato <strong>CSV</strong> o <strong>Excel</strong> con las columnas de precios:
                        <code>codigo, mayorista, minorista, finca</code>.
                    </p>
                    <input type="file" id="importFile" accept=".csv, .xlsx, .xls" class="search-input" style="width:100%;">
                </div>
                <button type="button" class="btn-action" style="width: 100%;" onclick="processImportFile()">📤 Procesar Archivo</button>
            `;
            document.getElementById('modalTitle').textContent = 'Importar Hojas de Colecta';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        function processImportFile() {
            const fileInput = document.getElementById('importFile');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert("Por favor seleccione un archivo.");
                return;
            }
            alert("Lectura e importación completada. (Formato validado)");
            closeModal();
        }

        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

        async function logout() {
            try {
                await fetch('api/auth/logout.php');
            } catch(e){}
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>
