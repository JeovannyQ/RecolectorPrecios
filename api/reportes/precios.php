<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$fechaDesde = trim($_GET['fecha_desde'] ?? date('Y-m-01'));
$fechaHasta = trim($_GET['fecha_hasta'] ?? date('Y-m-d'));
$zona       = trim($_GET['zona'] ?? '');
$categoriaId= (int)($_GET['categoria_id'] ?? 0);

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

$where = ["c.fecha BETWEEN :desde AND :hasta"];
$params = [':desde' => $fechaDesde, ':hasta' => $fechaHasta];

if (!empty($zona)) {
    $where[] = "c.zona = :zona";
    $params[':zona'] = $zona;
}

if ($categoriaId > 0) {
    $where[] = "p.categoria_id = :cat_id";
    $params[':cat_id'] = $categoriaId;
}

$sql = "
    SELECT 
        c.fecha, c.regional, c.zona, c.informador_nombre,
        p.codigo as producto_codigo, p.nombre as producto_nombre, p.unidad_medida,
        cat.nombre as categoria_nombre,
        cd.precio_mayorista, cd.precio_minorista, cd.precio_finca
    FROM colecta_detalles cd
    INNER JOIN colectas c ON cd.colecta_id = c.id
    INNER JOIN productos p ON cd.producto_id = p.id
    INNER JOIN categorias cat ON p.categoria_id = cat.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY c.fecha DESC, cat.orden ASC, p.orden ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reporte = $stmt->fetchAll();

jsonSuccess([
    'rango'   => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
    'zona'    => $zona ?: 'TODAS',
    'totales' => count($reporte),
    'items'   => $reporte
]);
