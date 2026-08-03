<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

$stmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
$roles = $stmt->fetchAll();

jsonSuccess($roles);
