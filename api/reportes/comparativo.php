<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$fechaDesde = trim($_GET['desde'] ?? $_GET['fecha_desde'] ?? date('Y-m-01'));
$fechaHasta = trim($_GET['hasta'] ?? $_GET['fecha_hasta'] ?? date('Y-m-d'));
$zona       = trim($_GET['zona'] ?? '');

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

$where = ["c.fecha BETWEEN :desde AND :hasta"];
$params = [':desde' => $fechaDesde, ':hasta' => $fechaHasta];

if (!empty($zona)) {
    $where[] = "c.zona = :zona";
    $params[':zona'] = $zona;
}

$sql = "
    SELECT 
        p.id,
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

jsonSuccess($reporte);
