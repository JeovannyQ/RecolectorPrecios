<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$usuario  = trim($data['usuario'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($usuario) || empty($password)) {
    jsonError('Por favor complete todos los campos');
}

$pdo = getDBConnection();
if (!$pdo) {
    jsonError('Error de conexión a la base de datos', 500);
}

$stmt = $pdo->prepare("
    SELECT u.*, r.nombre as rol_nombre 
    FROM usuarios u 
    INNER JOIN roles r ON u.rol_id = r.id 
    WHERE u.usuario = :usuario AND u.activo = 1 
    LIMIT 1
");
$stmt->execute([':usuario' => $usuario]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonError('Usuario o contraseña incorrectos');
}

// Iniciar sesión y cargar permisos
$permisos = [];
try {
    $stmtPerms = $pdo->prepare("SELECT permiso FROM role_permissions WHERE rol_id = :rol_id");
    $stmtPerms->execute([':rol_id' => $user['rol_id']]);
    $permisos = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $permisos = [];
}

if (empty($permisos)) {
    if ($user['rol_nombre'] === 'Administrador') {
        $permisos = ['dashboard', 'colecta', 'historico', 'reportes', 'categorias', 'productos', 'usuarios'];
    } else {
        $permisos = ['colecta'];
    }
}

$_SESSION['user_id']           = $user['id'];
$_SESSION['nombre']            = $user['nombre'];
$_SESSION['usuario']           = $user['usuario'];
$_SESSION['rol_id']            = $user['rol_id'];
$_SESSION['rol_nombre']        = $user['rol_nombre'];
$_SESSION['regional_asignada'] = $user['regional_asignada'];
$_SESSION['zona_asignada']     = $user['zona_asignada'];
$_SESSION['permisos']          = $permisos;

jsonSuccess([
    'id'                => $user['id'],
    'nombre'            => $user['nombre'],
    'usuario'           => $user['usuario'],
    'rol'               => $user['rol_nombre'],
    'regional_asignada' => $user['regional_asignada'],
    'zona_asignada'     => $user['zona_asignada'],
    'permisos'          => $permisos
], 'Inicio de sesión exitoso');
