<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();
$user = getLoggedInUser();
if ($user['rol_nombre'] !== 'Administrador') {
    jsonError('Acceso denegado. Solo administradores.', 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id          = !empty($data['id']) ? (int)$data['id'] : null;
$nombre      = trim($data['nombre'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');

if (empty($nombre)) {
    jsonError('El nombre del rol es obligatorio');
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE roles SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':id' => $id]);
        jsonSuccess(['id' => $id], 'Rol actualizado con éxito');
    } else {
        $stmt = $pdo->prepare("INSERT INTO roles (nombre, descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);
        jsonSuccess(['id' => $pdo->lastInsertId()], 'Rol creado con éxito');
    }
} catch (Exception $e) {
    jsonError('Error al guardar rol: ' . $e->getMessage(), 500);
}
