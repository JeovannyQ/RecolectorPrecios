<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$user = getLoggedInUser();
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id               = !empty($data['id']) ? (int)$data['id'] : null;
$fecha            = trim($data['fecha'] ?? date('Y-m-d'));
$regional         = trim($data['regional'] ?? $user['regional_asignada']);
$zona             = trim($data['zona'] ?? $user['zona_asignada']);
$informadorNombre = trim($data['informador_nombre'] ?? $user['nombre']);
$observaciones    = trim($data['observaciones'] ?? '');
$detalles         = $data['detalles'] ?? [];

if (empty($fecha) || empty($regional) || empty($zona) || empty($informadorNombre)) {
    jsonError('Por favor complete los campos obligatorios del encabezado');
}

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de conexión a la base de datos', 500);

try {
    $pdo->beginTransaction();

    if ($id) {
        $stmtUpdate = $pdo->prepare("
            UPDATE colectas 
            SET fecha = :fecha, regional = :regional, zona = :zona, 
                informador_nombre = :informador_nombre, observaciones = :observaciones, 
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':fecha'             => $fecha,
            ':regional'          => $regional,
            ':zona'              => $zona,
            ':informador_nombre' => $informadorNombre,
            ':observaciones'    => $observaciones,
            ':id'                => $id
        ]);

        $pdo->prepare("DELETE FROM colecta_detalles WHERE colecta_id = ?")->execute([$id]);
        $colectaId = $id;
    } else {
        $stmtInsert = $pdo->prepare("
            INSERT INTO colectas (fecha, regional, zona, informador_nombre, informador_id, observaciones, estado, creado_por)
            VALUES (:fecha, :regional, :zona, :informador_nombre, :informador_id, :observaciones, 'Completado', :creado_por)
        ");
        $stmtInsert->execute([
            ':fecha'             => $fecha,
            ':regional'          => $regional,
            ':zona'              => $zona,
            ':informador_nombre' => $informadorNombre,
            ':informador_id'     => $user['id'],
            ':observaciones'    => $observaciones,
            ':creado_por'        => $user['id']
        ]);
        $colectaId = $pdo->lastInsertId();
    }

    $stmtDetail = $pdo->prepare("
        INSERT INTO colecta_detalles (colecta_id, producto_id, unidad_medida, precio_mayorista, precio_minorista, precio_finca)
        VALUES (:colecta_id, :producto_id, :unidad_medida, :precio_mayorista, :precio_minorista, :precio_finca)
    ");

    $itemsInserted = 0;
    foreach ($detalles as $det) {
        $pId = (int)($det['producto_id'] ?? 0);
        if (!$pId) continue;

        $pMayorista = floatval($det['precio_mayorista'] ?? 0);
        $pMinorista = floatval($det['precio_minorista'] ?? 0);
        $pFinca     = floatval($det['precio_finca'] ?? 0);

        if ($pMayorista > 0 || $pMinorista > 0 || $pFinca > 0) {
            $stmtDetail->execute([
                ':colecta_id'       => $colectaId,
                ':producto_id'      => $pId,
                ':unidad_medida'    => trim($det['unidad_medida'] ?? 'Quintal'),
                ':precio_mayorista' => $pMayorista,
                ':precio_minorista' => $pMinorista,
                ':precio_finca'     => $pFinca
            ]);
            $itemsInserted++;
        }
    }

    $pdo->commit();

    jsonSuccess([
        'id'             => $colectaId,
        'items_guardados'=> $itemsInserted
    ], 'Colecta de precios guardada con éxito');

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonError('Error al guardar la colecta: ' . $e->getMessage(), 500);
}
