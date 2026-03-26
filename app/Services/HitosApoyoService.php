/**
 * Servicio para validación y inserción de Hitos con consistencia de datos
 * Archivo: app/Services/HitosApoyoService.php
 * 
 * Responsabilidades:
 * - Validar completitud de datos antes de insertar
 * - Mapear campos del formulario a estructura de BD
 * - Validar consistencia con el modelo de datos
 * - Prevenir inserciones de valores NULL innecesarios
 */

<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class HitosApoyoService
{
    /**
     * Estructura válida esperada en la tabla Hitos_Apoyo
     */
    private const REQUIRED_COLUMNS = [
        'fk_id_apoyo',      // INT NOT NULL
        'titulo_hito',      // NVARCHAR(150) NOT NULL
        'fecha_creacion',   // DATETIME2 NOT NULL
        'es_base',          // BIT NOT NULL DEFAULT 0
        'activo',           // BIT NOT NULL DEFAULT 1
    ];

    private const OPTIONAL_COLUMNS = [
        'slug_hito',        // NVARCHAR(80) NULL
        'fecha_inicio',     // DATE NULL
        'fecha_fin',        // DATE NULL
        'orden',            // SMALLINT NOT NULL DEFAULT 0
        'fecha_actualizacion', // DATETIME2 NULL
    ];

    /**
     * Mapeo de nombres (compatibilidad con nombres antiguos)
     */
    private const COLUMN_ALIASES = [
        'nombre_hito' => 'titulo_hito',
        'clave_hito' => 'slug_hito',
        'orden_hito' => 'orden',
    ];

    /**
     * Validar estructura de BD contra columnas esperadas
     * 
     * @return array Reporte de validación
     */
    public static function validateTableSchema(): array
    {
        $report = [
            'tabla_existe' => Schema::hasTable('Hitos_Apoyo'),
            'columnas_requeridas' => [],
            'columnas_opcionales' => [],
            'columnas_extra' => [],
            'inconsistencias' => [],
        ];

        if (!$report['tabla_existe']) {
            $report['inconsistencias'][] = 'Tabla Hitos_Apoyo no existe';
            return $report;
        }

        $columnasDB = DB::select(
            "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_NAME = 'Hitos_Apoyo' 
             ORDER BY ORDINAL_POSITION"
        );

        $columnasActuales = collect($columnasDB)->pluck('COLUMN_NAME')->toArray();

        // Validar requeridas
        foreach (self::REQUIRED_COLUMNS as $col) {
            if (!in_array($col, $columnasActuales)) {
                $report['inconsistencias'][] = "Columna REQUERIDA falta: {$col}";
            } else {
                $info = collect($columnasDB)->firstWhere('COLUMN_NAME', $col);
                $report['columnas_requeridas'][$col] = [
                    'tipo' => $info->DATA_TYPE,
                    'permite_null' => $info->IS_NULLABLE === 'YES',
                ];
            }
        }

        // Validar opcionales
        foreach (self::OPTIONAL_COLUMNS as $col) {
            if (in_array($col, $columnasActuales)) {
                $info = collect($columnasDB)->firstWhere('COLUMN_NAME', $col);
                $report['columnas_opcionales'][$col] = [
                    'tipo' => $info->DATA_TYPE,
                    'permite_null' => $info->IS_NULLABLE === 'YES',
                ];
            }
        }

        // Detectar columnas extra
        $columnasEsperadas = array_merge(self::REQUIRED_COLUMNS, self::OPTIONAL_COLUMNS);
        foreach ($columnasActuales as $col) {
            if (!in_array($col, $columnasEsperadas) && $col !== 'id_hito') {
                $report['columnas_extra'][] = $col;
            }
        }

        return $report;
    }

    /**
     * Preparar y validar fila de hito antes de insertar
     * 
     * @param array $milestoneData Datos del hito del formulario
     * @param int $apoyoId ID del apoyo
     * @param int $order Orden del hito
     * 
     * @return array|null Fila validada y completa, o null si no es válida
     */
    public static function prepareHitoRow(array $milestoneData, int $apoyoId, int $order): ?array
    {
        // 1. Validar título (obligatorio)
        $titulo = trim((string) ($milestoneData['titulo'] ?? ''));
        if (empty($titulo)) {
            Log::warning('Hito sin título rechazado', ['milestone' => $milestoneData]);
            return null;
        }

        // 2. Procesar y validar fechas
        $fecha_inicio = null;
        $fecha_fin = null;

        if (!empty($milestoneData['fecha_inicio'])) {
            try {
                $fecha_inicio = Carbon::parse($milestoneData['fecha_inicio'])->toDateString();
            } catch (\Exception $e) {
                Log::warning('Fecha de inicio inválida', [
                    'valor' => $milestoneData['fecha_inicio'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($milestoneData['fecha_fin'])) {
            try {
                $fecha_fin = Carbon::parse($milestoneData['fecha_fin'])->toDateString();
            } catch (\Exception $e) {
                Log::warning('Fecha de fin inválida', [
                    'valor' => $milestoneData['fecha_fin'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Procesar slug (opcional)
        $slug = trim((string) ($milestoneData['slug'] ?? '')) ?: null;

        // 4. Construir fila con TODOS los campos
        $row = [
            'fk_id_apoyo' => $apoyoId,
            'titulo_hito' => $titulo,
            'slug_hito' => $slug,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'es_base' => (int)(! empty($milestoneData['es_base']) ? 1 : 0),
            'activo' => 1,  // Siempre activo en creación
            'orden' => $order,
            'fecha_creacion' => now(),
            // ⚠️ NO INCLUIR fecha_actualizacion - SQL Server usará DEFAULT NULL
        ];

        // 5. Agregar campos opcionales si existen en BD
        if (Schema::hasColumn('Hitos_Apoyo', 'clave_hito')) {
            $row['clave_hito'] = $slug ? strtoupper($slug) : null;
        }

        // 6. Aplicar aliases (compatibilidad con nombres antiguos)
        $rowFinal = [];
        foreach ($row as $key => $value) {
            // Si existe el alias, usar el nombre antiguo si la columna existe
            if (isset(self::COLUMN_ALIASES[$key])) {
                $aliasName = self::COLUMN_ALIASES[$key];
                if (Schema::hasColumn('Hitos_Apoyo', $key)) {
                    $rowFinal[$key] = $value;
                } elseif (Schema::hasColumn('Hitos_Apoyo', $aliasName)) {
                    $rowFinal[$aliasName] = $value;
                }
            } else {
                // Campo normal - solo agregar si existe
                if (Schema::hasColumn('Hitos_Apoyo', $key)) {
                    $rowFinal[$key] = $value;
                }
            }
        }

        return $rowFinal;
    }

    /**
     * Validar consistencia entre datos a insertar y estructura de BD
     * 
     * @param array $rowsToInsert Filas a insertar
     * 
     * @return array Reporte de validación
     */
    public static function validateConsistency(array $rowsToInsert): array
    {
        $schema = self::validateTableSchema();
        $report = [
            'es_valido' => true,
            'errores' => [],
            'advertencias' => [],
            'total_filas' => count($rowsToInsert),
        ];

        // 1. Si hay inconsistencias de esquema, detener
        if (!empty($schema['inconsistencias'])) {
            $report['es_valido'] = false;
            $report['errores'] = $schema['inconsistencias'];
            return $report;
        }

        // 2. Validar cada fila
        foreach ($rowsToInsert as $idx => $row) {
            $rowErrors = [];
            $rowWarnings = [];

            // Verificar que tenga título_hito
            if (empty($row['titulo_hito'])) {
                $rowErrors[] = "Fila {$idx}: titulo_hito vacío";
            }

            // Verificar que tenga fk_id_apoyo
            if (empty($row['fk_id_apoyo'])) {
                $rowErrors[] = "Fila {$idx}: fk_id_apoyo vacío";
            }

            // Validar que campos NULL estén permitidos
            foreach ($row as $key => $value) {
                if ($value === null) {
                    $info = $schema['columnas_opcionales'][$key] ?? null;
                    if ($info && !$info['permite_null']) {
                        $rowErrors[] = "Fila {$idx}: {$key} es NULL pero no permite NULL";
                    }
                }

                // Validar que campo exista en BD
                $columnExists = in_array($key, self::REQUIRED_COLUMNS) 
                    || isset($schema['columnas_opcionales'][$key])
                    || $key === 'fecha_actualizacion';  // Campo especial

                if (!$columnExists) {
                    $rowWarnings[] = "Fila {$idx}: Columna '{$key}' no existe en BD";
                }
            }

            if (!empty($rowErrors)) {
                $report['es_valido'] = false;
                $report['errores'] = array_merge($report['errores'], $rowErrors);
            }

            if (!empty($rowWarnings)) {
                $report['advertencias'] = array_merge($report['advertencias'], $rowWarnings);
            }
        }

        return $report;
    }

    /**
     * Insertar hitos con validación completa
     * 
     * @param array $milestones Hitos a insertar
     * @param int $apoyoId ID del apoyo
     * 
     * @return array Resultado de inserción
     */
    public static function insertHitosValidated(array $milestones, int $apoyoId): array
    {
        $result = [
            'exitoso' => false,
            'total_intentados' => 0,
            'total_insertados' => 0,
            'rechazados' => [],
            'errores' => [],
        ];

        // 1. Validar esquema de BD
        $schemaValidation = self::validateTableSchema();
        if (!empty($schemaValidation['inconsistencias'])) {
            $result['errores'] = $schemaValidation['inconsistencias'];
            return $result;
        }

        // 2. Preparar filas
        $rowsToInsert = [];
        $order = 1;

        foreach ($milestones as $idx => $milestone) {
            $include = ! array_key_exists('incluir', $milestone)
                || filter_var($milestone['incluir'], FILTER_VALIDATE_BOOLEAN);

            if (!$include) {
                $result['rechazados'][] = "Hito {$idx}: Marcado como no incluir";
                continue;
            }

            $row = self::prepareHitoRow($milestone, $apoyoId, $order);

            if ($row === null) {
                $result['rechazados'][] = "Hito {$idx}: No tiene título válido";
                continue;
            }

            $rowsToInsert[] = $row;
            $order++;
        }

        $result['total_intentados'] = count($milestones);

        // 3. Validar consistencia
        $consistency = self::validateConsistency($rowsToInsert);
        if (!$consistency['es_valido']) {
            $result['errores'] = $consistency['errores'];
            return $result;
        }

        // 4. Insertar
        try {
            if (!empty($rowsToInsert)) {
                DB::table('Hitos_Apoyo')->insert($rowsToInsert);
                $result['total_insertados'] = count($rowsToInsert);
                $result['exitoso'] = true;

                Log::info('Hitos insertados exitosamente', [
                    'apoyo_id' => $apoyoId,
                    'cantidad' => $result['total_insertados'],
                ]);
            }
        } catch (\Exception $e) {
            $result['errores'][] = 'Error al insertar: ' . $e->getMessage();
            Log::error('Error insertando hitos', [
                'apoyo_id' => $apoyoId,
                'error' => $e->getMessage(),
                'rows' => $rowsToInsert,
            ]);
        }

        return $result;
    }
}
