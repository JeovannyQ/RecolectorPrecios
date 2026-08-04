<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de colecta no válido.");
}

$pdo = getDBConnection();
if (!$pdo) die("Error al conectar a la base de datos.");

// Obtener la colecta
$stmtColecta = $pdo->prepare("SELECT * FROM colectas WHERE id = :id");
$stmtColecta->execute([':id' => $id]);
$colecta = $stmtColecta->fetch();

if (!$colecta) {
    die("Colecta #{$id} no encontrada.");
}

// Obtener detalles de productos
$sqlDetalles = "
    SELECT 
        p.codigo as producto_codigo,
        p.nombre as producto_nombre,
        cd.unidad_medida,
        cat.nombre as categoria_nombre,
        cd.precio_mayorista,
        cd.precio_minorista,
        cd.precio_finca
    FROM colecta_detalles cd
    INNER JOIN productos p ON cd.producto_id = p.id
    INNER JOIN categorias cat ON p.categoria_id = cat.id
    WHERE cd.colecta_id = :id
    ORDER BY cat.orden ASC, p.orden ASC
";

$stmtDetalles = $pdo->prepare($sqlDetalles);
$stmtDetalles->execute([':id' => $id]);
$detalles = $stmtDetalles->fetchAll();

// Agrupar por categoría
$categoriasMap = [];
foreach ($detalles as $d) {
    $cat = $d['categoria_nombre'];
    if (!isset($categoriasMap[$cat])) {
        $categoriasMap[$cat] = [];
    }
    $categoriasMap[$cat][] = $d;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Colecta #<?php echo $colecta['id']; ?> - Recolector de Precios</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }

        .pdf-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .btn-print {
            background-color: #0284c7;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-print:hover { background-color: #0369a1; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #0284c7;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-title h1 {
            font-size: 22px;
            color: #0284c7;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }

        .header-title p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .header-meta {
            text-align: right;
            font-size: 13px;
        }

        .header-meta strong { color: #0f172a; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;

        }

        .info-val {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }

        .cat-title {
            background: #0284c7;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 4px 4px 0 0;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 10px;
        }

        th {
            background: #e2e8f0;
            color: #334155;
            text-align: left;
            padding: 8px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
        }

        td {
            padding: 7px 8px;
            border: 1px solid #e2e8f0;
        }

        tr:nth-child(even) { background-color: #f8fafc; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .price { font-weight: 600; color: #0284c7; }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #94a3b8;
        }

        @media print {
            body { background: white; padding: 0; }
            .pdf-container { box-shadow: none; padding: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="pdf-container">
    <div class="actions-bar no-print">
        <div>
            <strong style="color:#0f172a;">Vista Previa de Impresión / PDF</strong>
        </div>
        <div>
            <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
        </div>
    </div>

    <div class="header">
        <div class="header-title">
            <h1>Recolector de Precios</h1>
            <p>Sistema Semanal de Precios Agrícolas</p>
        </div>
        <div class="header-meta">
            <div><strong>Colecta #<?php echo $colecta['id']; ?></strong></div>
            <div>Fecha: <?php echo date('d/m/Y', strtotime($colecta['fecha'])); ?></div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">REGIONAL</span>
            <span class="info-val"><?php echo htmlspecialchars($colecta['regional']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">ZONA</span>
            <span class="info-val"><?php echo htmlspecialchars($colecta['zona']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">INFORMADOR</span>
            <span class="info-val"><?php echo htmlspecialchars($colecta['informador_nombre']); ?></span>
        </div>
    </div>

    <?php if (!empty($colecta['observaciones'])): ?>
    <div style="margin-bottom: 20px; padding: 10px; background: #fffbebfb; border: 1px solid #fef3c7; border-radius: 6px; font-size: 12px;">
        <strong>Observaciones:</strong> <?php echo htmlspecialchars($colecta['observaciones']); ?>
    </div>
    <?php endif; ?>

    <?php foreach ($categoriasMap as $catNombre => $items): ?>
        <div class="cat-title"><?php echo htmlspecialchars($catNombre); ?></div>
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Código</th>
                    <th style="width: 43%;">Producto</th>
                    <th style="width: 15%; text-align: center;">Unidad</th>
                    <th style="width: 10%; text-align: right;">Mayorista ($)</th>
                    <th style="width: 10%; text-align: right;">Minorista ($)</th>
                    <th style="width: 10%; text-align: right;">Finca ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="font-mono text-center"><?php echo htmlspecialchars($item['producto_codigo']); ?></td>
                    <td><strong><?php echo htmlspecialchars($item['producto_nombre']); ?></strong></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['unidad_medida']); ?></td>
                    <td class="text-right price">
                        <?php echo (float)$item['precio_mayorista'] > 0 ? '$' . number_format($item['precio_mayorista'], 2) : '-'; ?>
                    </td>
                    <td class="text-right price" style="color: #059669;">
                        <?php echo (float)$item['precio_minorista'] > 0 ? '$' . number_format($item['precio_minorista'], 2) : '-'; ?>
                    </td>
                    <td class="text-right price" style="color: #d97706;">
                        <?php echo (float)$item['precio_finca'] > 0 ? '$' . number_format($item['precio_finca'], 2) : '-'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <div class="footer">
        <div>Generado por el Sistema Recolector de Precios</div>
        <div>Fecha de Impresión: <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>
</div>

</body>
</html>
