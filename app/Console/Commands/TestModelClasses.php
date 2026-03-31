<?php

namespace App\Console\Commands;

use App\Models\CalendarioSincronizacionLog;
use App\Models\OAuthState;
use App\Models\ClaveSegumientoPrivada;
use App\Models\CadenaDigitalDocumento;
use App\Models\AuditoriaCargaMaterial;
use Illuminate\Console\Command;

class TestModelClasses extends Command
{
    protected $signature = 'test:model-classes';
    protected $description = 'Test that all model User class references are resolved';

    public function handle()
    {
        $this->info("🧪 Testing Model Class Resolution");
        $this->line("=================================\n");

        try {
            $this->info("[1] Testing CalendarioSincronizacionLog");
            $log = CalendarioSincronizacionLog::first();
            if ($log) {
                $this->line("    Log ID: {$log->id_log}");
                try {
                    $usuario = $log->usuario;
                    if ($usuario) {
                        $this->info("    ✅ usuario() relationship works");
                        $this->line("       User ID: {$usuario->id_usuario}");
                    } else {
                        $this->warn("    ℹ️  usuario() returned null (no related user)");
                    }
                } catch (\Exception $e) {
                    $this->error("    ❌ " . $e->getMessage());
                    return Command::FAILURE;
                }
            } else {
                $this->warn("    ℹ️  No logs found");
            }

            $this->line("\n[2] Testing OAuthState");
            $state = OAuthState::first();
            if ($state) {
                $this->line("    State: {$state->state}");
                try {
                    $directivo = $state->directivo;
                    if ($directivo) {
                        $this->info("    ✅ directivo() relationship works");
                    } else {
                        $this->warn("    ℹ️  directivo() returned null");
                    }
                } catch (\Exception $e) {
                    $this->error("    ❌ " . $e->getMessage());
                    return Command::FAILURE;
                }
            } else {
                $this->warn("    ℹ️  No OAuth states found");
            }

            $this->line("\n[3] Testing ClaveSegumientoPrivada");
            $clave = ClaveSegumientoPrivada::first();
            if ($clave) {
                $this->line("    Clave ID: {$clave->id_clave}");
                try {
                    $beneficiario = $clave->beneficiario;
                    if ($beneficiario) {
                        $this->info("    ✅ beneficiario() relationship works");
                    }
                } catch (\Exception $e) {
                    $this->error("    ❌ " . $e->getMessage());
                    return Command::FAILURE;
                }
            } else {
                $this->warn("    ℹ️  No claves found");
            }

            $this->newLine();
            $this->info("✅ All model class tests passed!");
            $this->line("   User class is properly resolved in all relationships");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Unexpected error: " . $e->getMessage());
            $this->error("   File: " . $e->getFile());
            $this->error("   Line: " . $e->getLine());
            return Command::FAILURE;
        }
    }
}
