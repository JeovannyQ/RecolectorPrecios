<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();
$user = getLoggedInUser();
if ($user['rol_nombre'] !== 'Administrador') {
    jsonError('Acceso denegado. Solo administradores.', 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id               = !empty($data['id']) ? (int)$data['id'] : null;
$nombre           = trim($data['nombre'] ?? '');
$usuario          = trim($data['usuario'] ?? '');
$password         = trim($data['password'] ?? '');
$rol_id           = (int)($data['rol_id'] ?? 2);
$regional_asignada= trim($data['regional_asignada'] ?? 'CIBAO NORTE');
$zona_asignada    = trim($data['zona_asignada'] ?? 'SANTIAGO');
$activo           = isset($data['activo']) ? (int)$data['activo'] : 1;

if (empty($nombre) || empty($usuario)) {
    jsonError('Nombre y Usuario son obligatorios');
}

if (!$id && empty($password)) {
    jsonError('La contraseña es obligatoria para usuarios nuevos');
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

try {
    if ($id) {
        if (!empty($password)) {
            $passHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre = :nombre, usuario = :usuario, password_hash = :password_hash, 
                    rol_id = :rol_id, regional_asignada = :regional_asignada, 
                    zona_asignada = :zona_asignada, activo = :activo 
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre'           => $nombre,
                ':usuario'          => $usuario,
                ':password_hash'    => $passHash,
                ':rol_id'           => $rol_id,
                ':regional_asignada'=> $regional_asignada,
                ':zona_asignada'    => $zona_asignada,
                ':activo'           => $activo,
                ':id'               => $id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre = :nombre, usuario = :usuario, rol_id = :rol_id, 
                    regional_asignada = :regional_asignada, zona_asignada = :zona_asignada, 
                    activo = :activo 
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre'           => $nombre,
                ':usuario'          => $usuario,
                ':rol_id'           => $rol_id,
                ':regional_asignada'=> $regional_asignada,
                ':zona_asignada'    => $zona_asignada,
                ':activo'           => $activo,
                ':id'               => $id
            ]);
        }
        jsonSuccess(['id' => $id], 'Usuario actualizado con éxito');
    } else {
        $passHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, usuario, password_hash, rol_id, regional_asignada, zona_asignada, activo)
            VALUES (:nombre, :usuario, :password_hash, :rol_id, :regional_asignada, :zona_asignada, :activo)
        ");
        $stmt->execute([
            ':nombre'           => $nombre,
            ':usuario'          => $usuario,
            ':password_hash'    => $passHash,
            ':rol_id'           => $rol_id,
            ':regional_asignada'=> $regional_asignada,
            ':zona_asignada'    => $zona_asignada,
            ':activo'           => $activo
        ]);
        jsonSuccess(['id' => $pdo->lastInsertId()], 'Usuario creado con éxito');
    }
} catch (Exception $e) {
    jsonError('Error al guardar usuario: ' . $e->getMessage(), 500);
}
