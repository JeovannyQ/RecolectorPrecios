<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();
$user = getLoggedInUser();
if ($user['rol_nombre'] !== 'Administrador') {
    jsonError('Acceso denegado. Solo administradores.', 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id     = !empty($data['id']) ? (int)$data['id'] : null;
$codigo = trim($data['codigo'] ?? '');
$nombre = trim($data['nombre'] ?? '');
$orden  = (int)($data['orden'] ?? 0);
$activo = isset($data['activo']) ? (int)$data['activo'] : 1;

if (empty($codigo) || empty($nombre)) {
    jsonError('Código y Nombre de categoría son obligatorios');
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE categorias SET codigo = :codigo, nombre = :nombre, orden = :orden, activo = :activo WHERE id = :id");
        $stmt->execute([':codigo' => $codigo, ':nombre' => $nombre, ':orden' => $orden, ':activo' => $activo, ':id' => $id]);
        jsonSuccess(['id' => $id], 'Categoría actualizada con éxito');
    } else {
        $stmt = $pdo->prepare("INSERT INTO categorias (codigo, nombre, orden, activo) VALUES (:codigo, :nombre, :orden, :activo)");
        $stmt->execute([':codigo' => $codigo, ':nombre' => $nombre, ':orden' => $orden, ':activo' => $activo]);
        jsonSuccess(['id' => $pdo->lastInsertId()], 'Categoría creada con éxito');
    }
} catch (Exception $e) {
    jsonError('Error al guardar categoría: ' . $e->getMessage(), 500);
}
