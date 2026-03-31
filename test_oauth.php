<?php
require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\OAuthState;

try {
    echo "=== Verificación OAuth States ===\n\n";
    
    // 1. Verificar tabla
    if (Schema::hasTable('oauth_states')) {
        echo "✅ Tabla oauth_states existe\n";
    } else {
        echo "❌ Tabla oauth_states NO existe\n";
        exit(1);
    }
    
    // 2. Verificar número de filas
    $count = DB::table('oauth_states')->count();
    echo "📊 Filas en oauth_states: $count\n";
    
    // 3. Verificar última fila
    $last = DB::table('oauth_states')->latest('id')->first();
    if ($last) {
        echo "📝 Último state:\n";
        echo "   - ID: {$last->id}\n";
        echo "   - State: " . substr($last->state, 0, 20) . "...\n";
        echo "   - Directivo: {$last->directivo_id}\n";
        echo "   - Expires: {$last->expires_at}\n";
        echo "   - Used: {$last->used_at}\n";
    }
    
    // 4. Intentar generar un state  
    echo "\n=== Intentando generar state ===\n";
    try {
        $state = OAuthState::generateState(6, 'google', 30);
        echo "✅ State generado: " . substr($state, 0, 20) . "...\n";
        
        // Verificar que se guardó
        $saved = DB::table('oauth_states')->where('state', $state)->first();
        if ($saved) {
            echo "✅ State guardado en BD\n";
        } else {
            echo "❌ State NO se guardó en BD\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error al generar: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
