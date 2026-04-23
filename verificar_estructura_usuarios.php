<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE TABLAS DE USUARIOS Y ROLES ===\n\n";

// 1. Verificar estructura de tabla Usuarios
echo "1. TABLA: Usuarios\n";
echo "   Columnas:\n";
$usuariosColumns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Usuarios'
    ORDER BY ORDINAL_POSITION
");

foreach ($usuariosColumns as $col) {
    echo "   - " . $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ") Nullable: " . $col->IS_NULLABLE . "\n";
}

// 2. Verificar tabla de roles si existe
echo "\n2. Buscando tabla de ROLES...\n";
$tables = DB::select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%rol%' OR TABLE_NAME LIKE '%role%' OR TABLE_NAME LIKE '%permiso%'
");

if (count($tables) > 0) {
    foreach ($tables as $table) {
        echo "   ✓ Tabla encontrada: " . $table->TABLE_NAME . "\n";
    }
} else {
    echo "   ✗ No se encontraron tablas de roles\n";
}

// 3. Verificar tabla Personal
echo "\n3. TABLA: Personal\n";
$personalColumns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Personal'
    ORDER BY ORDINAL_POSITION
");

if (count($personalColumns) > 0) {
    echo "   Columnas:\n";
    foreach ($personalColumns as $col) {
        echo "   - " . $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ") Nullable: " . $col->IS_NULLABLE . "\n";
    }
} else {
    echo "   ✗ Tabla no encontrada\n";
}

// 4. Verificar tabla de Directivos
echo "\n4. Buscando tabla DIRECTIVOS...\n";
$directivosCheck = DB::select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%directivo%'
");

if (count($directivosCheck) > 0) {
    echo "   ✓ Tabla encontrada: " . $directivosCheck[0]->TABLE_NAME . "\n";
} else {
    echo "   ✗ No encontrada\n";
}

// 5. Ver todos los tipos_usuario disponibles
echo "\n5. TIPOS DE USUARIOS EN BD:\n";
$tipos = DB::select("SELECT DISTINCT tipo_usuario FROM Usuarios ORDER BY tipo_usuario");
foreach ($tipos as $tipo) {
    echo "   - " . $tipo->tipo_usuario . "\n";
}

// 6. Buscar usuarios por tipo
echo "\n6. USUARIOS DISPONIBLES:\n";

echo "\n   a) Usuarios tipo 'Directivo':\n";
$directivos = DB::select("SELECT id_usuario, email, tipo_usuario FROM Usuarios WHERE tipo_usuario = 'Directivo' LIMIT 5");
if (count($directivos) > 0) {
    foreach ($directivos as $dir) {
        echo "      ID: " . $dir->id_usuario . " | Email: " . $dir->email . "\n";
    }
} else {
    echo "      No encontrados\n";
}

echo "\n   b) Usuarios tipo 'Administrativo':\n";
$admin = DB::select("SELECT id_usuario, email, tipo_usuario FROM Usuarios WHERE tipo_usuario = 'Administrativo' LIMIT 5");
if (count($admin) > 0) {
    foreach ($admin as $adm) {
        echo "      ID: " . $adm->id_usuario . " | Email: " . $adm->email . "\n";
    }
} else {
    echo "      No encontrados\n";
}

echo "\n7. RELACION CON TABLA Personal (si existe):\n";
if (count($personalColumns) > 0) {
    $personalData = DB::select("
        SELECT TOP 3 * FROM Personal
    ");
    if (count($personalData) > 0) {
        echo "   ✓ Tabla tiene datos\n";
        $props = (array)$personalData[0];
        echo "   Campos: " . implode(", ", array_keys($props)) . "\n";
    } else {
        echo "   ✗ Tabla vacía\n";
    }
}

echo "\n=== FIN ===\n";
?>
