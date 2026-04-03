<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EjecutarSetupPresupuesto extends Command
{
    protected $signature = 'presupuesto:ejecutar-setup {--sql-file=SQL_PRESUPUESTO_SETUP.sql}';
    protected $description = 'Ejecuta el setup de presupuestación desde archivo SQL';

    public function handle()
    {
        $this->info('🚀 Iniciando setup de presupuestación...');

        $sqlFile = base_path($this->option('sql-file'));

        if (!File::exists($sqlFile)) {
            $this->error("❌ Archivo no encontrado: {$sqlFile}");
            return 1;
        }

        $this->info("📋 Leyendo archivo SQL: {$sqlFile}");
        $sqlContent = File::get($sqlFile);

        // Dividir por GO (batch separator en SQL Server)
        $batches = preg_split('/^\s*GO\s*$/mi', $sqlContent);

        $successful = 0;
        $failed = 0;

        foreach ($batches as $index => $batch) {
            $batch = trim($batch);
            
            // Saltar comentarios y batch vacíos
            if (empty($batch) || preg_match('/^--/', $batch)) {
                continue;
            }

            try {
                $this->line("  [" . ($index + 1) . "] Ejecutando batch...");
                
                // Ejecutar el batch
                DB::transaction(function () use ($batch) {
                    DB::statement($batch);
                });

                $this->line("  ✅ Batch exitoso");
                $successful++;
                
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                
                // Si es error de permisos CREATE TABLE, advertir pero continuar
                if (str_contains($errorMsg, 'CREATE TABLE') && str_contains($errorMsg, 'denegado')) {
                    $this->warn("  ⚠️  Permiso denegado para CREATE TABLE (esperado si usuario no es admin)");
                    $failed++;
                } 
                // Si la tabla ya existe, es OK
                else if (str_contains($errorMsg, 'objeto de base de datos ya existe') || 
                         str_contains($errorMsg, 'already exists')) {
                    $this->line("  ℹ️  Tabla ya existe (OK)");
                    $successful++;
                }
                // Si es error de constraints o referencias, mostrar
                else if (str_contains($errorMsg, 'FOREIGN KEY') || str_contains($errorMsg, 'constraint')) {
                    $this->warn("  ⚠️  Error de constraint (puede que tablas ya existan): " . substr($errorMsg, 0, 100));
                    $failed++;
                }
                else {
                    $this->error("  ❌ Error: " . substr($errorMsg, 0, 150));
                    $this->line("     Batch: " . substr($batch, 0, 100) . "...");
                    $failed++;
                }
            }
        }

        $this->info('');
        $this->info("═════════════════════════════════════════");
        $this->info("✅ Batches exitosos: {$successful}");
        $this->warn("⚠️  Batches con problemas: {$failed}");
        $this->info("═════════════════════════════════════════");

        if ($failed > 0) {
            $this->warn("\n⚠️  ADVERTENCIA: Algunos batches fallaron.");
            $this->warn("   Si es por permisos CREATE TABLE, necesitas:");
            $this->warn("   1. Abrir SQL Server Management Studio");
            $this->warn("   2. Conectar como admin (sa)");
            $this->warn("   3. Ejecutar: SQL_PRESUPUESTO_SETUP.sql");
            $this->warn("   4. Después ejecutar: php artisan presupuesto:cargar --año=2026");
            return 1;
        }

        $this->info("\n✅ Setup presupuestación completado!");
        return 0;
    }
}
