<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateBDStructure extends Command
{
    protected $signature = 'bd:validate';
    protected $description = 'Validar estructura actual de BD vs Metodología';

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📊 ANÁLISIS DE ESTRUCTURA BD - SIGO');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // 1. LISTAR TODAS LAS TABLAS
        $this->info('✅ TABLAS EN BD_SIGO:');
        $tables = Schema::getTableListing();
        
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $this->line("   • {$table} ({$count} registros)");
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📋 ESTRUCTURA: Solicitudes');
        $this->info('═══════════════════════════════════════════════════════════════');

        $columns = Schema::getColumns('Solicitudes');
        foreach ($columns as $col) {
            $nullable = $col['nullable'] ? 'NULL' : 'NOT NULL';
            $this->line("   • {$col['name']}: {$col['type']} | {$nullable}");
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📋 ESTRUCTURA: Documentos_Expediente');
        $this->info('═══════════════════════════════════════════════════════════════');

        if (Schema::hasTable('Documentos_Expediente')) {
            $columns = Schema::getColumns('Documentos_Expediente');
            foreach ($columns as $col) {
                $nullable = $col['nullable'] ? 'NULL' : 'NOT NULL';
                $this->line("   • {$col['name']}: {$col['type']} | {$nullable}");
            }
        } else {
            $this->warn('   ❌ Tabla Documentos_Expediente NO existe');
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📋 ESTRUCTURA: Apoyos');
        $this->info('═══════════════════════════════════════════════════════════════');

        if (Schema::hasTable('Apoyos')) {
            $columns = Schema::getColumns('Apoyos');
            foreach ($columns as $col) {
                $nullable = $col['nullable'] ? 'NULL' : 'NOT NULL';
                $this->line("   • {$col['name']}: {$col['type']} | {$nullable}");
            }
        } else {
            $this->warn('   ❌ Tabla Apoyos NO existe');
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📝 ESTADOS EN Cat_EstadosSolicitud');
        $this->info('═══════════════════════════════════════════════════════════════');

        if (Schema::hasTable('Cat_EstadosSolicitud')) {
            $estados = DB::table('Cat_EstadosSolicitud')->get();
            foreach ($estados as $estado) {
                $this->line("   • ID {$estado->id_estado}: {$estado->nombre_estado}");
            }
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📊 VALIDACIÓN DE CAMPOS FALTANTES');
        $this->info('═══════════════════════════════════════════════════════════════');

        $missing = [];
        
        // Validar Documentos_Expediente
        if (Schema::hasTable('Documentos_Expediente')) {
            if (!Schema::hasColumn('Documentos_Expediente', 'origen_carga')) {
                $missing[] = "❌ Documentos_Expediente.origen_carga";
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'cargado_por')) {
                $missing[] = "❌ Documentos_Expediente.cargado_por";
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'justificacion_carga_fria')) {
                $missing[] = "❌ Documentos_Expediente.justificacion_carga_fria";
            }
        }

        // Validar Apoyos
        if (Schema::hasTable('Apoyos')) {
            if (!Schema::hasColumn('Apoyos', 'tipo_apoyo_detallado')) {
                $missing[] = "❌ Apoyos.tipo_apoyo_detallado";
            }
            if (!Schema::hasColumn('Apoyos', 'requiere_inventario')) {
                $missing[] = "❌ Apoyos.requiere_inventario";
            }
            if (!Schema::hasColumn('Apoyos', 'costo_promedio_unitario')) {
                $missing[] = "❌ Apoyos.costo_promedio_unitario";
            }
        }

        // Validar Google Drive
        if (!Schema::hasTable('google_drive_audit_logs')) {
            $missing[] = "❌ Tabla google_drive_audit_logs NO EXISTE";
        }

        // Validar tablas de Carga Fría
        if (!Schema::hasTable('auditorias_carga_fria')) {
            $missing[] = "❌ Tabla auditorias_carga_fria NO EXISTE";
        }
        if (!Schema::hasTable('consentimientos_carga_fria')) {
            $missing[] = "❌ Tabla consentimientos_carga_fria NO EXISTE";
        }

        if (count($missing) > 0) {
            foreach ($missing as $item) {
                $this->warn("   {$item}");
            }
        } else {
            $this->info("   ✅ Todos los campos requeridos existen");
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
    }
}
