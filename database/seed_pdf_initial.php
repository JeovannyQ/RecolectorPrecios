<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDBConnection();
    if (!$pdo) die("Error de conexión a BD");

    echo "🌱 Creando registro inicial de colecta del PDF...\n";

    // 1. Crear encabezado inicial
    $fecha = date('Y-m-d');
    $regional = 'CIBAO NORTE';
    $zona = 'SANTIAGO';
    $informador = 'Informador Oficial';

    $stmtColecta = $pdo->prepare("
        INSERT INTO colectas (fecha, regional, zona, informador_nombre, observaciones, estado, creado_por)
        VALUES (:fecha, :regional, :zona, :informador_nombre, 'Colecta inicial de plantilla PDF', 'Completado', 1)
    ");
    $stmtColecta->execute([
        ':fecha' => $fecha,
        ':regional' => $regional,
        ':zona' => $zona,
        ':informador_nombre' => $informador
    ]);
    $colectaId = $pdo->lastInsertId();

    // 2. Obtener todos los productos precargados
    $stmtProds = $pdo->query("SELECT id, unidad_medida FROM productos");
    $productos = $stmtProds->fetchAll();

    $stmtDet = $pdo->prepare("
        INSERT INTO colecta_detalles (colecta_id, producto_id, unidad_medida, precio_mayorista, precio_minorista, precio_finca)
        VALUES (:colecta_id, :producto_id, :unidad_medida, 0.00, 0.00, 0.00)
    ");

    foreach ($productos as $p) {
        $stmtDet->execute([
            ':colecta_id' => $colectaId,
            ':producto_id' => $p['id'],
            ':unidad_medida' => $p['unidad_medida']
        ]);
    }

    echo "✅ Colecta inicial creada con ID #$colectaId y " . count($productos) . " productos vinculados del PDF.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
