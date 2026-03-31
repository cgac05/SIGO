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
        // =====================================================
        // 1. MODIFY: Documentos_Expediente - Add Caso A fields
        // =====================================================
        Schema::table('Documentos_Expediente', function (Blueprint $table) {
            // Add new columns only if they don't exist
            try {
                $table->string('origen_carga', 50)->nullable()
                    ->comment("'beneficiario'|'admin_escaneo_presencial'");
            } catch (\Exception $e) {
                // Column probably exists
            }
            
            try {
                $table->unsignedInteger('cargado_por')->nullable()
                    ->comment('FK to Usuarios (admin who uploaded)');
            } catch (\Exception $e) {
                // Column probably exists
            }
            
            try {
                $table->boolean('marca_agua_aplicada')->default(false)
                    ->comment('Was watermark applied?');
            } catch (\Exception $e) {
                // Column probably exists
            }
            
            try {
                $table->string('qr_seguimiento', 510)->nullable()
                    ->comment('QR code data/image path');
            } catch (\Exception $e) {
                // Column probably exists
            }
            
            try {
                $table->string('hash_documento', 64)->nullable()
                    ->comment('SHA256(document content)');
            } catch (\Exception $e) {
                // Column probably exists
            }
            
            try {
                $table->string('hash_anterior', 64)->nullable()
                    ->comment('Previous document hash (digital chain)');
            } catch (\Exception $e) {
                // Column probably exists
            }
            
            try {
                $table->string('firma_admin', 255)->nullable()
                    ->comment('HMAC-SHA256 signature');
            } catch (\Exception $e) {
                // Column probably exists
            }
        });
        
        // Add foreign key if it doesn't exist
        try {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                $table->foreign('cargado_por')
                    ->references('id_usuario')
                    ->on('Usuarios')
                    ->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key probably exists
        }

        // =====================================================
        // 2. CREATE: Claves de Seguimiento Privadas (Caso A)
        // =====================================================
        Schema::create('claves_seguimiento_privadas', function (Blueprint $table) {
            $table->id('id_clave');
            $table->string('folio', 50)->unique();
            $table->string('clave_alfanumerica', 20)->comment('KX7M-9P2W-5LQ8 format');
            $table->string('hash_clave', 64)->comment('SHA256 verification hash');
            $table->unsignedInteger('beneficiario_id');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_ultimo_acceso')->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->boolean('bloqueada')->default(false);

            $table->foreign('beneficiario_id')
                ->references('id_usuario')
                ->on('Usuarios');
        });

        // =====================================================
        // 3. CREATE: Cadena Digital Documentos (Caso A)
        // =====================================================
        try {
            Schema::create('cadena_digital_documentos', function (Blueprint $table) {
                $table->id('id_cadena');
                $table->unsignedInteger('fk_id_documento');
                $table->string('folio', 50);
                $table->string('hash_actual', 64)->comment('Current document hash');
                $table->string('hash_anterior', 64)->nullable()->comment('Previous hash (chain link)');
                $table->unsignedInteger('admin_creador');
                $table->timestamp('timestamp_creacion')->useCurrent();
                $table->string('firma_hmac', 255)->comment('HMAC signature for immutability');
                $table->string('razon_cambio', 255)->nullable();

                // Try to add FK - may fail if column doesn't exist
                try {
                    $table->foreign('fk_id_documento')
                        ->references('id_documento')
                        ->on('Documentos_Expediente');
                } catch (\Exception $e) {
                    // FK will be added manually later
                }
                
                $table->foreign('admin_creador')
                    ->references('id_usuario')
                    ->on('Usuarios');
            });
        } catch (\Exception $e) {
            // Table might already exist
        }

        // =====================================================
        // 4. CREATE: Auditoría Carga Material (Caso A)
        // =====================================================
        Schema::create('auditorias_carga_material', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->string('folio', 50);
            $table->string('evento', 50)
                ->comment("'escaneo_completado'|'marca_agua_aplicada'|etc");
            $table->unsignedInteger('admin_id');
            $table->integer('cantidad_docs')->nullable();
            $table->timestamp('fecha_evento')->useCurrent();
            $table->string('ip_admin', 45)->nullable();
            $table->text('navegador_agente')->nullable();
            $table->json('detalles_evento')->nullable();

            $table->foreign('admin_id')
                ->references('id_usuario')
                ->on('Usuarios');
        });

        // =====================================================
        // 5. CREATE: Políticas Retención Documentos (Caso A)
        // =====================================================
        try {
            Schema::create('politicas_retencion_documentos', function (Blueprint $table) {
                $table->id('id_politica');
                $table->unsignedInteger('fk_id_documento');
                $table->string('folio', 50);
                $table->string('hito_cierre_apoyo', 100)->nullable();
                $table->datetime('fecha_cierre_apoyo')->nullable();
                $table->boolean('retencion_cumplida')->default(false);
                $table->datetime('fecha_borrado')->nullable();
                $table->string('razon_borrado', 255)->nullable();

                try {
                    $table->foreign('fk_id_documento')
                        ->references('id_documento')
                        ->on('Documentos_Expediente');
                } catch (\Exception $e) {
                    // FK will be added manually later
                }
            });
        } catch (\Exception $e) {
            // Table might already exist
        }

        // =====================================================
        // 6. MODIFY: Hitos_Apoyo - Add Google Calendar fields
        // =====================================================
        try {
            Schema::table('Hitos_Apoyo', function (Blueprint $table) {
                $table->string('google_calendar_event_id', 255)->nullable()
                    ->comment('ID of Google Calendar event');
                $table->boolean('google_calendar_sync')->default(true);
                $table->timestamp('ultima_sincronizacion')->nullable();
                $table->boolean('cambios_locales_pendientes')->default(false);
            });
        } catch (\Exception $e) {
            // Columns might already exist
        }

        // =====================================================
        // 7. MODIFY: Apoyos - Add Google Calendar config
        // =====================================================
        try {
            Schema::table('Apoyos', function (Blueprint $table) {
                $table->boolean('sincronizar_calendario')->default(true);
                $table->integer('recordatorio_dias')->default(3)
                    ->comment('Days before reminder');
                $table->string('google_group_email', 255)->nullable()
                    ->comment('Google Group for invitations');
            });
        } catch (\Exception $e) {
            // Columns might already exist
        }

        // =====================================================
        // 8. CREATE: Directivos Calendario Permisos (Google Cal)
        // =====================================================
        Schema::create('directivos_calendario_permisos', function (Blueprint $table) {
            $table->id('id_permiso');
            $table->unsignedInteger('fk_id_directivo');
            $table->string('google_calendar_id', 255)->nullable();
            $table->text('google_access_token')->nullable()
                ->comment('Encrypted OAuth token');
            $table->text('google_refresh_token')->nullable()
                ->comment('Encrypted refresh token');
            $table->timestamp('token_expiracion')->nullable();
            $table->string('email_directivo', 255);
            $table->integer('calendarios_sincronizados')->default(0);
            $table->timestamp('ultima_sincronizacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('email_directivo');
            $table->foreign('fk_id_directivo')
                ->references('id_usuario')
                ->on('Usuarios');
        });

        // =====================================================
        // 9. CREATE: Calendario Sincronización Log (Google Cal)
        // =====================================================
        Schema::create('calendario_sincronizacion_log', function (Blueprint $table) {
            $table->id('id_log');
            $table->unsignedInteger('fk_id_hito')->nullable();
            $table->unsignedInteger('fk_id_apoyo')->nullable();
            $table->string('tipo_cambio', 50)
                ->comment("'creacion'|'actualizacion'|'eliminacion'");
            $table->string('origen', 50)
                ->comment("'sigo'|'google'");
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->unsignedInteger('usuario_id');
            $table->timestamp('fecha_cambio')->useCurrent();
            $table->boolean('sincronizado')->default(false);
            $table->text('error_sincronizacion')->nullable();

            $table->foreign('fk_id_hito')
                ->references('id_hito')
                ->on('Hitos_Apoyo')
                ->onDelete('no action');
            $table->foreign('fk_id_apoyo')
                ->references('id_apoyo')
                ->on('Apoyos')
                ->onDelete('no action');
            $table->foreign('usuario_id')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('no action');
        });

        // =====================================================
        // 10. ADD: New Estados to Cat_EstadosSolicitud
        // =====================================================
        try {
            \DB::statement("
                INSERT INTO Cat_EstadosSolicitud (nombre_estado)
                SELECT 'EXPEDIENTE_PRESENCIAL' 
                WHERE NOT EXISTS (SELECT 1 FROM Cat_EstadosSolicitud WHERE nombre_estado = 'EXPEDIENTE_PRESENCIAL')
            ");

            \DB::statement("
                INSERT INTO Cat_EstadosSolicitud (nombre_estado)
                SELECT 'DOCS_VERIFICADOS' 
                WHERE NOT EXISTS (SELECT 1 FROM Cat_EstadosSolicitud WHERE nombre_estado = 'DOCS_VERIFICADOS')
            ");
        } catch (\Exception $e) {
            // Estados might already exist or column is too small
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables in reverse order
        Schema::dropIfExists('calendario_sincronizacion_log');
        Schema::dropIfExists('directivos_calendario_permisos');
        Schema::dropIfExists('politicas_retencion_documentos');
        Schema::dropIfExists('auditorias_carga_material');
        Schema::dropIfExists('cadena_digital_documentos');
        Schema::dropIfExists('claves_seguimiento_privadas');

        // Remove added columns
        Schema::table('Apoyos', function (Blueprint $table) {
            $table->dropColumn(['sincronizar_calendario', 'recordatorio_dias', 'google_group_email']);
        });

        Schema::table('Hitos_Apoyo', function (Blueprint $table) {
            $table->dropColumn(['google_calendar_event_id', 'google_calendar_sync', 'ultima_sincronizacion', 'cambios_locales_pendientes']);
        });

        Schema::table('Documentos_Expediente', function (Blueprint $table) {
            $table->dropForeign(['cargado_por']);
            $table->dropColumn(['origen_carga', 'cargado_por', 'marca_agua_aplicada', 'qr_seguimiento', 'hash_documento', 'hash_anterior', 'firma_admin', 'fecha_carga']);
        });
    }
};
