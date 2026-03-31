<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🎯 Verificando estructura de tablas...\n\n";

echo "📊 Tabla Apoyos:\n";
$apoyo = DB::table('Apoyos')->where('id_apoyo', 24)->first();
if ($apoyo) {
    echo "✅ Apoyo encontrado: {$apoyo->nombre_apoyo}\n";
} else {
    echo "❌ Apoyo no encontrado\n";
}

echo "\n📊 Tabla Usuarios (primeras 2+):\n";
$usuarios = DB::selectRaw('SELECT TOP 3 id_usuario, nombre_usuario FROM [Usuarios]');
foreach ($usuarios as $u) {
    echo "  - ID: {$u->id_usuario}, Nombre: {$u->nombre_usuario}\n";
}

echo "\n📊 Tabla DirectivoCalendarioPermiso:\n";
$permisos = DB::table('directivos_calendario_permisos')->get();
echo "Total: " . count($permisos) . "\n";
foreach ($permisos as $p) {
    echo "  - Directivo: {$p->fk_id_directivo}, Email: {$p->email_directivo}, Activo: {$p->activo}\n";
}
