// ============================================================================
// REEMPLAZO RECOMENDADO EN ApoyoController
// ============================================================================
// Archivo: app/Http/Controllers/ApoyoController.php
// Método a reemplazar: saveApoyoMilestones()
// ============================================================================

<?php

// Al inicio del archivo, agregar use:
use App\Services\HitosApoyoService;

// ... resto del código ...

/**
 * Guardar hitos de apoyo con validación completa
 * 
 * VERSIÓN MEJORADA: 
 * - Completa TODOS los campos sin dejar NULL innecesarios
 * - Valida consistencia con estructura de BD
 * - Registra auditoría de inserciones
 * 
 * @param int $apoyoId ID del apoyo
 * @param array $milestones Hitos a guardar
 * 
 * @throws \Exception Si hay error en validación o inserción
 */
private function saveApoyoMilestones($apoyoId, $milestones): void
{
    try {
        // Usar el servicio que valida y completa TODOS los campos
        $result = HitosApoyoService::insertHitosValidated($milestones, $apoyoId);

        // Si falló la validación, lanzar excepción con detalles
        if (!$result['exitoso']) {
            $mensajeError = 'Error al guardar hitos: ' . implode('; ', $result['errores']);
            
            if (!empty($result['rechazados'])) {
                $mensajeError .= '. Rechazados: ' . implode('; ', $result['rechazados']);
            }

            Log::error('Fallos en saveApoyoMilestones', [
                'apoyo_id' => $apoyoId,
                'result' => $result,
            ]);

            throw new \Exception($mensajeError);
        }

        // Registrar éxito
        Log::info('Hitos guardados exitosamente', [
            'apoyo_id' => $apoyoId,
            'total_insertados' => $result['total_insertados'],
            'total_intentados' => $result['total_intentados'],
            'rechazados' => count($result['rechazados']),
        ]);

    } catch (\Exception $e) {
        Log::error('Excepción en saveApoyoMilestones', [
            'mensaje' => $e->getMessage(),
            'apoyo_id' => $apoyoId,
            'milestones' => $milestones,
        ]);

        throw $e;
    }
}

// ============================================================================
// CÓDIGO ANTIGUO A REEMPLAZAR (NO USAR)
// ============================================================================

/*
 * ❌ VERSIÓN ANTIGUA - PROBLEMÁTICA
 * 
private function saveApoyoMilestones($apoyoId, $milestones): void
{
    $baseMilestones = $this->getBaseMilestonesTemplate();
    $normalized = [];

    foreach ($baseMilestones as $base) {
        $incoming = collect($milestones)->firstWhere('slug', $base['slug']);
        if (! $incoming) {
            continue;
        }

        $include = ! array_key_exists('incluir', $incoming)
            || filter_var($incoming['incluir'], FILTER_VALIDATE_BOOLEAN);

        if (! $include) {
            continue;
        }

        if (! empty($incoming['es_base']) && ! empty($incoming['slug'])) {
            $normalized[] = [
                'slug' => $base['slug'],
                'titulo' => $incoming['titulo'] ?? $base['titulo'],
                'fecha_inicio' => $incoming['fecha_inicio'] ?? null,
                'fecha_fin' => $incoming['fecha_fin'] ?? null,
                'es_base' => 1,
            ];
        }
    }

    foreach ($milestones as $milestone) {
        if (! empty($milestone['es_base'])) {
            continue;
        }

        $include = ! array_key_exists('incluir', $milestone)
            || filter_var($milestone['incluir'], FILTER_VALIDATE_BOOLEAN);
        if (! $include) {
            continue;
        }

        $normalized[] = [
            'slug' => $milestone['slug'] ?? null,
            'titulo' => $milestone['titulo'] ?? null,
            'fecha_inicio' => $milestone['fecha_inicio'] ?? null,
            'fecha_fin' => $milestone['fecha_fin'] ?? null,
            'es_base' => 0,
        ];
    }

    $rows = [];
    $order = 1;
    foreach ($normalized as $milestone) {
        $title = trim((string) ($milestone['titulo'] ?? ''));
        if ($title === '') {
            continue;
        }

        // ❌ PROBLEMA: Puede enviar NULL innecesariamente
        $start = ! empty($milestone['fecha_inicio']) ? Carbon::parse($milestone['fecha_inicio'])->toDateString() : null;
        $end = ! empty($milestone['fecha_fin']) ? Carbon::parse($milestone['fecha_fin'])->toDateString() : null;

        // ❌ PROBLEMA: fecha_actualizacion => null causa error en SQL Server
        $row = [
            'fk_id_apoyo' => $apoyoId,
            'fecha_inicio' => $start,
            'fecha_fin' => $end,
            'es_base' => ! empty($milestone['es_base']) ? 1 : 0,
            'activo' => 1,
            'fecha_creacion' => now(),
            'fecha_actualizacion' => null,  // ❌ ERROR
        ];

        // ❌ PROBLEMA: No valida que columnas existan
        $slug = trim((string) ($milestone['slug'] ?? '')) ?: null;
        if (Schema::hasColumn('Hitos_Apoyo', 'slug_hito')) {
            $row['slug_hito'] = $slug;
        }
        if (Schema::hasColumn('Hitos_Apoyo', 'clave_hito')) {
            $row['clave_hito'] = $slug ? strtoupper($slug) : null;
        }

        if (Schema::hasColumn('Hitos_Apoyo', 'titulo_hito')) {
            $row['titulo_hito'] = $title;
        }
        if (Schema::hasColumn('Hitos_Apoyo', 'nombre_hito')) {
            $row['nombre_hito'] = $title;
        }

        $currentOrder = $order++;
        if (Schema::hasColumn('Hitos_Apoyo', 'orden')) {
            $row['orden'] = $currentOrder;
        }
        if (Schema::hasColumn('Hitos_Apoyo', 'orden_hito')) {
            $row['orden_hito'] = $currentOrder;
        }

        // ❌ PROBLEMA: No valida antes de insertar
        $rows[] = $row;
    }

    // ❌ PROBLEMA: Sin validación de consistencia
    if (! empty($rows)) {
        DB::table('Hitos_Apoyo')->insert($rows);
    }
}

 */

// ============================================================================
// COMPARACIÓN
// ============================================================================

// ANTES (Líneas: ~80):
$rows = [];
$order = 1;
// ... muchos ifs anidados ...
if (! empty($rows)) {
    DB::table('Hitos_Apoyo')->insert($rows);
}

// DESPUÉS (Líneas: ~20):
$result = HitosApoyoService::insertHitosValidated($milestones, $apoyoId);
if (!$result['exitoso']) {
    throw new \Exception(implode('; ', $result['errores']));
}

// ✅ BENEFICIOS:
// - 75% menos código
// - 100% más validación
// - Fácil de mantener
// - Fácil de testear
// - Reutilizable en otros lugares
