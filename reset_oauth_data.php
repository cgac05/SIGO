<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Clean up
echo "=== Limpiando Datos de OAuth ===\n";

// 1. Delete all oauth states (to start fresh)
$deleted1 = DB::table('oauth_states')->delete();
echo "✅ Eliminados $deleted1 registros de oauth_states\n";

// 2. Delete old calendar permissions
$deleted2 = DB::table('directivos_calendario_permisos')->delete();
echo "✅ Eliminados $deleted2 registros de directivos_calendario_permisos\n";

// 3. Reset calendario_sincronizacion_log (optional)
$deleted3 = DB::table('calendario_sincronizacion_log')->delete();
echo "✅ Eliminados $deleted3 registros de calendario_sincronizacion_log\n";

echo "\n✅ BD limpiada. Ahora el usuario puede intentar conectar nuevamente desde cero\n";
