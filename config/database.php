<?php
// Configuración de la Base de Datos para Recolector de Precios
date_default_timezone_set('America/Santo_Domingo');

// Detectar producción (hosting / Linux)
$esProduccion = (PHP_SAPI === 'cli' && file_exists('/home/quitech'))
             || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'quitech01.com') !== false)
             || (file_exists('/home/quitech'));

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// Credenciales
$userDef = getenv('DB_USER') ?: ($esProduccion ? 'quitech_MiniJosue' : 'root');
$passDef = getenv('DB_PASS') ?: ($esProduccion ? '5YkWujmGatWNXtMWHFUE' : '');
$nameDef = getenv('DB_NAME') ?: ($esProduccion ? 'quitech_recolector' : 'recolector_precios');

define('DB_USER', $userDef);
define('DB_PASS', $passDef);
define('DB_NAME', $nameDef);

function getDBConnection($includeDbName = true) {
    static $pdo = null;
    if ($pdo !== null && $includeDbName) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    if (!$includeDbName) {
        try {
            return new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            return null;
        }
    }

    // Intentar conectar a la base de datos principal y a bases de datos alternativas si aplica
    $dbNamesToTry = array_unique([DB_NAME, 'quitech_recolector', 'quitech_MiniJosue', 'quitech_cerramorfe', 'recolector_precios']);

    foreach ($dbNamesToTry as $dbName) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . $dbName . ";charset=" . DB_CHARSET;
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo = $conn;
            return $conn;
        } catch (PDOException $e) {
            // Continuar probando alternativas
        }
    }

    return null;
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
