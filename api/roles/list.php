<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

$stmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($roles as &$rol) {
    $stmtPerms = $pdo->prepare("SELECT permiso FROM role_permissions WHERE rol_id = :rol_id");
    $stmtPerms->execute([':rol_id' => $rol['id']]);
    $rol['permisos'] = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
}
unset($rol);

jsonSuccess($roles);
