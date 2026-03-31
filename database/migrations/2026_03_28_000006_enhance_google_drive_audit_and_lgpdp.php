<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Validar y completar estructura de auditoría Google Drive
 * 
 * Esta migración asegura que:
 * 1. google_drive_audit_logs tenga todos los campos necesarios
 * 2. Se establezcan índices apropiados
 * 3. Se implementen políticas de retención LGPDP
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar si la tabla existe y tiene todos los campos
        if (!Schema::hasTable('google_drive_audit_logs')) {
            Schema::create('google_drive_audit_logs', function (Blueprint $table) {
                $table->id('id_audit_log');
                $table->unsignedInteger('fk_id_usuario');
                $table->unsignedBigInteger('fk_id_documento_expediente')->nullable();
                $table->string('accion', 50)
                    ->comment("'upload', 'download', 'share', 'delete', 'modify', 'view'");
                $table->string('archivo_nombre', 510);
                $table->string('google_file_id', 100)->nullable();
                $table->string('folder_id', 100)->nullable();
                $table->dateTime('fecha_accion')->useCurrent();
                $table->ipAddress('ip_usuario')->nullable();
                $table->text('navegador_agente')->nullable();
                $table->decimal('tamaño_archivo', 19, 4)->nullable()
                    ->comment("En bytes");
                $table->enum('tipo_archivo', [
                    'Documento',
                    'Imagen',
                    'PDF',
                    'Hoja de Cálculo',
                    'Presentación',
                    'Otro'
                ])->default('Documento');
                $table->enum('estatus_cumplimiento_lgpdp', [
                    'Conforme',
                    'Con Anomalía',
                    'Requiere Revisión',
                    'Marcado para Eliminación'
                ])->default('Conforme');
                $table->dateTime('fecha_vencimiento_retencion')->nullable()
                    ->comment("LGPDP: Retención de datos máximo 5 años");
                $table->boolean('eliminado_conforme_lgpdp')->default(false);
                $table->dateTime('fecha_eliminacion_lgpdp')->nullable();
                $table->text('observaciones')->nullable();
                $table->enum('encriptacion_status', [
                    'Encriptado',
                    'Encriptación Pendiente',
                    'No Requiere'
                ])->default('Encriptado');

                // Foreign Keys
                $table->foreign('fk_id_usuario')
                    ->references('id_usuario')
                    ->on('Usuarios')
                    ->onDelete('restrict');

                $table->foreign('fk_id_documento_expediente')
                    ->references('id_documento_expediente')
                    ->on('Documentos_Expediente')
                    ->onDelete('set null');

                // Índices para búsquedas y auditoría
                $table->index('fk_id_usuario');
                $table->index('fk_id_documento_expediente');
                $table->index('accion');
                $table->index('fecha_accion');
                $table->index('google_file_id');
                $table->index('estatus_cumplimiento_lgpdp');
                $table->index('eliminado_conforme_lgpdp');
                $table->index('fecha_vencimiento_retencion');

                // Índice compuesto para auditoría temporal
                $table->index(['fk_id_usuario', 'fecha_accion']);
                $table->index(['accion', 'fecha_accion']);
            });
        } else {
            // Si existe, agregar campos faltantes
            Schema::table('google_drive_audit_logs', function (Blueprint $table) {
                // Agregar campos que pueden faltar
                if (!Schema::hasColumn('google_drive_audit_logs', 'fk_id_usuario')) {
                    $table->unsignedInteger('fk_id_usuario')->nullable();
                }
                if (!Schema::hasColumn('google_drive_audit_logs', 'accion')) {
                    $table->string('accion', 50)->nullable();
                }
                if (!Schema::hasColumn('google_drive_audit_logs', 'fecha_accion')) {
                    $table->dateTime('fecha_accion')->useCurrent();
                }
                if (!Schema::hasColumn('google_drive_audit_logs', 'estatus_cumplimiento_lgpdp')) {
                    $table->enum('estatus_cumplimiento_lgpdp', [
                        'Conforme',
                        'Con Anomalía',
                        'Requiere Revisión',
                        'Marcado para Eliminación'
                    ])->default('Conforme');
                }
                if (!Schema::hasColumn('google_drive_audit_logs', 'fecha_vencimiento_retencion')) {
                    $table->dateTime('fecha_vencimiento_retencion')->nullable();
                }
                if (!Schema::hasColumn('google_drive_audit_logs', 'eliminado_conforme_lgpdp')) {
                    $table->boolean('eliminado_conforme_lgpdp')->default(false);
                }
                if (!Schema::hasColumn('google_drive_audit_logs', 'fecha_eliminacion_lgpdp')) {
                    $table->dateTime('fecha_eliminacion_lgpdp')->nullable();
                }
            });
        }

        // Crear tabla de políticas de retención LGPDP
        if (!Schema::hasTable('politicas_retencion_datos')) {
            Schema::create('politicas_retencion_datos', function (Blueprint $table) {
                $table->id('id_politica');
                $table->string('nombre_politica', 255);
                $table->text('descripcion');
                $table->integer('dias_retencion')->comment("Días antes de marcar para eliminación");
                $table->string('tipo_dato', 100)
                    ->comment("'documento', 'expediente', 'solicitud', 'auditoria'");
                $table->boolean('requiere_consentimiento_previo')->default(true);
                $table->text('fundamento_legal')->nullable();
                $table->boolean('activa')->default(true);
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->unsignedInteger('fk_id_usuario_creador');

                $table->foreign('fk_id_usuario_creador')
                    ->references('id_usuario')
                    ->on('Usuarios')
                    ->onDelete('restrict');

                $table->index('tipo_dato');
                $table->index('dias_retencion');
                $table->index('activa');
            });
        }

        // Crear tabla de solicitudes de ARCO (Acceso, Rectificación, Cancelación, Oposición)
        if (!Schema::hasTable('solicitudes_arco')) {
            Schema::create('solicitudes_arco', function (Blueprint $table) {
                $table->id('id_solicitud_arco');
                $table->string('folio_arco', 50)->unique();
                $table->unsignedInteger('fk_id_beneficiario');
                $table->enum('tipo_solicitud', ['Acceso', 'Rectificación', 'Cancelación', 'Oposición'])
                    ->comment("LGPDP - Derechos ARCO");
                $table->text('descripcion_solicitud');
                $table->dateTime('fecha_solicitud')->useCurrent();
                $table->enum('estado', [
                    'Recibida',
                    'En Análisis',
                    'Aprobada',
                    'Rechazada',
                    'Parcialmente Aprobada',
                    'Concluida'
                ])->default('Recibida');
                $table->unsignedInteger('fk_id_responsable')->nullable();
                $table->dateTime('fecha_respuesta')->nullable();
                $table->text('respuesta_texto')->nullable();
                $table->dateTime('fecha_limite_respuesta')
                    ->comment("LGPDP: 20 días hábiles para responder");
                $table->boolean('documentacion_completa')->default(false);
                $table->text('razon_rechazo')->nullable();

                $table->foreign('fk_id_beneficiario')
                    ->references('id_usuario')
                    ->on('Usuarios')
                    ->onDelete('restrict');

                $table->foreign('fk_id_responsable')
                    ->references('id_usuario')
                    ->on('Usuarios')
                    ->onDelete('set null');

                $table->index('fk_id_beneficiario');
                $table->index('fk_id_responsable');
                $table->index('tipo_solicitud');
                $table->index('estado');
                $table->index('fecha_solicitud');
                $table->index('fecha_limite_respuesta');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_arco');
        Schema::dropIfExists('politicas_retencion_datos');
        // No eliminamos google_drive_audit_logs por ser tabla existente
    }
};
