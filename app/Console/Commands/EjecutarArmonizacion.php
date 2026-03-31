<?php
/**
 * Artisan Command to Execute Harmonization
 * php artisan armonizacion:ejecutar
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EjecutarArmonizacion extends Command
{
    protected $signature = 'armonizacion:ejecutar';
    protected $description = 'Ejecutar script de armonización BD SIGO';

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📊 EJECUTANDO ARMONIZACIÓN BD SIGO');
        $this->info('═══════════════════════════════════════════════════════════════');

        $sqlFile = base_path('ARMONIZACION_BD_SIGO.sql');

        if (!file_exists($sqlFile)) {
            $this->error("❌ Archivo $sqlFile no encontrado");
            return 1;
        }

        $sqlContent = file_get_contents($sqlFile);
        $statements = preg_split('/^\s*GO\s*$/m', $sqlContent);

        $executed = 0;
        $errors = [];

        foreach ($statements as $idx => $statement) {
            $statement = trim($statement);

            // Ignorar líneas vacías y comentarios
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            // Remover comentarios de línea
            $statement = preg_replace('/^--.*$/m', '', $statement);
            $statement = trim($statement);

            if (empty($statement)) {
                continue;
            }

            try {
                DB::statement($statement);
                $executed++;
                $this->line("✅ Ejecutado bloque $idx");
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();

                // Ignorar errores no críticos
                if (stripos($errorMsg, 'already exists') !== false || 
                    stripos($errorMsg, 'ya existe') !== false ||
                    stripos($errorMsg, 'UNIQUE') !== false) {
                    $this->warn("⚠️  Bloque $idx: Elemento ya existe");
                } else {
                    $errors[] = "Bloque $idx: " . substr($errorMsg, 0, 150);
                    $this->error("❌ Error: " . substr($errorMsg, 0, 100));
                }
            }
        }

        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('📈 RESUMEN DE EJECUCIÓN');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->line('Bloques ejecutados: ' . $executed);
        $this->line('Errores críticos: ' . count($errors));

        // Validación Final
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('✅ VALIDACIÓN FINAL');
        $this->info('═══════════════════════════════════════════════════════════════');

        // Documentos_Expediente
        try {
            $result = DB::select("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'Documentos_Expediente' 
                ORDER BY ORDINAL_POSITION
            ");
            $columnNames = array_map(fn($col) => $col->COLUMN_NAME, $result);
            
            $this->line('');
            $this->info('✅ DOCUMENTOS_EXPEDIENTE:');
            $requiredFields = ['origen_carga', 'cargado_por', 'justificacion_carga_fria', 'marca_agua_aplicada', 'qr_seguimiento'];
            foreach ($requiredFields as $field) {
                $exists = in_array($field, $columnNames) ? '✓' : '✗';
                $this->line("   $exists $field");
            }
        } catch (\Exception $e) {
            $this->error('Error verificando Documentos_Expediente');
        }

        // Apoyos
        try {
            $result = DB::select("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'Apoyos' 
                ORDER BY ORDINAL_POSITION
            ");
            $columnNames = array_map(fn($col) => $col->COLUMN_NAME, $result);
            
            $this->line('');
            $this->info('✅ APOYOS:');
            $requiredFields = ['tipo_apoyo_detallado', 'requiere_inventario', 'costo_promedio_unitario'];
            foreach ($requiredFields as $field) {
                $exists = in_array($field, $columnNames) ? '✓' : '✗';
                $this->line("   $exists $field");
            }
        } catch (\Exception $e) {
            $this->error('Error verificando Apoyos');
        }

        // Estados
        try {
            $result = DB::select("
                SELECT id_estado, nombre_estado 
                FROM Cat_EstadosSolicitud 
                ORDER BY id_estado
            ");
            
            $this->line('');
            $this->info('✅ ESTADOS EN CAT_ESTADOSSOLICITUD:');
            foreach ($result as $state) {
                $this->line("   ID {$state->id_estado}: {$state->nombre_estado}");
            }
        } catch (\Exception $e) {
            $this->error('Error verificando estados');
        }

        // Tablas de Inventario
        try {
            $result = DB::select("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = 'dbo' 
                AND TABLE_NAME IN (
                    'auditorias_carga_fria',
                    'consentimientos_carga_fria',
                    'inventario_material',
                    'componentes_apoyo',
                    'ordenes_compra_interno',
                    'recepciones_material',
                    'facturas_compra',
                    'movimientos_inventario',
                    'salidas_beneficiarios',
                    'detalle_salida_beneficiarios',
                    'auditorias_salida_material',
                    'politicas_retencion_datos',
                    'solicitudes_arco'
                ) 
                ORDER BY TABLE_NAME
            ");
            
            $tableNames = array_map(fn($t) => $t->TABLE_NAME, $result);
            
            $this->line('');
            $this->info('✅ TABLAS DE INVENTARIO Y CARGA FRÍA:');
            $expectedTables = [
                'auditorias_carga_fria',
                'consentimientos_carga_fria',
                'inventario_material',
                'componentes_apoyo',
                'ordenes_compra_interno',
                'recepciones_material',
                'facturas_compra',
                'movimientos_inventario',
                'salidas_beneficiarios',
                'detalle_salida_beneficiarios',
                'auditorias_salida_material',
                'politicas_retencion_datos',
                'solicitudes_arco'
            ];
            
            foreach ($expectedTables as $table) {
                $exists = in_array($table, $tableNames) ? '✓' : '✗';
                $this->line("   $exists $table");
            }
        } catch (\Exception $e) {
            $this->error('Error verificando tablas');
        }

        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('🎉 ¡ARMONIZACIÓN COMPLETADA!');
        $this->info('═══════════════════════════════════════════════════════════════');

        return 0;
    }
}
