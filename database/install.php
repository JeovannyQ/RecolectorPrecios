<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Instalador - Recolector de Precios</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; max-width: 800px; margin: 0 auto; }
        .card { background: #1e293b; border-radius: 12px; padding: 24px; border: 1px solid #334155; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); }
        h1 { color: #38bdf8; margin-top: 0; }
        .log { background: #090d16; padding: 16px; border-radius: 8px; font-family: monospace; line-height: 1.6; color: #a7f3d0; overflow-x: auto; }
        .success { color: #4ade80; font-weight: bold; }
        .error { color: #f87171; font-weight: bold; }
        .btn { display: inline-block; background: #0284c7; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 15px; }
        .btn:hover { background: #0369a1; }
    </style>
</head>
<body>
<div class='card'>
    <h1>🌱 Instalador del Sistema Recolector de Precios</h1>
    <div class='log'>";

try {
    // 1. Conectar a MySQL sin seleccionar DB para crearla
    $pdoRoot = getDBConnection(false);
    if (!$pdoRoot) {
        throw new Exception("No se pudo conectar al servidor MySQL en " . DB_HOST);
    }

    echo "▶️ Creando base de datos '<code>" . DB_NAME . "</code>' si no existe...<br>";
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "<span class='success'>✅ Base de datos verificada/creada.</span><br><br>";

    // 2. Conectar a la DB recién creada
    $pdo = getDBConnection(true);
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos " . DB_NAME);
    }

    // 3. Crear Tablas
    echo "▶️ Creando tablas del sistema...<br>";

    // Tablas de Roles y Usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `roles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre` VARCHAR(50) NOT NULL UNIQUE,
            `descripcion` VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `usuarios` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `rol_id` INT NOT NULL,
            `nombre` VARCHAR(150) NOT NULL,
            `usuario` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `regional_asignada` VARCHAR(100) DEFAULT 'CIBAO NORTE',
            `zona_asignada` VARCHAR(100) DEFAULT 'SANTIAGO',
            `activo` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Tablas de Catálogo (Categorías y Productos)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categorias` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `codigo` VARCHAR(10) NOT NULL UNIQUE,
            `nombre` VARCHAR(150) NOT NULL,
            `orden` INT DEFAULT 0,
            `activo` TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `productos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `categoria_id` INT NOT NULL,
            `codigo` VARCHAR(20) NOT NULL UNIQUE,
            `nombre` VARCHAR(150) NOT NULL,
            `unidad_medida` VARCHAR(100) NOT NULL,
            `orden` INT DEFAULT 0,
            `activo` TINYINT(1) DEFAULT 1,
            FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Tablas de Colecta de Precios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `colectas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `fecha` DATE NOT NULL,
            `regional` VARCHAR(100) NOT NULL DEFAULT 'CIBAO NORTE',
            `zona` VARCHAR(100) NOT NULL DEFAULT 'SANTIAGO',
            `informador_nombre` VARCHAR(150) NOT NULL,
            `informador_id` INT NULL,
            `observaciones` TEXT NULL,
            `estado` ENUM('Borrador','Completado') DEFAULT 'Completado',
            `creado_por` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`fecha`),
            INDEX (`regional`, `zona`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `colecta_detalles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `colecta_id` INT NOT NULL,
            `producto_id` INT NOT NULL,
            `unidad_medida` VARCHAR(100) NOT NULL,
            `precio_mayorista` DECIMAL(12,2) DEFAULT 0.00,
            `precio_minorista` DECIMAL(12,2) DEFAULT 0.00,
            `precio_finca` DECIMAL(12,2) DEFAULT 0.00,
            FOREIGN KEY (`colecta_id`) REFERENCES `colectas`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "<span class='success'>✅ Tablas creadas exitosamente.</span><br><br>";

    // 4. Insertar Roles por Defecto
    echo "▶️ Insertando roles por defecto...<br>";
    $pdo->exec("
        INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
        (1, 'Administrador', 'Control total de la plataforma, usuarios y reportes'),
        (2, 'Informador', 'Registro de colecta semanal de precios en campo')
        ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);
    ");
    echo "<span class='success'>✅ Roles 'Administrador' e 'Informador' listos.</span><br><br>";

    // 5. Insertar Usuario Admin por Defecto
    echo "▶️ Creando usuario inicial Admin...<br>";
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("
        INSERT INTO `usuarios` (`id`, `rol_id`, `nombre`, `usuario`, `password_hash`, `regional_asignada`, `zona_asignada`, `activo`) VALUES
        (1, 1, 'Administrador General', 'admin', '$adminPass', 'CIBAO NORTE', 'SANTIAGO', 1)
        ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
    ");
    echo "<span class='success'>✅ Usuario 'admin' (Clave: <code>admin123</code>) creado.</span><br><br>";

    // 6. Poblar Catálogo Completo (11 Categorías y 120+ Productos de la lista oficial)
    echo "▶️ Cargando catálogo oficial de productos agrícolas (11 categorías)...<br>";

    $catalog = [
        '1' => [
            'nombre' => 'CEREALES',
            'productos' => [
                ['1.01', 'Arroz Cáscara', 'Fanega/100 Kg.'],
                ['1.02', 'Arroz blanco (Factoria)', 'Saco/125 lbs'],
                ['1.03', 'Maíz (en Grano)', 'Quintal'],
                ['1.04', 'Maíz (Verde/Dulce)', 'Millar'],
                ['1.05', 'Sorgo', 'Quintal'],
            ]
        ],
        '2' => [
            'nombre' => 'RAICES-TUBERCULOS',
            'productos' => [
                ['2.01', 'Batata', 'Quintal'],
                ['2.02', 'Ñame', 'Quintal'],
                ['2.03', 'Papa', 'Quintal'],
                ['2.04', 'Yautía (Amarilla)', 'Quintal'],
                ['2.05', 'Yautía (Blanca)', 'Quintal'],
                ['2.06', 'Yautía (Coco)', 'Quintal'],
                ['2.07', 'Yautía (Morada)', 'Quintal'],
                ['2.08', 'Yuca (Amarga)', 'Quintal'],
                ['2.09', 'Yuca (Dulce)', 'Quintal'],
                ['2.10', 'Mapuey', 'Quintal'],
            ]
        ],
        '3' => [
            'nombre' => 'LEGUMINOSAS',
            'productos' => [
                ['3.01', 'Arvejas', 'Quintal'],
                ['3.02', 'Guandul (Verde en Vaina)', 'Quintal'],
                ['3.03', 'Guandul (Grano Seco)', 'Quintal'],
                ['3.04', 'Habichuela (Roja)', 'Quintal'],
                ['3.05', 'Habichuela (Negra)', 'Quintal'],
                ['3.06', 'Habichuela (Blanca)', 'Quintal'],
                ['3.07', 'Habichuela (Gira o Pinta)', 'Quintal'],
                ['3.08', 'Habas', 'Quintal'],
                ['3.09', 'Anconí (Cowpea-Caupi)', 'Quintal'],
            ]
        ],
        '4' => [
            'nombre' => 'MUSACEAS',
            'productos' => [
                ['4.01', 'Plátano', 'Millar'],
                ['4.02', 'Plátano FHIA-20', 'Millar'],
                ['4.03', 'Plátano FHIA-21', 'Millar'],
                ['4.04', 'Guineo (Verde)', 'Racimo/60lb/120 Ud'],
                ['4.05', 'Guineo (Orgánico)', 'Caja/42 Lib'],
                ['4.06', 'Rulo', 'Racimo'],
            ]
        ],
        '5' => [
            'nombre' => 'OLEAGINOSAS',
            'productos' => [
                ['5.01', 'Coco (Seco)', 'Millar'],
                ['5.02', 'Coco (de agua)', 'Millar'],
                ['5.03', 'Maní', 'Quintal'],
                ['5.04', 'Soya', 'Quintal'],
                ['5.05', 'Palma africana', 'Quintal'],
                ['5.06', 'Ajonjolí', 'Quintal'],
            ]
        ],
        '6' => [
            'nombre' => 'LEGUMBRES-HORTALIZAS (Campo Abierto)',
            'productos' => [
                ['6.01', 'Ají (Cubanela)', 'Quintal'],
                ['6.02', 'Ají (Gustoso)', 'Quintal'],
                ['6.03', 'Ají (Picante)', 'Quintal'],
                ['6.04', 'Ají (Cachucha)', 'Quintal'],
                ['6.05', 'Ají (Morrón)', 'Quintal'],
                ['6.06', 'Otra varieda de ají (Habanero)', 'Quintal'],
                ['6.07', 'Ajo', 'Quintal'],
                ['6.08', 'Auyama', 'Quintal'],
                ['6.09', 'Berenjena', 'Saco/125lbs'],
                ['6.10', 'Berenjena (Morada)', 'Quintal'],
                ['6.11', 'Cebolla (Roja)', 'Quintal'],
                ['6.12', 'Cebollín', 'Quintal'],
                ['6.13', 'Zanahoria', 'Quintal'],
                ['6.14', 'Cilantro (Seco, Semillas)', 'Quintal'],
                ['6.15', 'Cilantro (Ancho)', 'Paquete/1 lbs'],
                ['6.16', 'Cilantrico (Verdura)', 'Paquete/1 lbs'],
                ['6.17', 'Espinaca', 'Quintal'],
                ['6.18', 'Puerro', 'Quintal'],
                ['6.19', 'Molondrón', 'Quintal'],
                ['6.20', 'Pepino', 'Saco/90 lbs'],
                ['6.21', 'Rábano', 'Quintal'],
                ['6.22', 'Lechuga (Criolla de Hojas)', 'Matas( 3 und)'],
                ['6.23', 'Lechuga (Repollada)', 'Millar'],
                ['6.24', 'Remolacha', 'Quintal'],
                ['6.25', 'Repollo', 'Millar'],
                ['6.26', 'Tomate (de Ensalada)', 'Quintal'],
                ['6.27', 'Tomate (Industrial)', 'Quintal'],
                ['6.28', 'Coliflor', 'Quintal'],
                ['6.29', 'Brócolis', 'Quintal'],
                ['6.30', 'Apio (en Hojas)', 'Quintal'],
                ['6.31', 'Apio (Cepas)', 'Quintal'],
                ['6.32', 'Tayota', 'Millar'],
                ['6.33', 'Pimienta (en Grano)', 'Quintal'],
                ['6.34', 'Berro', 'Quintal'],
                ['6.35', 'Bangaña', 'Quintal'],
                ['6.36', 'Tindora', 'Quintal'],
                ['6.37', 'Musú Chino', 'Quintal'],
                ['6.38', 'Vainitas (Chinas)', 'Quintal'],
                ['6.39', 'Cundiamor', 'Quintal'],
                ['6.40', 'Guard beans (Guabin)', 'Quintal'],
                ['6.41', 'Cúrcuma', 'Quintal'],
                ['6.42', 'Pepino (Chino Dulce)', 'Quintal'],
                ['6.43', 'Berenjena (China)', 'Quintal'],
                ['6.44', 'Tayota (blanca grande)', 'Quintal'],
                ['6.45', 'Calabacín', 'Quintal'],
                ['6.46', 'Rúcula', 'Paquete/1 lbs'],
                ['6.47', 'Parvol', 'Quintal'],
                ['6.48', 'Ají Jalapeño', 'Quintal'],
                ['6.49', 'Citronella', 'Quintal'],
                ['6.50', 'Succhini', 'Quintal'],
            ]
        ],
        '7' => [
            'nombre' => 'PROD. BAJO AMB. PROTEGIDO',
            'productos' => [
                ['7.01', 'Ají (Cubanela)', 'Quintal'],
                ['7.02', 'Ají (Picante)', 'Quintal'],
                ['7.03', 'Ají (Morrón)', 'Quintal'],
                ['7.04', 'Ají (Habanero)', 'Quintal'],
                ['7.05', 'Ají (Jamaiquino)', 'Quintal'],
                ['7.06', 'Berenjena (Morada)', 'Quintal'],
                ['7.07', 'Berenjena (China)', 'Quintal'],
                ['7.08', 'Tomate (Ensalada)', 'Quintal'],
                ['7.09', 'Tomate (Bugalú)', 'Quintal'],
                ['7.10', 'Tomate (Cherry)', 'Quintal'],
                ['7.11', 'Pepino (Grande - Tradic.)', 'Quintal'],
                ['7.12', 'Pepino (Chino Dulce)', 'Quintal'],
                ['7.13', 'Tayota (Verde)', 'Quintal'],
                ['7.14', 'Tayota (Blanca Grande)', 'Quintal'],
                ['7.15', 'Bangaña', 'Quintal'],
                ['7.16', 'Tindora', 'Quintal'],
                ['7.17', 'Musú Chino', 'Quintal'],
                ['7.18', 'Vainitas (Chinas)', 'Quintal'],
                ['7.19', 'Cundiamor', 'Quintal'],
                ['7.20', 'Guard beans (Guabin)', 'Quintal'],
                ['7.21', 'Cúrcuma', 'Quintal'],
                ['7.22', 'Calabacín', 'Quintal'],
                ['7.23', 'Rúcula', 'Quintal'],
                ['7.24', 'Parvol', 'Quintal'],
                ['7.25', 'Ají Jalapeño', 'Quintal'],
                ['7.26', 'Citronella', 'Quintal'],
                ['7.27', 'Succhini', 'Quintal'],
            ]
        ],
        '8' => [
            'nombre' => 'PRODUCTOS TRADICIONALES',
            'productos' => [
                ['8.01', 'Café (Verde en Grano)', 'Costal/50 kilos'],
                ['8.02', 'Cacao (Sánchez)', 'Quintal'],
                ['8.03', 'Cacao (Hispaniola)', 'Quintal'],
                ['8.04', 'Caña negra', 'Quintal'],
                ['8.05', 'Tabaco', 'Quintal'],
            ]
        ],
        '9' => [
            'nombre' => 'FRUTALES',
            'productos' => [
                ['9.01', 'Aguacate (Criollo)', 'Millar'],
                ['9.02', 'Aguacate (Hass)', 'Millar'],
                ['9.03', 'Aguacate (Semil-34)', 'Millar'],
                ['9.04', 'Aguacate (Popenoe)', 'Millar'],
                ['9.05', 'Aguacate (Carla)', 'Millar'],
                ['9.06', 'Aguacate (Benny)', 'Millar'],
                ['9.07', 'Lechosa Maradol', 'Millar'],
                ['9.08', 'Lechosa Red Lady', 'Quintal'],
                ['9.09', 'Melón (Cantaloupe)', 'Quintal'],
                ['9.10', 'Melón (Amarillo)', 'Millar'],
                ['9.11', 'Melón (Blanco)', 'Millar'],
                ['9.12', 'Sandía', 'Quintal'],
                ['9.13', 'Mango (Tommy)', 'Millar'],
                ['9.14', 'Mango (Gota de Oro)', 'Millar'],
                ['9.15', 'Mango (Banilejo)', 'Millar'],
                ['9.16', 'Mango (Puntica)', 'Millar'],
                ['9.17', 'Mango (Keit)', 'Millar'],
                ['9.18', 'Mango (Yamaguí)', 'Millar'],
                ['9.19', 'Zapote', 'Quintal'],
                ['9.20', 'Limón Agrio (Criollo)', 'Millar'],
                ['9.21', 'Limón Agrio (Persa)', 'Millar'],
                ['9.22', 'Naranja Agria', 'Millar'],
                ['9.23', 'Naranja Dulce', 'Millar'],
                ['9.24', 'Toronja', 'Millar'],
                ['9.25', 'Piña MD2', 'Millar'],
                ['9.26', 'Piña (Cayena Lisa)', 'Millar'],
                ['9.27', 'Chinola', 'Millar'],
                ['9.28', 'Tamarindo', 'Quintal'],
                ['9.29', 'Mandarina', 'Millar'],
                ['9.30', 'Fresa', 'Quintal'],
                ['9.31', 'Cereza', 'Quintal'],
                ['9.32', 'Uva', 'Quintal'],
                ['9.33', 'Granadillo', 'Docena'],
                ['9.34', 'Níspero', 'Millar'],
                ['9.35', 'Guanábana', 'Quintal'],
                ['9.36', 'Macadamia', 'Quintal'],
                ['9.37', 'Cajuil', 'Quintal'],
                ['9.38', 'Pitahaya', 'Quintal'],
                ['9.39', 'Guayaba (Criolla)', 'Millar'],
                ['9.40', 'Guayaba (Injerta)', 'Quintal'],
                ['9.41', 'Carambola', 'Millar'],
                ['9.42', 'Mamón', 'Millar'],
                ['9.43', 'Anón', 'Millar'],
                ['9.44', 'Jagua', 'Millar'],
                ['9.45', 'Caimito', 'Millar'],
                ['9.46', 'Manzana (de Oro)', 'Millar'],
                ['9.47', 'Pera (Criolla)', 'Ciento'],
                ['9.48', 'Caqui', 'Millar'],
            ]
        ],
        '10' => [
            'nombre' => 'OTROS CULTIVOS',
            'productos' => [
                ['10.01', 'Pastos', 'Pacas'],
                ['10.02', 'Bija', 'Quintal'],
                ['10.03', 'Orégano (Verde en hoja)', 'Quintal'],
                ['10.04', 'Canela', 'Quintal'],
                ['10.05', 'Malagueta', 'Quintal'],
                ['10.06', 'Nuez Moscada', 'Quintal'],
                ['10.07', 'Jengibre', 'Quintal'],
                ['10.08', 'Vainilla', 'Quintal'],
                ['10.09', 'Pan de Fruta', 'Quintal'],
                ['10.10', 'Buen Pan', 'Quintal'],
                ['10.11', 'Sábila', 'Mata/Quintal'],
                ['10.12', 'Sisal', 'Quintal'],
                ['10.13', 'BayRum', 'Quintal'],
                ['10.14', 'Hierba buena', 'Quintal'],
                ['10.15', 'Menta', 'Quintal'],
                ['10.16', 'Albahaca', 'Quintal'],
                ['10.17', 'Higuereta', 'Quintal'],
                ['10.18', 'Palmito', 'Quintal'],
                ['10.19', 'Piñón', 'Quintal'],
                ['10.20', 'Pino', 'Quintal'],
                ['10.21', 'Acacia', 'Quintal'],
                ['10.22', 'Caoba', 'Quintal'],
                ['10.23', 'Cedro', 'Quintal'],
                ['10.24', 'Caucho', 'Quintal'],
                ['10.25', 'Noni', 'Quintal'],
                ['10.26', 'Pejiballe', 'Quintal'],
            ]
        ],
        '11' => [
            'nombre' => 'CARNES',
            'productos' => [
                ['11.01', 'Cerdo (Adulto en pie)', 'Kilo'],
                ['11.02', 'Cerdito (Tierno en pie)', 'Kilo'],
                ['11.03', 'Chivo (Adulto en pie)', 'Kilo'],
                ['11.04', 'Chivito (En pie)', 'Kilo'],
                ['11.05', 'Pollo (Vivo en pie)', 'Quintal'],
                ['11.06', 'Res Macho (Adulto en pie)', 'Kilo'],
                ['11.07', 'Res Hembra (Adulta en pie)', 'Kilo'],
                ['11.08', 'Res (Novilla en pie)', 'Kilo'],
                ['11.09', 'Huevos de Gallina', 'Cto/Millar'],
                ['11.10', 'Leche (Líquida, Cruda)', 'Litro'],
            ]
        ],
    ];

    $stmtCat = $pdo->prepare("INSERT INTO categorias (codigo, nombre, orden) VALUES (:codigo, :nombre, :orden) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
    $stmtProd = $pdo->prepare("INSERT INTO productos (categoria_id, codigo, nombre, unidad_medida, orden) VALUES (:categoria_id, :codigo, :nombre, :unidad_medida, :orden) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), unidad_medida = VALUES(unidad_medida)");

    $cCount = 0; $pCount = 0;
    foreach ($catalog as $catCodigo => $catData) {
        $stmtCat->execute([':codigo' => $catCodigo, ':nombre' => $catData['nombre'], ':orden' => (int)$catCodigo]);
        $catId = $pdo->lastInsertId();
        if (!$catId) {
            $s = $pdo->prepare("SELECT id FROM categorias WHERE codigo = ?");
            $s->execute([$catCodigo]);
            $catId = $s->fetchColumn();
        }
        $cCount++;

        $pOrden = 1;
        foreach ($catData['productos'] as $p) {
            $stmtProd->execute([
                ':categoria_id' => $catId,
                ':codigo'       => $p[0],
                ':nombre'       => $p[1],
                ':unidad_medida'=> $p[2],
                ':orden'        => $pOrden++
            ]);
            $pCount++;
        }
    }

    echo "<span class='success'>✅ $cCount Categorías y $pCount Productos precargados correctamente.</span><br><br>";
    echo "<h2 style='color:#4ade80;'>🎉 INSTALACIÓN COMPLETADA EXITOSAMENTE</h2>";
    echo "<a href='../index.php' class='btn'>Ir al Sistema Recolector de Precios →</a>";

} catch (Exception $e) {
    echo "<br><span class='error'>❌ ERROR EN INSTALACIÓN: " . htmlspecialchars($e->getMessage()) . "</span>";
}

echo "</div></div></body></html>";
