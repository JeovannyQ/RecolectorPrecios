<?php
require_once __DIR__ . '/../auth/session.php';

checkAuth();

$pdo = getDBConnection();
if (!$pdo) jsonError('Error de base de datos', 500);

try {
    $currentMonthStart = date('Y-m-01');

    // 1. Colectas Totales y del Mes
    $stmtCol = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN fecha >= '$currentMonthStart' THEN 1 ELSE 0 END) as mes FROM colectas");
    $resCol = $stmtCol->fetch(PDO::FETCH_ASSOC);
    $totalColectas = (int)($resCol['total'] ?? 0);
    $colectasMes = (int)($resCol['mes'] ?? 0);

    // 2. Productos Activos
    $stmtProd = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1");
    $totalProductos = (int)$stmtProd->fetchColumn();

    // 3. Informadores / Usuarios Activos
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1");
    $totalInformadores = (int)$stmtUsers->fetchColumn();

    // 4. Promedios de Precios
    $stmtProms = $pdo->query("
        SELECT 
            COALESCE(AVG(NULLIF(precio_mayorista, 0)), 0) as avg_mayorista,
            COALESCE(AVG(NULLIF(precio_minorista, 0)), 0) as avg_minorista,
            COALESCE(AVG(NULLIF(precio_finca, 0)), 0) as avg_finca
        FROM colecta_detalles
    ");
    $resProms = $stmtProms->fetch(PDO::FETCH_ASSOC);

    // 5. Últimas 5 colectas
    $stmtUltimas = $pdo->query("
        SELECT c.*, COUNT(cd.id) as total_items
        FROM colectas c
        LEFT JOIN colecta_detalles cd ON cd.colecta_id = c.id
        GROUP BY c.id
        ORDER BY c.fecha DESC, c.id DESC
        LIMIT 5
    ");
    $ultimasColectas = $stmtUltimas->fetchAll(PDO::FETCH_ASSOC);

    // 6. Colectas por Zona
    $stmtZona = $pdo->query("
        SELECT zona, COUNT(*) as cantidad 
        FROM colectas 
        GROUP BY zona 
        ORDER BY cantidad DESC 
        LIMIT 6
    ");
    $colectasPorZona = $stmtZona->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess([
        'total_colectas'    => $totalColectas,
        'colectas_mes'      => $colectasMes,
        'total_productos'   => $totalProductos,
        'total_informadores'=> $totalInformadores,
        'promedios'         => [
            'mayorista' => round((float)($resProms['avg_mayorista'] ?? 0), 2),
            'minorista' => round((float)($resProms['avg_minorista'] ?? 0), 2),
            'finca'     => round((float)($resProms['avg_finca'] ?? 0), 2)
        ],
        'ultimas_colectas'  => $ultimasColectas,
        'colectas_por_zona' => $colectasPorZona
    ]);
} catch (Exception $e) {
    jsonError('Error al obtener estadísticas del dashboard: ' . $e->getMessage(), 500);
}
