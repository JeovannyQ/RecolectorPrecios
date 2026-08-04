<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();
$user = getLoggedInUser();
if ($user['rol_nombre'] !== 'Administrador') {
    jsonError('Acceso denegado. Solo administradores.', 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;
$id = !empty($data['id']) ? (int)$data['id'] : null;

if (!$id) {
    jsonError('ID de rol inválido');
}

if ($id === 1) {
    jsonError('No se puede eliminar el rol de Administrador principal');
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

try {
    // Verificar si el rol tiene usuarios asignados
    $chk = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = :id");
    $chk->execute([':id' => $id]);
    if ($chk->fetchColumn() > 0) {
        jsonError('No se puede eliminar este rol porque tiene usuarios asignados. Reasigne los usuarios primero.');
    }

    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = :id");
    $stmt->execute([':id' => $id]);
    jsonSuccess(null, 'Rol eliminado con éxito');
} catch (Exception $e) {
    jsonError('Error al eliminar rol: ' . $e->getMessage(), 500);
}
