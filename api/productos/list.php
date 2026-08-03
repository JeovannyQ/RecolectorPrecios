<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

// Obtener todas las categorías activas
$stmtCat = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY orden ASC");
$categorias = $stmtCat->fetchAll();

// Obtener todos los productos activos
$stmtProd = $pdo->query("SELECT * FROM productos WHERE activo = 1 ORDER BY orden ASC");
$productos = $stmtProd->fetchAll();

// Agrupar productos por categoría
$prodMap = [];
foreach ($productos as $p) {
    $prodMap[$p['categoria_id']][] = $p;
}

$resultado = [];
foreach ($categorias as $c) {
    $c['productos'] = $prodMap[$c['id']] ?? [];
    $resultado[] = $c;
}

jsonSuccess($resultado);
