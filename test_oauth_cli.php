#!/usr/bin/env php
<?php

$_SERVER['APP_ENV'] ??= 'local';

require(__DIR__.'/vendor/autoload.php');

use Illuminate\Support\Facades\DB;
use App\Models\OAuthState;
use Illuminate\Support\Facades\Schema;

$app = require_once(__DIR__.'/bootstrap/app.php');

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    echo "=== Test OAuthState ===\n";
    
    // 1. Verificar tabla
    echo "\n1. Verificando tabla...\n";
    if (Schema::hasTable('oauth_states')) {
        echo "   ✅ Tabla oauth_states existe\n";
        
        // Ver estructura
        $columns = Schema::getColumnListing('oauth_states');
        echo "   Columnas: " . implode(', ', $columns) . "\n";
    } else {
        echo "   ❌ Tabla no existe\n";
        exit(1);
    }
    
    // 2. Intentar generar un state
    echo "\n2. Generando state...\n";
    try {
        $state = OAuthState::generateState(6);
        echo "   ✅ State generado: " . substr($state, 0, 30) . "...\n";
        
        // Verificar que se guardó
        $record = OAuthState::where('state', $state)->first();
        if ($record) {
            echo "   ✅ Registrado en BD\n";
            echo "      - ID: " . $record->id . "\n";
            echo "      - Directivo: " . $record->directivo_id . "\n";
            echo "      - Expires: " . $record->expires_at . "\n";
        } else {
            echo "   ❌ No se guardó en BD\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    // 3. Intentar validar un state
    echo "\n3. Validando state...\n";
    try {
        $valid = OAuthState::validateState($state);
        if ($valid) {
            echo "   ✅ State es válido\n";
        } else {
            echo "   ❌ State no es válido\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Error en validación: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Test completado\n";
    
} catch (\Exception $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo $e->getTraceAsString();
    exit(1);
}

