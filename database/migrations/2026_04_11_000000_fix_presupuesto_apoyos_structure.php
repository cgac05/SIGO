<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ Esta migración es adaptativa para la estructura existente
        // La tabla presupuesto_apoyos ya existe con estructura:
        // id_apoyo_presupuesto, folio, id_categoria, monto_solicitado, 
        // monto_aprobado, estado, fecha_solicitud, etc.

        // Simplemente asegurar que las columnas críticas existan
        if (!Schema::hasTable('presupuesto_apoyos')) {
            // Si no existe (nunca debería pasar), crearla
            Schema::create('presupuesto_apoyos', function (Blueprint $table) {
                $table->id('id_apoyo_presupuesto');
                $table->string('folio');
                $table->unsignedBigInteger('id_categoria');
                $table->decimal('monto_solicitado', 15, 2);
                $table->decimal('monto_aprobado', 15, 2)->default(0);
                $table->string('estado')->default('PENDIENTE');
                $table->timestamp('fecha_solicitud')->useCurrent();
                $table->timestamp('fecha_aprobacion')->nullable();
                $table->string('aprobado_por')->nullable();
                $table->timestamps();

                $table->index('folio');
                $table->index('id_categoria');
            });
            return;
        }

        // Solo agregar columnas que falten (verificación defensiva)
        if (!Schema::hasColumn('presupuesto_apoyos', 'aprobado_por')) {
            Schema::table('presupuesto_apoyos', function (Blueprint $table) {
                $table->string('aprobado_por')->nullable()->after('fecha_aprobacion');
            });
        }

        // Log de éxito
        \Log::info('✅ Migración presupuesto_apoyos completada - estructura verificada');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se revierte este fix
    }
};
