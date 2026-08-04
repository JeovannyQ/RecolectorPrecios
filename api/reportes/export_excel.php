<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$fechaDesde = trim($_GET['desde'] ?? $_GET['fecha_desde'] ?? date('Y-m-01'));
$fechaHasta = trim($_GET['hasta'] ?? $_GET['fecha_hasta'] ?? date('Y-m-d'));
$zona       = trim($_GET['zona'] ?? '');

$pdo = getDBConnection();
if (!$pdo) die('Error al conectar a la base de datos');

$where = ["c.fecha BETWEEN :desde AND :hasta"];
$params = [':desde' => $fechaDesde, ':hasta' => $fechaHasta];

if (!empty($zona)) {
    $where[] = "c.zona = :zona";
    $params[':zona'] = $zona;
}

$sql = "
    SELECT 
        p.codigo,
        p.nombre as producto,
        cat.nombre as categoria,
        cd.unidad_medida,
        AVG(CASE WHEN cd.precio_mayorista > 0 THEN cd.precio_mayorista ELSE NULL END) as avg_mayorista,
        AVG(CASE WHEN cd.precio_minorista > 0 THEN cd.precio_minorista ELSE NULL END) as avg_minorista,
        AVG(CASE WHEN cd.precio_finca > 0 THEN cd.precio_finca ELSE NULL END) as avg_finca,
        COUNT(cd.id) as total_registros
    FROM colecta_detalles cd
    INNER JOIN colectas c ON cd.colecta_id = c.id
    INNER JOIN productos p ON cd.producto_id = p.id
    INNER JOIN categorias cat ON p.categoria_id = cat.id
    WHERE " . implode(" AND ", $where) . "
    GROUP BY p.id, p.codigo, p.nombre, cat.nombre, cd.unidad_medida, cat.orden, p.orden
    ORDER BY cat.orden ASC, p.orden ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reporte = $stmt->fetchAll();

$filename = "Reporte_Precios_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th { background-color: #0284c7; color: #ffffff; border: 1px solid #005b87; padding: 8px; text-align: center; }
        td { border: 1px solid #cccccc; padding: 6px; }
        .num { text-align: right; }
        .center { text-align: center; }
        .title { font-size: 16px; font-weight: bold; color: #0284c7; margin-bottom: 5px; }
        .subtitle { font-size: 12px; color: #555555; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="title">RECOLECTOR DE PRECIOS - REPORTE COMPARATIVO</div>
    <div class="subtitle">
        Período: <?php echo htmlspecialchars($fechaDesde); ?> al <?php echo htmlspecialchars($fechaHasta); ?> | 
        Zona: <?php echo htmlspecialchars($zona ?: 'TODAS'); ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Código</th>
                <th>Producto</th>
                <th>Unidad</th>
                <th>Mayorista Prom. ($)</th>
                <th>Minorista Prom. ($)</th>
                <th>Finca Prom. ($)</th>
                <th>Registros</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reporte as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['categoria']); ?></td>
                <td class="center"><?php echo htmlspecialchars($r['codigo']); ?></td>
                <td><?php echo htmlspecialchars($r['producto']); ?></td>
                <td class="center"><?php echo htmlspecialchars($r['unidad_medida']); ?></td>
                <td class="num"><?php echo number_format((float)($r['avg_mayorista'] ?? 0), 2, '.', ','); ?></td>
                <td class="num"><?php echo number_format((float)($r['avg_minorista'] ?? 0), 2, '.', ','); ?></td>
                <td class="num"><?php echo number_format((float)($r['avg_finca'] ?? 0), 2, '.', ','); ?></td>
                <td class="center"><?php echo (int)$r['total_registros']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
