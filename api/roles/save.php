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
$permisos    = isset($data['permisos']) && is_array($data['permisos']) ? $data['permisos'] : [];

if (empty($nombre)) {
    jsonError('El nombre del rol es obligatorio');
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

try {
    $pdo->beginTransaction();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE roles SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':id' => $id]);
        $rolId = $id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO roles (nombre, descripcion) VALUES (:nombre, :descripcion)");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);
        $rolId = (int)$pdo->lastInsertId();
    }

    // Reemplazar permisos
    $delStmt = $pdo->prepare("DELETE FROM role_permissions WHERE rol_id = :rol_id");
    $delStmt->execute([':rol_id' => $rolId]);

    if (!empty($permisos)) {
        $insStmt = $pdo->prepare("INSERT INTO role_permissions (rol_id, permiso) VALUES (:rol_id, :permiso)");
        foreach ($permisos as $p) {
            $pClean = trim($p);
            if (!empty($pClean)) {
                $insStmt->execute([':rol_id' => $rolId, ':permiso' => $pClean]);
            }
        }
    }

    $pdo->commit();
    jsonSuccess(['id' => $rolId], $id ? 'Rol actualizado con éxito' : 'Rol creado con éxito');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('Error al guardar rol: ' . $e->getMessage(), 500);
}
