<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📊 Tabla Usuarios:\n";
$usuarios = DB::table('Usuarios')->limit(3)->get();
foreach ($usuarios as $u) {
    echo "  - ID: {$u->id_usuario}, Nombre: {$u->nombre_usuario}\n";
}

echo "\n📊 Tabla DirectivoCalendarioPermiso con relacion:\n";
$permisos = DB::table('directivos_calendario_permisos')
    ->join('Usuarios', 'directivos_calendario_permisos.fk_id_directivo', '=', 'Usuarios.id_usuario')
    ->get(['directivos_calendario_permisos.*', 'Usuarios.nombre_usuario']);

foreach ($permisos as $p) {
    echo "  - Directivo: {$p->nombre_usuario} (ID: {$p->fk_id_directivo}), Email: {$p->email_directivo}\n";
}
