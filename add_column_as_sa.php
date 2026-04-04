<?php
$connections = [
    ['dsn' => 'sqlsrv:Server=localhost,1433;Database=BD_SIGO;TrustServerCertificate=true;', 'user' => null, 'pass' => null, 'name' => 'Windows Auth'],
    ['dsn' => 'sqlsrv:Server=localhost,1433;Database=BD_SIGO;TrustServerCertificate=true;', 'user' => 'SigoWebAppUser', 'pass' => 'UsuarioSigo159', 'name' => 'App User'],
];

$pdo = null;
foreach ($connections as $c) {
    try {
        $pdo = new PDO($c['dsn'], $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "✓ Conectado: {$c['name']}\n\n";
        break;
    } catch (Exception $e) {}
}

if (!$pdo) { echo "✗ Error de conexión\n"; exit(1); }

try {
    $check = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Cat_TiposDocumento' AND COLUMN_NAME='peso_maximo_mb'";
    $result = $pdo->query($check)->fetch(PDO::FETCH_ASSOC);
    
    if ($result['cnt'] == 0) {
        echo "➤ Agregando columna peso_maximo_mb...\n";
        $pdo->exec("ALTER TABLE Cat_TiposDocumento ADD peso_maximo_mb INT DEFAULT 5 NULL");
        echo "✓ ¡Columna agregada!\n\n";
    } else {
        echo "✓ Columna ya existe\n\n";
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "DOCUMENTOS CON LÍMITES DE PESO:\n";
    echo str_repeat("-", 60) . "\n";
    
    $docs = $pdo->query("SELECT id_tipo_doc, nombre_documento, peso_maximo_mb FROM Cat_TiposDocumento ORDER BY nombre_documento")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($docs as $d) {
        printf("  [%2d] %-35s => %3d MB\n", $d['id_tipo_doc'], $d['nombre_documento'], $d['peso_maximo_mb']);
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✓ ¡OPERACIÓN EXITOSA!\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n"; exit(1);
}
