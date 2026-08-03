<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonError('No has iniciado sesión', 401);
    }
}

function getLoggedInUser() {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'                => $_SESSION['user_id'],
        'nombre'            => $_SESSION['nombre'],
        'usuario'           => $_SESSION['usuario'],
        'rol_id'            => $_SESSION['rol_id'],
        'rol_nombre'        => $_SESSION['rol_nombre'],
        'regional_asignada' => $_SESSION['regional_asignada'] ?? 'CIBAO NORTE',
        'zona_asignada'     => $_SESSION['zona_asignada'] ?? 'SANTIAGO'
    ];
}
