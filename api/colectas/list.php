<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

$user       = getLoggedInUser();
$fechaDesde = trim($_GET['fecha_desde'] ?? '');
$fechaHasta = trim($_GET['fecha_hasta'] ?? '');
$zona       = trim($_GET['zona'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($fechaDesde)) {
    $where[] = "c.fecha >= :fecha_desde";
    $params[':fecha_desde'] = $fechaDesde;
}

if (!empty($fechaHasta)) {
    $where[] = "c.fecha <= :fecha_hasta";
    $params[':fecha_hasta'] = $fechaHasta;
}

if (!empty($zona)) {
    $where[] = "c.zona = :zona";
    $params[':zona'] = $zona;
}

$sql = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM colecta_detalles cd WHERE cd.colecta_id = c.id) as total_productos,
           (SELECT COUNT(*) FROM colecta_detalles cd WHERE cd.colecta_id = c.id AND (cd.precio_mayorista > 0 OR cd.precio_minorista > 0 OR cd.precio_finca > 0)) as productos_con_precio
    FROM colectas c
    WHERE " . implode(" AND ", $where) . "
    ORDER BY c.fecha DESC, c.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$colectas = $stmt->fetchAll();

jsonSuccess($colectas);
