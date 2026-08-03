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

        .category-row td {
            background: rgba(2, 132, 199, 0.08);
            color: #38bdf8;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
        }

        .code-tag {
            background: rgba(255, 255, 255, 0.06);
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .unit-tag {
            font-size: 0.82rem;
            color: #10b981;
            font-weight: 500;
        }

        .price-input {
            width: 110px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 10px;
            color: #ffffff;
            font-size: 0.95rem;
            text-align: right;
            font-weight: 600;
            outline: none;
            transition: all 0.2s;
        }

        .price-input:focus {
            border-color: var(--accent);
            background: #243552;
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .price-input.filled {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        /* Bottom Bar */
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #0d1424;
            border-top: 1px solid var(--border-color);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 99;
            box-shadow: 0 -10px 25px rgba(0,0,0,0.5);
        }

        .counter-badge { font-size: 0.9rem; color: var(--text-muted); }
        .counter-badge strong { color: #10b981; font-size: 1.1rem; }

        .btn-action {
            background: linear-gradient(135deg, #0284c7, #10b981);
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action:hover { transform: translateY(-2px); }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-secondary:hover { background: rgba(255, 255, 255, 0.1); }

        /* History & Admin Tables */
        .history-list { display: flex; flex-direction: column; gap: 12px; }

        .history-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 100%;
            max-width: 850px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
        .btn-close { background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
            </div>
            <div>
                <div class="brand-title">Recolector de Precios</div>
                <div class="brand-sub">Sistema de Monitoreo Semanal</div>
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
        <div class="nav-tabs">
            <button class="tab-btn active" onclick="switchTab('nueva')">
                📝 Nueva Colecta Semanal
            </button>
            <button class="tab-btn" onclick="switchTab('historico')">
                📊 Histórico de Levantamientos
            </button>
            <button class="tab-btn" onclick="switchTab('reportes')">
                📈 Reportes & Tendencias
            </button>
            <?php if ($isAdmin): ?>
            <button class="tab-btn" onclick="switchTab('admin_categorias')">
                📁 Categorías
            </button>
            <button class="tab-btn" onclick="switchTab('admin_productos')">
                🏷️ Productos
            </button>
            <button class="tab-btn" onclick="switchTab('admin_usuarios')">
                👥 Usuarios y Roles
            </button>
            <?php endif; ?>
        </div>

        <!-- TAB 1: NUEVA COLECTA -->
        <div id="tabNueva" class="tab-content">
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

        <!-- TAB 2: HISTORICO -->
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

        <!-- TAB 3: REPORTES -->
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

        <?php if ($isAdmin): ?>
        <!-- TAB 4: MANTENIMIENTO CATEGORIAS -->
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

        <!-- TAB 5: MANTENIMIENTO PRODUCTOS -->
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

        <!-- TAB 6: USUARIOS Y ROLES -->
        <div id="tabAdminUsuarios" class="tab-content" style="display: none;">
            <div class="section-card" style="margin-bottom: 24px;">
                <div class="section-header">
                    <h2 class="section-title">Gestión de Roles del Sistema</h2>
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
    <div class="bottom-bar" id="bottomBar">
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

        document.addEventListener('DOMContentLoaded', () => {
            loadCatalog();
            if (isAdmin) loadRoles();
        });

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');

            document.getElementById('bottomBar').style.display = (tab === 'nueva') ? 'flex' : 'none';

            if (tab === 'nueva') {
                document.getElementById('tabNueva').style.display = 'block';
            } else if (tab === 'historico') {
                document.getElementById('tabHistorico').style.display = 'block';
                loadHistory();
            } else if (tab === 'reportes') {
                document.getElementById('tabReportes').style.display = 'block';
            } else if (tab === 'admin_categorias') {
                document.getElementById('tabAdminCategorias').style.display = 'block';
                renderAdminCategories();
            } else if (tab === 'admin_productos') {
                document.getElementById('tabAdminProductos').style.display = 'block';
                renderAdminProducts();
            } else if (tab === 'admin_usuarios') {
                document.getElementById('tabAdminUsuarios').style.display = 'block';
                loadAdminRoles();
                loadAdminUsers();
            }
            if (event && event.currentTarget) event.currentTarget.classList.add('active');
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
            let html = `<button class="pill-btn active" onclick="filterCategory('TODAS', this)">Todas las Categorías</button>`;
            catalogData.forEach(cat => {
                html += `<button class="pill-btn" onclick="filterCategory('${cat.id}', this)">${cat.codigo}. ${cat.nombre}</button>`;
            });
            container.innerHTML = html;
        }

        function renderProductsTable(categories) {
            const tbody = document.getElementById('productsTableBody');
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
                            <button class="pill-btn" onclick="openCategoryModal(${c.id}, '${c.codigo}', '${escapedName}', ${c.orden})">✏️ Editar</button>
                        </td>
                    </tr>
                `;
            });
            html += `</tbody></table>`;
            container.innerHTML = html;
        }

        function renderAdminProducts() {
            const container = document.getElementById('productsAdminList');
            if (!catalogData || catalogData.length === 0) {
                container.innerHTML = '<p style="padding: 20px; color: var(--text-muted);">Cargando productos...</p>';
                return;
            }

            let html = `
                <div style="margin-bottom: 12px; padding: 0 4px;">
                    <input type="text" class="search-input" placeholder="🔍 Filtrar lista de productos..." onkeyup="filterAdminProducts(this.value)">
                </div>
                <table class="price-table" id="adminProdTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Unidad de Medida</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            catalogData.forEach(cat => {
                if (cat.productos) {
                    cat.productos.forEach(p => {
                        const escapedName = (p.nombre || '').replace(/'/g, "\\'");
                        const escapedUnit = (p.unidad_medida || '').replace(/'/g, "\\'");
                        html += `
                            <tr class="admin-prod-row" data-search="${p.codigo.toLowerCase()} ${p.nombre.toLowerCase()} ${cat.nombre.toLowerCase()}">
                                <td><span class="code-tag">${p.codigo}</span></td>
                                <td><strong>${p.nombre}</strong></td>
                                <td style="color: #38bdf8;">${cat.nombre}</td>
                                <td><span class="unit-tag">${p.unidad_medida}</span></td>
                                <td>
                                    <button class="pill-btn" onclick="openProductModal(${p.id}, ${cat.id}, '${p.codigo}', '${escapedName}', '${escapedUnit}')">✏️ Editar</button>
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

            const inputEl = event.target;
            if (val && parseFloat(val) > 0) {
                inputEl.classList.add('filled');
            } else {
                inputEl.classList.remove('filled');
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
            document.getElementById('filledCounter').textContent = count;
        }

        function saveDraft() {
            localStorage.setItem('recolector_draft_products', JSON.stringify(filledProducts));
            document.getElementById('draftNotice').style.display = 'inline';
        }

        function loadDraft() {
            const saved = localStorage.getItem('recolector_draft_products');
            if (saved && !document.getElementById('colectaId').value) {
                try {
                    filledProducts = JSON.parse(saved);
                    renderProductsTable(catalogData);
                    document.getElementById('draftNotice').style.display = 'inline';
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
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
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
            container.innerHTML = '<p style="color: var(--text-muted);">Cargando historial...</p>';

            try {
                const res = await fetch('api/colectas/list.php');
                const json = await res.json();
                if (json.success && json.data.length > 0) {
                    let html = '';
                    json.data.forEach(c => {
                        html += `
                            <div class="history-card">
                                <div class="history-info">
                                    <h3>📅 Colecta del ${c.fecha} - ${c.zona}</h3>
                                    <p>Regional: <strong>${c.regional}</strong> | Informador: <strong>${c.informador_nombre}</strong> | Precios cargados: <strong style="color: #10b981;">${c.productos_con_precio} de ${c.total_productos}</strong></p>
                                </div>
                                <div style="display:flex; gap: 8px;">
                                    <button class="btn-action" style="padding: 6px 14px; font-size: 0.85rem;" onclick="loadColectaToForm(${c.id})">✏️ Llenar / Editar Precios</button>
                                    <button class="btn-secondary" style="padding: 6px 14px; font-size: 0.85rem;" onclick="viewColectaDetails(${c.id})">🔍 Ver / Imprimir</button>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="color: var(--text-muted);">No hay colectas registradas aún.</p>';
                }
            } catch(e) {
                container.innerHTML = '<p style="color: var(--danger);">Error al cargar historial.</p>';
            }
        }

        async function loadColectaToForm(id) {
            try {
                const res = await fetch(`api/colectas/get.php?id=${id}`);
                const json = await res.json();
                if (json.success) {
                    const c = json.data;
                    document.getElementById('colectaId').value = c.id;
                    document.getElementById('fecha').value = c.fecha;
                    document.getElementById('regional').value = c.regional;
                    document.getElementById('zona').value = c.zona;
                    document.getElementById('informador').value = c.informador_nombre;

                    document.getElementById('formSectionTitle').textContent = `Editando Colecta #${c.id} del ${c.fecha}`;

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

                    renderProductsTable(catalogData);
                    switchTab('nueva');
                }
            } catch(e) {
                alert("Error al cargar la colecta en el formulario");
            }
        }

        function openImportModal() {
            const html = `
                <form id="importForm" onsubmit="submitImportFile(event)">
                    <p style="font-size:0.9rem; color: var(--text-muted); margin-bottom: 15px;">
                        Seleccione un archivo <strong>CSV</strong> (separado por comas) con la estructura: <br>
                        <code>Código, Nombre Producto, Unidad, Mayorista, Minorista, Finca</code>
                    </p>
                    <div class="field-group" style="margin-bottom: 15px;">
                        <label>Archivo CSV de Precios</label>
                        <input type="file" id="csvFile" accept=".csv" required style="padding: 8px;">
                    </div>
                    <button type="submit" class="btn-action" style="width: 100%;">📤 Subir e Importar Registros</button>
                </form>
            `;
            document.getElementById('modalTitle').textContent = 'Importar Precios desde Archivo CSV / Excel';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        async function submitImportFile(e) {
            e.preventDefault();
            const fileInput = document.getElementById('csvFile');
            if (!fileInput.files[0]) return;

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('fecha', document.getElementById('fecha').value);
            formData.append('regional', document.getElementById('regional').value);
            formData.append('zona', document.getElementById('zona').value);
            formData.append('informador', document.getElementById('informador').value);

            try {
                const res = await fetch('api/colectas/import_csv.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    alert(json.message);
                    closeModal();
                    loadColectaToForm(json.data.colecta_id);
                } else {
                    alert("Error: " + json.error);
                }
            } catch(e) {
                alert("Error durante la importación del archivo");
            }
        }

        async function loadAdminRoles() {
            const container = document.getElementById('rolesList');
            try {
                await loadRoles();
                let html = `
                    <table class="price-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Rol</th>
                                <th>Descripción</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                rolesData.forEach(r => {
                    html += `
                        <tr>
                            <td><span class="code-tag">#${r.id}</span></td>
                            <td><strong style="color: #38bdf8;">${r.nombre}</strong></td>
                            <td>${r.descripcion || '-'}</td>
                            <td>
                                <button class="pill-btn" onclick="openRoleModal(${r.id}, '${r.nombre}', '${r.descripcion || ''}')">✏️ Editar</button>
                            </td>
                        </tr>
                    `;
                });
                html += `</tbody></table>`;
                container.innerHTML = html;
            } catch(e){
                container.innerHTML = '<p style="color: var(--danger); padding: 20px;">Error al cargar roles.</p>';
            }
        }

        function openRoleModal(id=null, nombre='', descripcion='') {
            const html = `
                <form id="roleForm" onsubmit="saveRoleForm(event)">
                    <input type="hidden" id="roleId" value="${id || ''}">
                    <div class="form-grid">
                        <div class="field-group">
                            <label>Nombre del Rol</label>
                            <input type="text" id="roleNombre" value="${nombre}" required placeholder="ej: Supervisor">
                        </div>
                        <div class="field-group">
                            <label>Descripción</label>
                            <input type="text" id="roleDesc" value="${descripcion}" placeholder="ej: Acceso a reportes y supervisión">
                        </div>
                    </div>
                    <button type="submit" class="btn-action" style="width: 100%; margin-top: 15px;">Guardar Rol</button>
                </form>
            `;
            document.getElementById('modalTitle').textContent = id ? 'Editar Rol' : 'Nuevo Rol';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }

        async function saveRoleForm(e) {
            e.preventDefault();
            const id = document.getElementById('roleId').value;
            const nombre = document.getElementById('roleNombre').value;
            const descripcion = document.getElementById('roleDesc').value;

            const res = await fetch('api/roles/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, nombre, descripcion })
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

        async function loadAdminUsers() {
            const container = document.getElementById('usersList');
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

        async function viewColectaDetails(id) {
            try {
                const res = await fetch(`api/colectas/get.php?id=${id}`);
                const json = await res.json();
                if (json.success) {
                    const c = json.data;
                    document.getElementById('modalTitle').textContent = `Colecta del ${c.fecha} (${c.zona})`;

                    let tableHtml = `
                        <div style="margin-bottom: 15px; font-size: 0.9rem; color: var(--text-muted);">
                            <strong>Regional:</strong> ${c.regional} &nbsp;|&nbsp; 
                            <strong>Informador:</strong> ${c.informador_nombre} &nbsp;|&nbsp;
                            <strong>Fecha:</strong> ${c.fecha}
                        </div>
                        <table class="price-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Cód.</th>
                                    <th>Categoría</th>
                                    <th>Producto</th>
                                    <th>Unidad</th>
                                    <th style="text-align: right;">Mayorista</th>
                                    <th style="text-align: right;">Minorista</th>
                                    <th style="text-align: right;">Finca</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    c.detalles.forEach(d => {
                        tableHtml += `
                            <tr>
                                <td><span class="code-tag">${d.producto_codigo}</span></td>
                                <td style="color: #38bdf8;">${d.categoria_nombre}</td>
                                <td><strong>${d.producto_nombre}</strong></td>
                                <td><span class="unit-tag">${d.unidad_medida}</span></td>
                                <td style="text-align: right; font-weight:600;">$${parseFloat(d.precio_mayorista).toFixed(2)}</td>
                                <td style="text-align: right; font-weight:600;">$${parseFloat(d.precio_minorista).toFixed(2)}</td>
                                <td style="text-align: right; font-weight:600;">$${parseFloat(d.precio_finca).toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    tableHtml += `</tbody></table>`;
                    document.getElementById('modalBody').innerHTML = tableHtml;
                    document.getElementById('detailModal').style.display = 'flex';
                }
            } catch(e) {
                alert("Error al cargar detalles de la colecta");
            }
        }

        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

        async function logout() {
            if (confirm("¿Deseas cerrar sesión?")) {
                await fetch('api/auth/logout.php');
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>
