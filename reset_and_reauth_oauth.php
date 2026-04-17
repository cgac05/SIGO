<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "════════════════════════════════════════════════════════════\n";
echo "🔄 LIMPIAR Y RESETEAR GOOGLE OAUTH\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Obtener el permiso actual
$permiso = \App\Models\DirectivoCalendarioPermiso::find(7);

if (!$permiso) {
    echo "❌ No se encontró permiso ID 7\n";
    exit(1);
}

echo "Email conectado: {$permiso->email_directivo}\n";
echo "Estado: " . ($permiso->activo ? 'ACTIVO' : 'INACTIVO') . "\n\n";

// PASO 1: Desconectar (desactivar)
echo "PASO 1: Desactivando permiso en BD...\n";
DB::table('directivos_calendario_permisos')
    ->where('id_permiso', $permiso->id_permiso)
    ->update([
        'activo' => 0,
        'google_access_token' => null,
        'google_refresh_token' => null,
        'token_expiracion' => null,
    ]);

echo "✅ Permisos desactivados y tokens borrados de BD\n\n";

// PASO 2: Generar nueva URL con prompt=consent
echo "PASO 2: Generando nueva URL de autenticación...\n";
echo "⚡ Esta vez se forzará que Google pida consentimiento nuevamente\n\n";

try {
    $service = app(\App\Services\GoogleCalendarService::class);
    
    // Generar state
    $directivo_id = 6;
    $state = base64_encode(random_bytes(32));
    
    // Guardar state en BD con expiración
    DB::table('oauth_states')->insert([
        'state' => $state,
        'directivo_id' => $directivo_id,
        'created_at' => DB::raw('GETDATE()'),
        'expires_at' => DB::raw('DATEADD(MINUTE, 15, GETDATE())'),
    ]);
    
    echo "✅ State generado: " . substr($state, 0, 20) . "...\n";
    echo "✅ State guardado en BD\n\n";
    
    // Generar URL con el nuevo state
    $authUrl = $service->generarUrlAutenticacion($state);
    
    echo "════════════════════════════════════════════════════════════\n";
    echo "📋 LINK PARA RE-AUTENTICAR (válido 15 minutos):\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo $authUrl . "\n";
    echo "════════════════════════════════════════════════════════════\n\n";
    
    // Intenta abrir en navegador
    $osType = strtoupper(substr(PHP_OS, 0, 3));
    if ($osType === 'WIN') {
        shell_exec("start " . escapeshellarg($authUrl));
        echo "🌐 Abriendo en navegador...\n\n";
    }
    
    echo "📌 INSTRUCCIONES:\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo "1. En el navegador, si aparece \"Continuar como altro@gmail.com\":\n";
    echo "   → Click en \"Usar otra cuenta\"\n";
    echo "   → Ingresa: guillermoavilamora2@gmail.com\n\n";
    echo "2. Click en \"Permitir\" en la pantalla de permisos\n\n";
    echo "3. ⚠️  IMPORTANTE: Si Google dice \"Acceso rápido\", verifica:\n";
    echo "   - Despliega arrow abajo para ver más opciones\n";
    echo "   - Busca opción de \"Desconectar\" o iniciar sesión con otra cuenta\n\n";
    echo "4. Automáticamente volverá a:\n";
    echo "   → http://localhost:8000/admin/calendario\n\n";
    
    echo "✅ Después, verifica con:\n";
    echo "   php diagnose_token_decrypt.php\n\n";
    
    echo "⏱️  El link expira en: 15 minutos\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
