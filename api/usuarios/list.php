<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();
$user = getLoggedInUser();
if ($user['rol_nombre'] !== 'Administrador') {
    jsonError('Acceso denegado. Solo administradores.', 403);
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

$stmt = $pdo->query("
    SELECT u.id, u.rol_id, u.nombre, u.usuario, u.regional_asignada, u.zona_asignada, u.activo, u.created_at, r.nombre as rol_nombre
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    ORDER BY u.nombre ASC
");
$usuarios = $stmt->fetchAll();

jsonSuccess($usuarios);
