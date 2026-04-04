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
    echo "➤ Actualizando documentos existentes al valor predeterminado (5 MB)...\n";
    $pdo->exec("UPDATE Cat_TiposDocumento SET peso_maximo_mb = 5 WHERE peso_maximo_mb IS NULL OR peso_maximo_mb = 0");
    
    echo "✓ ¡Actualización completada!\n\n";
    
    echo str_repeat("=", 60) . "\n";
    echo "DOCUMENTOS FINALES CON LÍMITES DE PESO:\n";
    echo str_repeat("=", 60) . "\n";
    
    $docs = $pdo->query("SELECT id_tipo_doc, nombre_documento, peso_maximo_mb FROM Cat_TiposDocumento ORDER BY nombre_documento")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($docs as $d) {
        printf("  [%2d] %-35s => %3d MB\n", $d['id_tipo_doc'], $d['nombre_documento'], $d['peso_maximo_mb']);
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✓ ¡LÍMITES DE PESO CONFIGURADOS EXITOSAMENTE!\n";
    echo str_repeat("=", 60) . "\n";
    echo "\nFuncionalidad lista para usar:\n";
    echo "  • Nuevo documento -> se asigna el valor de peso_maximo_mb\n";
    echo "  • Rango permitido: 1-500 MB\n";
    echo "  • Documentos existentes: 5 MB (predeterminado)\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n"; exit(1);
}
