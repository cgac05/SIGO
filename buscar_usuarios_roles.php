<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BÚSQUEDA DE USUARIOS: DIRECTIVO Y ADMINISTRATIVO ===\n\n";

// 1. Ver tabla de roles
echo "1. TABLA: Cat_Roles\n";
$rolesColumns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Cat_Roles'
    ORDER BY ORDINAL_POSITION
");

echo "   Estructura:\n";
foreach ($rolesColumns as $col) {
    echo "   - " . $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ")\n";
}

$roles = DB::select("SELECT TOP 10 * FROM Cat_Roles");
echo "\n   Roles disponibles:\n";
foreach ($roles as $rol) {
    $props = (array)$rol;
    foreach ($props as $k => $v) {
        echo "   - " . $k . ": " . $v . "\n";
    }
    echo "   ---\n";
}

// 2. Ver Personal con roles
echo "\n2. TABLA: Personal (con roles)\n";
$personal = DB::select("
    SELECT TOP 20 
        p.numero_empleado,
        p.nombre,
        p.apellido_paterno,
        p.apellido_materno,
        p.fk_rol,
        p.fk_id_usuario,
        u.email,
        u.tipo_usuario,
        cr.nombre_rol
    FROM Personal p
    LEFT JOIN Usuarios u ON p.fk_id_usuario = u.id_usuario
    LEFT JOIN Cat_Roles cr ON p.fk_rol = cr.id_rol
");

if (count($personal) > 0) {
    echo "   Registros encontrados:\n";
    foreach ($personal as $p) {
        echo "\n   - Empleado: " . $p->numero_empleado . "\n";
        echo "     Nombre: " . $p->nombre . " " . $p->apellido_paterno . " " . $p->apellido_materno . "\n";
        echo "     Email: " . ($p->email ?? 'SIN EMAIL') . "\n";
        echo "     Tipo Usuario: " . ($p->tipo_usuario ?? 'N/A') . "\n";
        echo "     Rol: " . ($p->nombre_rol ?? 'SIN ROL') . "\n";
        echo "     ID Usuario: " . $p->fk_id_usuario . "\n";
    }
} else {
    echo "   ✗ Sin registros en Personal\n";
}

// 3. Contar por rol
echo "\n3. CONTEO DE USUARIOS POR ROL:\n";
$countByRole = DB::select("
    SELECT 
        cr.id_rol,
        cr.nombre_rol,
        COUNT(p.numero_empleado) as cantidad
    FROM Cat_Roles cr
    LEFT JOIN Personal p ON cr.id_rol = p.fk_rol
    GROUP BY cr.id_rol, cr.nombre_rol
    ORDER BY cr.nombre_rol
");

foreach ($countByRole as $c) {
    echo "   - " . $c->nombre_rol . ": " . $c->cantidad . " usuarios\n";
}

echo "\n=== FIN ===\n";
?>
