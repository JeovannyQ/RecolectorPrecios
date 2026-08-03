<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$user = getLoggedInUser();
$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

if (empty($_FILES['file']['tmp_name'])) {
    jsonError('Por favor seleccione un archivo CSV o Excel para importar');
}

$filePath = $_FILES['file']['tmp_name'];
$fileHandle = fopen($filePath, 'r');
if (!$fileHandle) {
    jsonError('No se pudo abrir el archivo subido');
}

$header = fgetcsv($fileHandle, 1000, ",");

$fecha     = trim($_POST['fecha'] ?? date('Y-m-d'));
$regional  = trim($_POST['regional'] ?? 'CIBAO NORTE');
$zona      = trim($_POST['zona'] ?? 'SANTIAGO');
$informador= trim($_POST['informador'] ?? $user['nombre']);

try {
    $pdo->beginTransaction();

    $stmtIns = $pdo->prepare("
        INSERT INTO colectas (fecha, regional, zona, informador_nombre, informador_id, observaciones, estado, creado_por)
        VALUES (:fecha, :regional, :zona, :informador_nombre, :informador_id, 'Importación inicial desde archivo', 'Completado', :creado_por)
    ");
    $stmtIns->execute([
        ':fecha'             => $fecha,
        ':regional'          => $regional,
        ':zona'              => $zona,
        ':informador_nombre' => $informador,
        ':informador_id'     => $user['id'],
        ':creado_por'        => $user['id']
    ]);
    $colectaId = $pdo->lastInsertId();

    $stmtFindProd = $pdo->prepare("SELECT id, unidad_medida FROM productos WHERE codigo = ? OR nombre = ? LIMIT 1");
    $stmtDet = $pdo->prepare("
        INSERT INTO colecta_detalles (colecta_id, producto_id, unidad_medida, precio_mayorista, precio_minorista, precio_finca)
        VALUES (:colecta_id, :producto_id, :unidad_medida, :mayorista, :minorista, :finca)
    ");

    $importedCount = 0;
    while (($row = fgetcsv($fileHandle, 1000, ",")) !== FALSE) {
        if (count($row) < 3) continue;

        $codigo    = trim($row[0]);
        $nombre    = trim($row[1]);
        $unidad    = trim($row[2]);
        $mayorista = floatval($row[3] ?? 0);
        $minorista = floatval($row[4] ?? 0);
        $finca     = floatval($row[5] ?? 0);

        $stmtFindProd->execute([$codigo, $nombre]);
        $prod = $stmtFindProd->fetch();

        if ($prod) {
            $stmtDet->execute([
                ':colecta_id'    => $colectaId,
                ':producto_id'   => $prod['id'],
                ':unidad_medida' => $unidad ?: $prod['unidad_medida'],
                ':mayorista'     => $mayorista,
                ':minorista'     => $minorista,
                ':finca'         => $finca
            ]);
            $importedCount++;
        }
    }

    fclose($fileHandle);
    $pdo->commit();

    jsonSuccess([
        'colecta_id' => $colectaId,
        'registros_importados' => $importedCount
    ], "Importación completada con éxito. Se procesaron $importedCount productos.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonError('Error durante la importación: ' . $e->getMessage(), 500);
}
