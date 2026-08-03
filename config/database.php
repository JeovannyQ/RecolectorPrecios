<?php
// Configuración de la Base de Datos para Recolector de Precios
date_default_timezone_set('America/Santo_Domingo');

// Detectar producción en servidor quitech01.com
$esProduccion = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'quitech01.com') !== false) 
             || (isset($_SERVER['PWD']) && strpos($_SERVER['PWD'], 'quitech') !== false)
             || (file_exists('/home/quitech'));

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: ($esProduccion ? 'quitech_cerramorfe' : 'recolector_precios'));
define('DB_USER', getenv('DB_USER') ?: ($esProduccion ? 'quitech_MiniJosue' : 'root'));
define('DB_PASS', getenv('DB_PASS') ?: ($esProduccion ? '5YkWujmGatWNXtMWHFUE' : ''));
define('DB_CHARSET', 'utf8mb4');

function getDBConnection($includeDbName = true) {
    static $pdo = null;
    if ($pdo !== null && $includeDbName) {
        return $pdo;
    }

    try {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ($includeDbName ? ";dbname=" . DB_NAME : "") . ";charset=" . DB_CHARSET;
        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        if ($includeDbName) {
            $pdo = $conn;
        }
        return $conn;
    } catch (PDOException $e) {
        if ((strpos($e->getMessage(), 'Unknown database') !== false || $e->getCode() == 1049) && $includeDbName) {
            return null;
        }
        error_log("Error DB: " . $e->getMessage());
        return null;
    }
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message, 'success' => false], $statusCode);
}

function jsonSuccess($data = null, $message = null) {
    $response = ['success' => true];
    if ($message !== null) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    jsonResponse($response);
}
