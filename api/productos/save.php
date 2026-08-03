<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();
$user = getLoggedInUser();
if ($user['rol_nombre'] !== 'Administrador') {
    jsonError('Acceso denegado. Solo administradores.', 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id           = !empty($data['id']) ? (int)$data['id'] : null;
$categoria_id = (int)($data['categoria_id'] ?? 0);
$codigo       = trim($data['codigo'] ?? '');
$nombre       = trim($data['nombre'] ?? '');
$unidad_medida= trim($data['unidad_medida'] ?? 'Quintal');
$orden        = (int)($data['orden'] ?? 0);
$activo       = isset($data['activo']) ? (int)$data['activo'] : 1;

if (!$categoria_id || empty($codigo) || empty($nombre) || empty($unidad_medida)) {
    jsonError('Categoría, Código, Nombre y Unidad de medida son obligatorios');
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

try {
    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET categoria_id = :categoria_id, codigo = :codigo, nombre = :nombre, 
                unidad_medida = :unidad_medida, orden = :orden, activo = :activo 
            WHERE id = :id
        ");
        $stmt->execute([
            ':categoria_id'  => $categoria_id,
            ':codigo'        => $codigo,
            ':nombre'        => $nombre,
            ':unidad_medida' => $unidad_medida,
            ':orden'         => $orden,
            ':activo'        => $activo,
            ':id'            => $id
        ]);
        jsonSuccess(['id' => $id], 'Producto actualizado con éxito');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO productos (categoria_id, codigo, nombre, unidad_medida, orden, activo) 
            VALUES (:categoria_id, :codigo, :nombre, :unidad_medida, :orden, :activo)
        ");
        $stmt->execute([
            ':categoria_id'  => $categoria_id,
            ':codigo'        => $codigo,
            ':nombre'        => $nombre,
            ':unidad_medida' => $unidad_medida,
            ':orden'         => $orden,
            ':activo'        => $activo
        ]);
        jsonSuccess(['id' => $pdo->lastInsertId()], 'Producto creado con éxito');
    }
} catch (Exception $e) {
    jsonError('Error al guardar producto: ' . $e->getMessage(), 500);
}
