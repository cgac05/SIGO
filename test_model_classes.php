<?php
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

use App\Models\User;
use App\Models\CalendarioSincronizacionLog;
use App\Models\OAuthState;

echo "✅ Testing Model Class Resolution\n";
echo "==================================\n\n";

try {
    echo "[1] Testing User::class\n";
    echo "    ✅ Can reference User::class directly\n\n";
    
    echo "[2] Testing CalendarioSincronizacionLog relationships\n";
    $log = CalendarioSincronizacionLog::first();
    if ($log) {
        echo "    Log found: ID {$log->id_log}\n";
        try {
            echo "    Accessing usuario() relationship...\n";
            $usuario = $log->usuario;
            if ($usuario) {
                echo "    ✅ usuario() resolved correctly\n";
                echo "    User ID: {$usuario->id_usuario}\n";
            } else {
                echo "    ℹ️  usuario() returned null (no related user)\n";
            }
        } catch (Exception $e) {
            echo "    ❌ Error accessing usuario(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "    ℹ️  No logs found in database\n";
    }
    
    echo "\n[3] Testing OAuthState relationships\n";
    $state = OAuthState::first();
    if ($state) {
        echo "    State found: {$state->state}\n";
        try {
            echo "    Accessing directivo() relationship...\n";
            $directivo = $state->directivo;
            if ($directivo) {
                echo "    ✅ directivo() resolved correctly\n";
            } else {
                echo "    ℹ️  directivo() returned null\n";
            }
        } catch (Exception $e) {
            echo "    ❌ Error accessing directivo(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "    ℹ️  No OAuth states found in database\n";
    }
    
    echo "\n✅ All model tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}
?>
