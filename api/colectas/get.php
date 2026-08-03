<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) jsonError('ID de colecta no proporcionado');

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

$stmt = $pdo->prepare("SELECT * FROM colectas WHERE id = ?");
$stmt->execute([$id]);
$colecta = $stmt->fetch();

if (!$colecta) jsonError('Colecta no encontrada', 404);

// Cargar detalles
$stmtDet = $pdo->prepare("
    SELECT cd.*, p.codigo as producto_codigo, p.nombre as producto_nombre, p.categoria_id, cat.nombre as categoria_nombre
    FROM colecta_detalles cd
    INNER JOIN productos p ON cd.producto_id = p.id
    INNER JOIN categorias cat ON p.categoria_id = cat.id
    WHERE cd.colecta_id = ?
    ORDER BY p.codigo ASC
");
$stmtDet->execute([$id]);
$colecta['detalles'] = $stmtDet->fetchAll();

jsonSuccess($colecta);
