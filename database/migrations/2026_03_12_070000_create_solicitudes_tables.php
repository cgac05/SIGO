<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración que crea las tablas de catálogos, solicitudes y documentos
 * necesarias para el módulo de trámites del beneficiario.
 *
 * Tablas creadas / modificadas:
 * - Cat_EstadosSolicitud   : catálogo de estados (Pendiente, En revisión, Aprobada, Rechazada)
 * - Cat_Prioridades        : catálogo de prioridades (Baja, Normal, Alta)
 * - Cat_TiposDocumento     : catálogo de tipos de documento requeridos
 * - Apoyos (ALTER)         : agrega foto_ruta y descripcion si aún no existen
 * - Requisitos_Apoyo       : documentos requeridos por apoyo
 * - Solicitudes            : trámites presentados por beneficiarios
 * - Documentos_Expediente  : archivos cargados por solicitud
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Cat_EstadosSolicitud
        // ------------------------------------------------------------------
        if (! Schema::hasTable('Cat_EstadosSolicitud')) {
            Schema::create('Cat_EstadosSolicitud', function (Blueprint $table) {
                $table->increments('id_estado');
                $table->string('nombre_estado', 30)->unique();
            });

            // Datos semilla obligatorios (id_estado = 1 es el DEFAULT en Solicitudes)
            DB::table('Cat_EstadosSolicitud')->insert([
                ['id_estado' => 1, 'nombre_estado' => 'Pendiente'],
                ['id_estado' => 2, 'nombre_estado' => 'En revisión'],
                ['id_estado' => 3, 'nombre_estado' => 'Aprobada'],
                ['id_estado' => 4, 'nombre_estado' => 'Rechazada'],
            ]);
        }

        // ------------------------------------------------------------------
        // 2. Cat_Prioridades
        // ------------------------------------------------------------------
        if (! Schema::hasTable('Cat_Prioridades')) {
            Schema::create('Cat_Prioridades', function (Blueprint $table) {
                $table->integer('id_prioridad')->primary();
                $table->string('nivel', 20)->unique();
            });

            DB::table('Cat_Prioridades')->insert([
                ['id_prioridad' => 1, 'nivel' => 'Baja'],
                ['id_prioridad' => 2, 'nivel' => 'Normal'],
                ['id_prioridad' => 3, 'nivel' => 'Alta'],
            ]);
        }

        // ------------------------------------------------------------------
        // 3. Cat_TiposDocumento
        // ------------------------------------------------------------------
        if (! Schema::hasTable('Cat_TiposDocumento')) {
            Schema::create('Cat_TiposDocumento', function (Blueprint $table) {
                $table->increments('id_tipo_doc');
                $table->string('nombre_documento', 100)->unique();
                $table->string('tipo_archivo_permitido', 20)->default('pdf');
                $table->boolean('validar_tipo_archivo')->default(true);
                $table->text('descripcion')->nullable();
            });
        }

        // ------------------------------------------------------------------
        // 4. Apoyos — columnas faltantes (foto_ruta, descripcion)
        // ------------------------------------------------------------------
        if (Schema::hasTable('Apoyos')) {
            Schema::table('Apoyos', function (Blueprint $table) {
                if (! Schema::hasColumn('Apoyos', 'foto_ruta')) {
                    $table->string('foto_ruta', 500)->nullable();
                }
                if (! Schema::hasColumn('Apoyos', 'descripcion')) {
                    $table->text('descripcion')->nullable();
                }
            });
        }

        // ------------------------------------------------------------------
        // 5. Requisitos_Apoyo  (FK → Apoyos, Cat_TiposDocumento)
        // ------------------------------------------------------------------
        if (! Schema::hasTable('Requisitos_Apoyo')) {
            Schema::create('Requisitos_Apoyo', function (Blueprint $table) {
                $table->unsignedInteger('fk_id_apoyo');
                $table->unsignedInteger('fk_id_tipo_doc');
                $table->boolean('es_obligatorio')->default(true);

                $table->primary(['fk_id_apoyo', 'fk_id_tipo_doc']);
                $table->foreign('fk_id_apoyo')
                      ->references('id_apoyo')->on('Apoyos')
                      ->onDelete('cascade');
                $table->foreign('fk_id_tipo_doc')
                      ->references('id_tipo_doc')->on('Cat_TiposDocumento')
                      ->onDelete('cascade');
            });
        }

        // ------------------------------------------------------------------
        // 6. Solicitudes  (FK → Beneficiarios, Apoyos, Cat_EstadosSolicitud, Cat_Prioridades)
        // ------------------------------------------------------------------
        if (! Schema::hasTable('Solicitudes')) {
            Schema::create('Solicitudes', function (Blueprint $table) {
                // IDENTITY(1000,1) — usar autoIncrement y ajustar el seed via DB::statement
                $table->increments('folio');
                $table->char('fk_curp', 18);
                $table->unsignedInteger('fk_id_apoyo');
                $table->unsignedInteger('fk_id_estado')->default(1);
                $table->integer('fk_id_prioridad')->nullable();
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->dateTime('fecha_actualizacion')->useCurrent();
                $table->text('observaciones_internas')->nullable();

                $table->foreign('fk_curp')
                      ->references('curp')->on('Beneficiarios')
                      ->onDelete('cascade');
                $table->foreign('fk_id_apoyo')
                      ->references('id_apoyo')->on('Apoyos')
                      ->onDelete('restrict');
                $table->foreign('fk_id_estado')
                      ->references('id_estado')->on('Cat_EstadosSolicitud')
                      ->onDelete('restrict');
                $table->foreign('fk_id_prioridad')
                      ->references('id_prioridad')->on('Cat_Prioridades')
                      ->onDelete('set null');
            });

            // Ajustar el auto-increment inicial a 1000 (compatible SQL Server y SQLite)
            try {
                $driver = Schema::getConnection()->getDriverName();
                if ($driver === 'sqlsrv') {
                    DB::statement("DBCC CHECKIDENT ('Solicitudes', RESEED, 999)");
                }
                // SQLite no necesita reseed; los tests arrancan siempre en 1
            } catch (\Throwable) {
                // No es crítico; continúa sin el reseed
            }
        }

        // ------------------------------------------------------------------
        // 7. Documentos_Expediente  (FK → Solicitudes, Cat_TiposDocumento)
        // ------------------------------------------------------------------
        if (! Schema::hasTable('Documentos_Expediente')) {
            Schema::create('Documentos_Expediente', function (Blueprint $table) {
                $table->increments('id_documento');
                $table->unsignedInteger('fk_folio');
                $table->unsignedInteger('fk_id_tipo_doc');
                $table->string('ruta_archivo', 500);
                $table->string('estado_validacion', 20)->default('Pendiente');
                $table->unsignedSmallInteger('version')->default(1);
                $table->dateTime('fecha_carga')->useCurrent();

                $table->foreign('fk_folio')
                      ->references('folio')->on('Solicitudes')
                      ->onDelete('cascade');
                $table->foreign('fk_id_tipo_doc')
                      ->references('id_tipo_doc')->on('Cat_TiposDocumento')
                      ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('Documentos_Expediente');
        Schema::dropIfExists('Solicitudes');
        Schema::dropIfExists('Requisitos_Apoyo');

        if (Schema::hasTable('Apoyos')) {
            Schema::table('Apoyos', function (Blueprint $table) {
                if (Schema::hasColumn('Apoyos', 'foto_ruta')) {
                    $table->dropColumn('foto_ruta');
                }
                if (Schema::hasColumn('Apoyos', 'descripcion')) {
                    $table->dropColumn('descripcion');
                }
            });
        }

        Schema::dropIfExists('Cat_TiposDocumento');
        Schema::dropIfExists('Cat_Prioridades');
        Schema::dropIfExists('Cat_EstadosSolicitud');
    }
};
