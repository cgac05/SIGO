<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agregar nuevos estados para flujos de Carga Fría y Digitación de Expediente
 * 
 * Estados a agregar:
 * - DOCUMENTOS_CARGADOS_ADMIN (cuando admin inicia carga en nombre beneficiario)
 * - CONSENTIDO_BENEFICIARIO (cuando beneficiario aprueba carga fría posterior)
 * - RECHAZADO_POR_BENEFICIARIO (cuando beneficiario rechaza carga fría)
 * - EXPEDIENTE_CREADO (cuando admin verifica y crea expediente)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar que la tabla existe
        if (Schema::hasTable('Cat_EstadosSolicitud')) {
            // Estados actuales según BD:
            // 1: Pendiente
            // 2: Validado
            // 3: En Subsanación
            // 4: Aprobado
            // 5: Rechazado

            // Agregar nuevos estados (partiendo de donde terminó)
            DB::table('Cat_EstadosSolicitud')->updateOrInsert(
                ['id_estado' => 6],
                ['nombre_estado' => 'Expediente Creado']
            );

            DB::table('Cat_EstadosSolicitud')->updateOrInsert(
                ['id_estado' => 7],
                ['nombre_estado' => 'Documentos Cargados Admin']
            );

            DB::table('Cat_EstadosSolicitud')->updateOrInsert(
                ['id_estado' => 8],
                ['nombre_estado' => 'Consentido Beneficiario']
            );

            DB::table('Cat_EstadosSolicitud')->updateOrInsert(
                ['id_estado' => 9],
                ['nombre_estado' => 'Rechazado por Beneficiario']
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Cat_EstadosSolicitud')) {
            DB::table('Cat_EstadosSolicitud')
                ->whereIn('id_estado', [6, 7, 8, 9])
                ->delete();
        }
    }
};
