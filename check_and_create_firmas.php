<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR TABLAS CON 'firma' ===\n";

// Query exacta para ver todas las tablas
$allTables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'BD_SIGO'");

echo "Total de tablas: " . count($allTables) . "\n\n";

$firmaTables = array_filter($allTables, function($t) {
    return stripos($t->TABLE_NAME, 'firma') !== false;
});

if (count($firmaTables) > 0) {
    echo "Tablas con 'firma':\n";
    foreach ($firmaTables as $t) {
        echo "  - {$t->TABLE_NAME}\n";
    }
} else {
    echo "No hay tablas con 'firma'\n";
}

// Crear la tabla si no existe
echo "\n=== CREANDO TABLA ===\n";

// Primero verificar exactamente
$exists = DB::select(
    "SELECT 1 FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = 'BD_SIGO' AND TABLE_NAME = 'firmas_electronicas'"
);

if (count($exists) === 0) {
    echo "Tabla NO existe, creando...\n";
    
    try {
        DB::statement("
            CREATE TABLE firmas_electronicas (
                id_firma INT PRIMARY KEY IDENTITY(1,1),
                folio INT NOT NULL,
                cuv NVARCHAR(255) NOT NULL UNIQUE,
                usuario_id INT NOT NULL,
                fecha_firma DATETIME2 DEFAULT GETDATE(),
                ip_address NVARCHAR(45),
                user_agent NVARCHAR(MAX),
                created_at DATETIME2 DEFAULT GETDATE(),
                updated_at DATETIME2 DEFAULT GETDATE()
            )
        ");
        
        echo "✓ Tabla creada\n";
    } catch (\Exception $e) {
        echo "Error al crear: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ Tabla ya existe\n";
}
