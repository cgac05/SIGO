<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea todas las tablas necesarias para Caso A (Carga Híbrida Asincrónica)
     */
    public function up(): void
    {
        // 1. Tabla: Claves de Seguimiento Privadas
        // Genera claves únicas para acceso privado a documentos (Momento 1)
        // ✨ Arquitectura: Caso A crea solicitud ORDINARIA
        // Estas claves SOLO para acceso Momento 3 (beneficiario consulta)
        if (!Schema::hasTable('claves_seguimiento_privadas')) {
            Schema::create('claves_seguimiento_privadas', function (Blueprint $table) {
                $table->id('id_clave');
                $table->string('folio', 50)->unique();
                $table->string('clave_alfanumerica', 20)->unique();
                $table->string('hash_clave', 64);
                $table->unsignedInteger('beneficiario_id');
                $table->dateTime('fecha_creacion')->default(DB::raw('GETDATE()'));
                $table->dateTime('fecha_ultimo_acceso')->nullable();
                $table->integer('intentos_fallidos')->default(0);
                $table->boolean('bloqueada')->default(false);

                // Índices
                $table->index('folio');
                $table->index('beneficiario_id');
                $table->index('fecha_creacion');
                $table->index('bloqueada');

                // Foreign keys
                $table->foreign('beneficiario_id')
                    ->references('id_usuario')
                    ->on('usuarios')
                    ->onDelete('cascade');
            });
        }

        // 2. Tabla: Cadena Digital de Documentos
        // Rastrea integridad de documentos mediante hashes y cadena de enlace
        if (!Schema::hasTable('cadena_digital_documentos')) {
            Schema::create('cadena_digital_documentos', function (Blueprint $table) {
                $table->id('id_cadena');
                $table->unsignedInteger('fk_id_documento')->nullable();
                $table->string('folio', 50);
                $table->string('hash_actual', 64);
                $table->string('hash_anterior', 64)->nullable();
                $table->unsignedInteger('admin_creador')->nullable();
                $table->dateTime('timestamp_creacion')->default(DB::raw('GETDATE()'));
                $table->string('firma_hmac', 255);
                $table->string('razon_cambio', 255)->nullable();

                // Índices
                $table->index('folio');
                $table->index('fk_id_documento');
                $table->index('admin_creador');
                $table->index('timestamp_creacion');

                // Foreign keys
                $table->foreign('fk_id_documento')
                    ->references('id_doc')
                    ->on('documentos_expediente')
                    ->onDelete('set null');

                $table->foreign('admin_creador')
                    ->references('id_usuario')
                    ->on('usuarios')
                    ->onDelete('set null');
            });
        }

        // 3. Tabla: Auditoría de Carga Material
        // Registra eventos de escaneo y procesamiento de documentos (Momento 2)
        if (!Schema::hasTable('auditorias_carga_material')) {
            Schema::create('auditorias_carga_material', function (Blueprint $table) {
                $table->id('id_auditoria');
                $table->string('folio', 50);
                $table->string('evento', 50);
                $table->unsignedInteger('admin_id')->nullable();
                $table->integer('cantidad_docs');
                $table->dateTime('fecha_evento')->default(DB::raw('GETDATE()'));
                $table->string('ip_admin', 45)->nullable();
                $table->text('navegador_agente')->nullable();
                $table->longText('detalles_evento')->nullable();

                // Índices
                $table->index('folio');
                $table->index('evento');
                $table->index('admin_id');
                $table->index('fecha_evento');

                // Foreign keys
                $table->foreign('admin_id')
                    ->references('id_usuario')
                    ->on('usuarios')
                    ->onDelete('set null');
            });
        }

        // 4. Tabla: Política de Retención de Documentos
        // Gestiona retención y eliminación automática según LGPDP
        if (!Schema::hasTable('politicas_retencion_documento')) {
            Schema::create('politicas_retencion_documento', function (Blueprint $table) {
                $table->id('id_politica');
                $table->string('folio', 50)->unique();
                $table->unsignedInteger('fk_id_documento');
                $table->date('fecha_creacion')->default(DB::raw('CAST(GETDATE() AS DATE)'));
                $table->date('fecha_expiracion');
                $table->date('fecha_cierre_apoyo')->nullable();
                $table->boolean('retencion_cumplida')->default(false);
                $table->dateTime('fecha_borrado')->nullable();
                $table->string('razon_borrado', 255)->nullable();

                // Índices
                $table->index('folio');
                $table->index('fk_id_documento');
                $table->index('fecha_expiracion');
                $table->index('retencion_cumplida');

                // Foreign keys
                $table->foreign('fk_id_documento')
                    ->references('id_doc')
                    ->on('documentos_expediente')
                    ->onDelete('cascade');
            });
        }

        // 5. Modificación: Tabla documentos_expediente
        // Agregar campos necesarios para Caso A
        if (Schema::hasTable('documentos_expediente')) {
            Schema::table('documentos_expediente', function (Blueprint $table) {
                // Verificar si columnas no existen
                if (!Schema::hasColumn('documentos_expediente', 'origen_carga')) {
                    $table->string('origen_carga', 50)->nullable()->after('ruta_local');
                }
                if (!Schema::hasColumn('documentos_expediente', 'cargado_por')) {
                    $table->unsignedInteger('cargado_por')->nullable()->after('origen_carga');
                }
                if (!Schema::hasColumn('documentos_expediente', 'hash_documento')) {
                    $table->string('hash_documento', 64)->nullable()->after('cargado_por');
                }
                if (!Schema::hasColumn('documentos_expediente', 'hash_anterior')) {
                    $table->string('hash_anterior', 64)->nullable()->after('hash_documento');
                }
                if (!Schema::hasColumn('documentos_expediente', 'firma_admin')) {
                    $table->string('firma_admin', 255)->nullable()->after('hash_anterior');
                }
                if (!Schema::hasColumn('documentos_expediente', 'qr_seguimiento')) {
                    $table->text('qr_seguimiento')->nullable()->after('firma_admin');
                }
                if (!Schema::hasColumn('documentos_expediente', 'marca_agua_aplicada')) {
                    $table->boolean('marca_agua_aplicada')->default(false)->after('qr_seguimiento');
                }
                if (!Schema::hasColumn('documentos_expediente', 'estado_verificacion')) {
                    $table->string('estado_verificacion', 50)->default('PENDIENTE')->after('marca_agua_aplicada');
                }

                // Índices para nuevos campos
                if (!Schema::hasIndex('documentos_expediente', ['origen_carga'])) {
                    $table->index('origen_carga');
                }
                if (!Schema::hasIndex('documentos_expediente', ['cargado_por'])) {
                    $table->index('cargado_por');
                }
                if (!Schema::hasIndex('documentos_expediente', ['hash_documento'])) {
                    $table->index('hash_documento');
                }
                if (!Schema::hasIndex('documentos_expediente', ['estado_verificacion'])) {
                    $table->index('estado_verificacion');
                }

                // Foreign key para cargado_por
                $table->foreign('cargado_por')
                    ->references('id_usuario')
                    ->on('usuarios')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar modificaciones a documentos_expediente primero
        if (Schema::hasTable('documentos_expediente')) {
            Schema::table('documentos_expediente', function (Blueprint $table) {
                // Eliminar foreign keys
                $table->dropForeignKeyIfExists('documentos_expediente_cargado_por_foreign');
                // Eliminar columnas
                $table->dropColumnIfExists('origen_carga');
                $table->dropColumnIfExists('cargado_por');
                $table->dropColumnIfExists('hash_documento');
                $table->dropColumnIfExists('hash_anterior');
                $table->dropColumnIfExists('firma_admin');
                $table->dropColumnIfExists('qr_seguimiento');
                $table->dropColumnIfExists('marca_agua_aplicada');
                $table->dropColumnIfExists('estado_verificacion');
            });
        }

        // Eliminar tablas
        Schema::dropIfExists('politicas_retencion_documento');
        Schema::dropIfExists('auditorias_carga_material');
        Schema::dropIfExists('cadena_digital_documentos');
        Schema::dropIfExists('claves_seguimiento_privadas');
    }
};
