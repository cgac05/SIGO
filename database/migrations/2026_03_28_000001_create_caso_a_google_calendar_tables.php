<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations for Caso A (Carga Hibrida) + Google Calendar Integration
     */
    public function up(): void
    {
        // 1. MODIFY: Documentos_Expediente - Add Caso A fields
        Schema::table('Documentos_Expediente', function (Blueprint $table) {
            if (!Schema::hasColumn('Documentos_Expediente', 'origen_carga')) {
                $table->string('origen_carga', 50)->nullable()->comment("'beneficiario'|'admin_escaneo_presencial'");
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'cargado_por')) {
                $table->unsignedInteger('cargado_por')->nullable()->comment('FK to Usuarios (admin who uploaded)');
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'marca_agua_aplicada')) {
                $table->boolean('marca_agua_aplicada')->default(false)->comment('Was watermark applied?');
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'qr_seguimiento')) {
                $table->string('qr_seguimiento', 510)->nullable()->comment('QR code data/image path');
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'hash_documento')) {
                $table->string('hash_documento', 64)->nullable()->comment('SHA256(document content)');
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'hash_anterior')) {
                $table->string('hash_anterior', 64)->nullable()->comment('Previous document hash (digital chain)');
            }
            if (!Schema::hasColumn('Documentos_Expediente', 'firma_admin')) {
                $table->string('firma_admin', 255)->nullable()->comment('HMAC-SHA256 signature');
            }
        });

        // Add foreign key if column exists and FK doesn't
        try {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                $table->foreign('cargado_por')->references('id_usuario')->on('Usuarios')->onDelete('set null');
            });
        } catch (\Exception $e) { /* FK already exists */ }

        // 2. CREATE: Claves de Seguimiento Privadas
        if (!Schema::hasTable('claves_seguimiento_privadas')) {
            Schema::create('claves_seguimiento_privadas', function (Blueprint $table) {
                $table->id('id_clave');
                $table->string('folio', 50)->unique();
                $table->string('clave_alfanumerica', 20);
                $table->string('hash_clave', 64);
                $table->unsignedInteger('beneficiario_id');
                $table->timestamp('fecha_creacion')->useCurrent();
                $table->timestamp('fecha_ultimo_acceso')->nullable();
                $table->integer('intentos_fallidos')->default(0);
                $table->boolean('bloqueada')->default(false);
                $table->foreign('beneficiario_id')->references('id_usuario')->on('Usuarios');
            });
        }

        // 3. CREATE: Cadena Digital Documentos
        if (!Schema::hasTable('cadena_digital_documentos')) {
            Schema::create('cadena_digital_documentos', function (Blueprint $table) {
                $table->id('id_cadena');
                $table->unsignedInteger('fk_id_documento');
                $table->string('folio', 50);
                $table->string('hash_actual', 64);
                $table->string('hash_anterior', 64)->nullable();
                $table->unsignedInteger('admin_creador');
                $table->timestamp('timestamp_creacion')->useCurrent();
                $table->string('firma_hmac', 255);
                $table->string('razon_cambio', 255)->nullable();
                $table->foreign('fk_id_documento')->references('id_documento')->on('Documentos_Expediente');
                $table->foreign('admin_creador')->references('id_usuario')->on('Usuarios');
            });
        }

        // 4. CREATE: Auditoría Carga Material
        if (!Schema::hasTable('auditorias_carga_material')) {
            Schema::create('auditorias_carga_material', function (Blueprint $table) {
                $table->id('id_auditoria');
                $table->string('folio', 50);
                $table->string('evento', 50);
                $table->unsignedInteger('admin_id');
                $table->integer('cantidad_docs')->nullable();
                $table->timestamp('fecha_evento')->useCurrent();
                $table->string('ip_admin', 45)->nullable();
                $table->text('navegador_agente')->nullable();
                $table->json('detalles_evento')->nullable();
                $table->foreign('admin_id')->references('id_usuario')->on('Usuarios');
            });
        }

        // 5. CREATE: Políticas Retención Documentos
        if (!Schema::hasTable('politicas_retencion_documentos')) {
            Schema::create('politicas_retencion_documentos', function (Blueprint $table) {
                $table->id('id_politica');
                $table->unsignedInteger('fk_id_documento');
                $table->string('folio', 50);
                $table->string('hito_cierre_apoyo', 100)->nullable();
                $table->datetime('fecha_cierre_apoyo')->nullable();
                $table->boolean('retencion_cumplida')->default(false);
                $table->datetime('fecha_borrado')->nullable();
                $table->string('razon_borrado', 255)->nullable();
                $table->foreign('fk_id_documento')->references('id_documento')->on('Documentos_Expediente');
            });
        }

        // 6. MODIFY: Hitos_Apoyo
        Schema::table('Hitos_Apoyo', function (Blueprint $table) {
            if (!Schema::hasColumn('Hitos_Apoyo', 'google_calendar_event_id')) {
                $table->string('google_calendar_event_id', 255)->nullable();
            }
            if (!Schema::hasColumn('Hitos_Apoyo', 'google_calendar_sync')) {
                $table->boolean('google_calendar_sync')->default(true);
            }
            if (!Schema::hasColumn('Hitos_Apoyo', 'ultima_sincronizacion')) {
                $table->timestamp('ultima_sincronizacion')->nullable();
            }
            if (!Schema::hasColumn('Hitos_Apoyo', 'cambios_locales_pendientes')) {
                $table->boolean('cambios_locales_pendientes')->default(false);
            }
        });

        // 7. MODIFY: Apoyos
        Schema::table('Apoyos', function (Blueprint $table) {
            if (!Schema::hasColumn('Apoyos', 'sincronizar_calendario')) {
                $table->boolean('sincronizar_calendario')->default(true);
            }
            if (!Schema::hasColumn('Apoyos', 'recordatorio_dias')) {
                $table->integer('recordatorio_dias')->default(3);
            }
            if (!Schema::hasColumn('Apoyos', 'google_group_email')) {
                $table->string('google_group_email', 255)->nullable();
            }
        });

        // 8. CREATE: Directivos Calendario Permisos
        if (!Schema::hasTable('directivos_calendario_permisos')) {
            Schema::create('directivos_calendario_permisos', function (Blueprint $table) {
                $table->id('id_permiso');
                $table->unsignedInteger('fk_id_directivo');
                $table->string('google_calendar_id', 255)->nullable();
                $table->text('google_access_token')->nullable();
                $table->text('google_refresh_token')->nullable();
                $table->timestamp('token_expiracion')->nullable();
                $table->string('email_directivo', 255)->unique();
                $table->integer('calendarios_sincronizados')->default(0);
                $table->timestamp('ultima_sincronizacion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->foreign('fk_id_directivo')->references('id_usuario')->on('Usuarios');
            });
        }

        // 9. CREATE: Calendario Sincronización Log
        if (!Schema::hasTable('calendario_sincronizacion_log')) {
            Schema::create('calendario_sincronizacion_log', function (Blueprint $table) {
                $table->id('id_log');
                $table->unsignedInteger('fk_id_hito')->nullable();
                $table->unsignedInteger('fk_id_apoyo')->nullable();
                $table->string('tipo_cambio', 50);
                $table->string('origen', 50);
                $table->json('datos_anteriores')->nullable();
                $table->json('datos_nuevos')->nullable();
                $table->unsignedInteger('usuario_id');
                $table->timestamp('fecha_cambio')->useCurrent();
                $table->boolean('sincronizado')->default(false);
                $table->text('error_sincronizacion')->nullable();
                $table->foreign('fk_id_hito')->references('id_hito')->on('Hitos_Apoyo');
                $table->foreign('fk_id_apoyo')->references('id_apoyo')->on('Apoyos');
                $table->foreign('usuario_id')->references('id_usuario')->on('Usuarios');
            });
        }

        // 10. ADD: New Estados
        foreach (['EXPEDIENTE_PRESENCIAL', 'DOCS_VERIFICADOS'] as $estado) {
            if (Schema::hasTable('Cat_EstadosSolicitud')) {
                DB::table('Cat_EstadosSolicitud')->updateOrInsert(['nombre_estado' => $estado]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_sincronizacion_log');
        Schema::dropIfExists('directivos_calendario_permisos');
        Schema::dropIfExists('politicas_retencion_documentos');
        Schema::dropIfExists('auditorias_carga_material');
        Schema::dropIfExists('cadena_digital_documentos');
        Schema::dropIfExists('claves_seguimiento_privadas');
    }
};