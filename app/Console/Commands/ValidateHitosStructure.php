<?php

namespace App\Console\Commands;

use App\Services\HitosApoyoService;
use Illuminate\Console\Command;

class ValidateHitosStructure extends Command
{
    /**
     * Nombre y descripción del comando
     */
    protected $signature = 'validate:hitos-structure';
    protected $description = 'Valida la estructura de la tabla Hitos_Apoyo y consistencia de datos';

    /**
     * Ejecutar el comando
     */
    public function handle(): int
    {
        $this->info('🔍 Validando estructura de tabla Hitos_Apoyo...');
        $this->line('');

        // Validar esquema
        $schemaResult = HitosApoyoService::validateTableSchema();

        if (!$schemaResult['tabla_existe']) {
            $this->error('❌ Tabla Hitos_Apoyo no existe');
            return 1;
        }

        $this->info('✅ Tabla existe');

        // Mostrar columnas requeridas
        $this->line('');
        $this->info('📋 COLUMNAS REQUERIDAS:');
        foreach ($schemaResult['columnas_requeridas'] as $col => $info) {
            $nullable = $info['permite_null'] ? '(NULL)' : '(NOT NULL)';
            $this->line("  ✓ {$col} [{$info['tipo']}] {$nullable}");
        }

        // Mostrar columnas opcionales
        if (!empty($schemaResult['columnas_opcionales'])) {
            $this->line('');
            $this->info('📝 COLUMNAS OPCIONALES:');
            foreach ($schemaResult['columnas_opcionales'] as $col => $info) {
                $nullable = $info['permite_null'] ? '(NULL)' : '(NOT NULL)';
                $this->line("  ○ {$col} [{$info['tipo']}] {$nullable}");
            }
        }

        // Mostrar columnas extra
        if (!empty($schemaResult['columnas_extra'])) {
            $this->line('');
            $this->warn('⚠️  COLUMNAS EXTRA (No mapeadas):');
            foreach ($schemaResult['columnas_extra'] as $col) {
                $this->line("  ⚠ {$col}");
            }
        }

        // Mostrar inconsistencias
        if (!empty($schemaResult['inconsistencias'])) {
            $this->line('');
            $this->error('❌ INCONSISTENCIAS ENCONTRADAS:');
            foreach ($schemaResult['inconsistencias'] as $error) {
                $this->line("  • {$error}");
            }
            return 1;
        }

        $this->line('');
        $this->info('✅ Estructura validada exitosamente');

        return 0;
    }
}
