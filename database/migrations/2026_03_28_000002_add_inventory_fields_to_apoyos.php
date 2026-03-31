<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregar campos para gestión de apoyos en especie
 * 
 * Campos:
 * - tipo_apoyo_detallado: ECONÓMICO | ESPECIE_KIT | ESPECIE_ÚNICO | SERVICIOS
 * - requiere_inventario: BIT (1 si necesita gestión de almacén)
 * - costo_promedio_unitario: MONEY (para análisis de especie)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Apoyos', function (Blueprint $table) {
            // Campo para especificar tipo de apoyo en más detalle
            if (!Schema::hasColumn('Apoyos', 'tipo_apoyo_detallado')) {
                $table->string('tipo_apoyo_detallado', 50)
                    ->nullable()
                    ->comment("ECONÓMICO | ESPECIE_KIT | ESPECIE_ÚNICO | SERVICIOS")
                    ->after('tipo_apoyo');
            }

            // Bandera para saber si necesita gestión de inventario
            if (!Schema::hasColumn('Apoyos', 'requiere_inventario')) {
                $table->boolean('requiere_inventario')
                    ->default(false)
                    ->comment("1 si el apoyo necesita gestión de almacén/inventario")
                    ->after('tipo_apoyo_detallado');
            }

            // Costo unitario promedio (para calcular valor de salida)
            if (!Schema::hasColumn('Apoyos', 'costo_promedio_unitario')) {
                $table->decimal('costo_promedio_unitario', 19, 4)
                    ->nullable()
                    ->comment("Costo promedio unitario para apoyos en especie (para auditoría)")
                    ->after('requiere_inventario');
            }

            // Agregar índice para búsquedas rápidas
            $table->index('requiere_inventario');
        });
    }

    public function down(): void
    {
        Schema::table('Apoyos', function (Blueprint $table) {
            $table->dropIndex(['requiere_inventario']);
            
            $table->dropColumn([
                'tipo_apoyo_detallado',
                'requiere_inventario',
                'costo_promedio_unitario'
            ]);
        });
    }
};
