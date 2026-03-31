<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crear tablas de auditoría para Carga Fría
 * 
 * Tablas:
 * - auditorias_carga_fria: Registro de quién cargó, cuándo, por qué
 * - consentimientos_carga_fria: Confirmación posterior del beneficiario
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabla 1: Auditoría de Carga Fría
        Schema::create('auditorias_carga_fria', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->unsignedInteger('fk_id_beneficiario');
            $table->unsignedInteger('fk_id_admin');
            $table->unsignedInteger('fk_id_solicitud')->nullable();
            $table->string('apartado_carga', 50)->nullable();
            $table->integer('cantidad_documentos')->default(0);
            $table->text('justificacion')->nullable();
            $table->dateTime('fecha_carga')->useCurrent();
            $table->ipAddress('ip_admin')->nullable();
            $table->text('navegador_agente')->nullable();

            // Foreign keys
            $table->foreign('fk_id_beneficiario')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('cascade');

            $table->foreign('fk_id_admin')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('restrict');

            $table->foreign('fk_id_solicitud')
                ->references('folio')
                ->on('Solicitudes')
                ->onDelete('set null');

            // Índices
            $table->index('fk_id_beneficiario');
            $table->index('fk_id_admin');
            $table->index('fk_id_solicitud');
            $table->index('fecha_carga');
        });

        // Tabla 2: Consentimientos de Carga Fría
        Schema::create('consentimientos_carga_fria', function (Blueprint $table) {
            $table->id('id_consentimiento');
            $table->unsignedInteger('fk_id_beneficiario');
            $table->unsignedBigInteger('fk_id_auditoria_carga_fria');
            $table->boolean('consiente')->nullable()->comment("1=sí, 0=no, NULL=pendiente");
            $table->dateTime('fecha_consentimiento')->nullable();
            $table->ipAddress('ip_beneficiario')->nullable();
            $table->string('metodo_consentimiento', 50)->nullable()
                ->comment("'email', 'firma_digital', 'presencial'");
            $table->text('observaciones')->nullable();

            // Foreign keys
            $table->foreign('fk_id_beneficiario')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('cascade');

            $table->foreign('fk_id_auditoria_carga_fria')
                ->references('id_auditoria')
                ->on('auditorias_carga_fria')
                ->onDelete('cascade');

            // Índices
            $table->index('fk_id_beneficiario');
            $table->index('fk_id_auditoria_carga_fria');
            $table->index('consiente');
            $table->index('fecha_consentimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos_carga_fria');
        Schema::dropIfExists('auditorias_carga_fria');
    }
};
