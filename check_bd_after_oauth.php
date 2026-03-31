<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Verificando BD ===\n\n";

// Permisos guardados
$permisos = DB::table('directivos_calendario_permisos')->get();
echo "📝 Permisos guardados: " . count($permisos) . "\n";
foreach ($permisos as $p) {
    echo "  - ID: {$p->id_permiso}, Directivo: {$p->fk_id_directivo}, Email: {$p->email_directivo}, Activo: {$p->activo}\n";
    echo "    Token guardado: " . (strlen($p->google_access_token ?? '') > 50 ? "✅ SÍ (" . strlen($p->google_access_token) . " chars)" : "❌ NO") . "\n";
}

// OAuth states
$states = DB::table('oauth_states')->get();
echo "\n🔐 OAuth States: " . count($states) . "\n";
foreach ($states as $s) {
    echo "  - State: {$s->state}, Used: {$s->used_at}\n";
}

// Eventos de prueba
$apoyos = DB::table('Apoyos')->where('nombre_apoyo', 'like', '%PRUEBA%')->get();
echo "\n🎯 Apoyos de prueba: " . count($apoyos) . "\n";
foreach ($apoyos as $a) {
    $hitos = DB::table('hitos_apoyo')->where('fk_id_apoyo', $a->id_apoyo)->get();
    echo "  - {$a->nombre_apoyo} ({$a->id_apoyo}): " . count($hitos) . " hitos\n";
}
