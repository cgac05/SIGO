<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregar campos para Carga Fría a tabla Documentos_Expediente
 * 
 * Campos adicionales:
 * - origen_carga: 'beneficiario' | 'admin_carga_fria' | 'digitacion_expediente'
 * - cargado_por: FK a usuarios (quién cargó)
 * - justificacion_carga_fria: Razón por la que admin lo cargó
 * - marca_agua_aplicada: BIT para indicar si se aplicó marca de agua
 * - qr_seguimiento: Campo para almacenar código QR de seguimiento
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Documentos_Expediente', function (Blueprint $table) {
            // Agregar campos de origen y auditoría
            if (!Schema::hasColumn('Documentos_Expediente', 'origen_carga')) {
                $table->string('origen_carga', 50)
                    ->default('beneficiario')
                    ->comment("'beneficiario' | 'admin_carga_fria' | 'digitacion_expediente'")
                    ->after('origen_archivo');
            }

            if (!Schema::hasColumn('Documentos_Expediente', 'cargado_por')) {
                $table->unsignedInteger('cargado_por')
                    ->nullable()
                    ->comment("FK a usuarios - quién cargó (admin o sistema)")
                    ->after('origen_carga');
                
                $table->foreign('cargado_por')
                    ->references('id_usuario')
                    ->on('Usuarios')
                    ->onDelete('set null');
            }

            if (!Schema::hasColumn('Documentos_Expediente', 'justificacion_carga_fria')) {
                $table->text('justificacion_carga_fria')
                    ->nullable()
                    ->comment("Razón por la que admin cargó (analfabeta digital, discapacidad, etc.)")
                    ->after('cargado_por');
            }

            // Campos para expediente digitalizado
            if (!Schema::hasColumn('Documentos_Expediente', 'marca_agua_aplicada')) {
                $table->boolean('marca_agua_aplicada')
                    ->default(false)
                    ->comment("Indica si se aplicó marca de agua al documento")
                    ->after('justificacion_carga_fria');
            }

            if (!Schema::hasColumn('Documentos_Expediente', 'qr_seguimiento')) {
                $table->string('qr_seguimiento', 510)
                    ->nullable()
                    ->comment("Código QR de seguimiento del expediente")
                    ->after('marca_agua_aplicada');
            }

            // Agregar índices para mejorar queries de auditoría
            $table->index('origen_carga');
            $table->index('cargado_por');
        });
    }

    public function down(): void
    {
        Schema::table('Documentos_Expediente', function (Blueprint $table) {
            $table->dropForeignKey(['cargado_por']);
            $table->dropIndex(['origen_carga']);
            $table->dropIndex(['cargado_por']);
            
            $table->dropColumn([
                'origen_carga',
                'cargado_por',
                'justificacion_carga_fria',
                'marca_agua_aplicada',
                'qr_seguimiento'
            ]);
        });
    }
};
