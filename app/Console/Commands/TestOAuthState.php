<?php

namespace App\Console\Commands;

use App\Models\OAuthState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TestOAuthState extends Command
{
    protected $signature = 'oauth:test';
    protected $description = 'Test OAuth State functionality';

    public function handle()
    {
        $this->info('=== Test OAuthState ===');
        
        // 1. Verificar tabla
        $this->info("\n1. Verificando tabla...");
        if (Schema::hasTable('oauth_states')) {
            $this->line("   ✅ Tabla oauth_states existe");
            $columns = Schema::getColumnListing('oauth_states');
            $this->line("   Columnas: " . implode(', ', $columns));
        } else {
            $this->error("   ❌ Tabla no existe");
            return 1;
        }
        
        // 2. Intentar generar un state
        $this->info("\n2. Generando state...");
        try {
            $state = OAuthState::generateState(6);
            $this->line("   ✅ State generado: " . substr($state, 0, 30) . "...");
            
            // Verificar que se guardó
            $record = OAuthState::where('state', $state)->first();
            if ($record) {
                $this->line("   ✅ Registrado en BD");
                $this->line("      - ID: " . $record->id);
                $this->line("      - Directivo: " . $record->directivo_id);
                $this->line("      - Expires: " . $record->expires_at);
                $this->line("      - Created: " . $record->created_at);
            } else {
                $this->error("   ❌ No se guardó en BD");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error: " . $e->getMessage());
            $this->error($e->getFile() . ":" . $e->getLine());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        // 3. Intentar validar un state
        $this->info("\n3. Validando state...");
        try {
            $valid = OAuthState::validateState($state);
            if ($valid) {
                $this->line("   ✅ State es válido");
            } else {
                $this->error("   ❌ State no es válido");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error en validación: " . $e->getMessage());
            return 1;
        }
        
        $this->info("\n✅ Test completado sin errores");
        return 0;
    }
}
