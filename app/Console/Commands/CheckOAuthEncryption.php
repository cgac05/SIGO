<?php

namespace App\Console\Commands;

use App\Models\DirectivoCalendarioPermiso;
use Illuminate\Console\Command;

class CheckOAuthEncryption extends Command
{
    protected $signature = 'check:oauth-encryption';
    protected $description = 'Verify OAuth token encryption/decryption';

    public function handle()
    {
        $this->line("🔐 Verificando encriptación de tokens OAuth");
        $this->line("==========================================\n");

        $permiso = DirectivoCalendarioPermiso::first();

        if (!$permiso) {
            $this->error("No hay registros de permisos de calendario");
            return;
        }

        $this->line("Email: {$permiso->email_directivo}");
        $this->line("Activo: " . ($permiso->activo ? "✅" : "❌"));
        $this->newLine();

        // Check raw token
        $this->line("📊 Token en BD (valores raw):");
        $this->line("  google_access_token: " . (strlen($permiso->google_access_token ?? '') > 0 ? "✅ " . strlen($permiso->google_access_token) . " chars" : "❌ NULL"));
        $this->line("  google_refresh_token: " . (strlen($permiso->google_refresh_token ?? '') > 0 ? "✅ " . strlen($permiso->google_refresh_token) . " chars" : "❌ NULL"));
        $this->newLine();

        // Try to decrypt
        $this->line("🔓 Intentando desencriptar:");

        try {
            if ($permiso->google_refresh_token) {
                $refreshDecrypted = decrypt($permiso->google_refresh_token);
                $this->line("  Refresh token desencriptado: " . (strlen($refreshDecrypted) > 0 ? "✅ " . strlen($refreshDecrypted) . " chars" : "❌ VACÍO"));
                
                if (strlen($refreshDecrypted) > 0) {
                    // Try to parse as JSON
                    $tokenData = json_decode($refreshDecrypted, true);
                    if ($tokenData) {
                        $this->comment("  Token parsed as JSON ✅");
                        $this->line("    Claves: " . implode(", ", array_keys($tokenData)));
                    } else {
                        $this->warn("  Token NO es JSON válido");
                    }
                }
            } else {
                $this->error("  google_refresh_token es NULL");
            }
        } catch (\Exception $e) {
            $this->error("  Error al desencriptar: " . $e->getMessage());
        }

        $this->newLine();
        $this->line("🔍 Resumen:");
        if (!$permiso->google_refresh_token) {
            $this->error("   ❌ No hay refresh token en BD");
        } else {
            try {
                $refreshDecrypted = decrypt($permiso->google_refresh_token);
                if ($refreshDecrypted) {
                    $this->info("   ✅ Token desencriptado correctamente");
                    $this->line("   ➡️  OAuth debe funcionar");
                } else {
                    $this->error("   ❌ Token desencriptado pero VACÍO");
                    $this->line("   ➡️  Problema: Clave de encriptación incorrecta");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Error al desencriptar: {$e->getMessage()}");
                $this->line("   ➡️  Problema: APP_KEY inválida o token corrupto");
            }
        }

        return Command::SUCCESS;
    }
}
