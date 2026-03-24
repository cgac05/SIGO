<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('Hitos_Apoyo')) {
            Schema::create('Hitos_Apoyo', function (Blueprint $table) {
                $table->increments('id_hito');
                $table->unsignedInteger('fk_id_apoyo');
                $table->string('clave_hito', 30);
                $table->string('nombre_hito', 100);
                $table->unsignedSmallInteger('orden_hito');
                $table->dateTime('fecha_inicio')->nullable();
                $table->dateTime('fecha_fin')->nullable();
                $table->boolean('activo')->default(true);
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->dateTime('fecha_actualizacion')->useCurrent();

                $table->foreign('fk_id_apoyo')
                    ->references('id_apoyo')->on('Apoyos')
                    ->onDelete('cascade');

                $table->unique(['fk_id_apoyo', 'clave_hito']);
                $table->unique(['fk_id_apoyo', 'orden_hito']);
            });
        }

        if (Schema::hasTable('Solicitudes')) {
            Schema::table('Solicitudes', function (Blueprint $table) {
                if (! Schema::hasColumn('Solicitudes', 'folio_institucional')) {
                    $table->string('folio_institucional', 40)->nullable();
                    $table->index('folio_institucional');
                }

                if (! Schema::hasColumn('Solicitudes', 'permite_correcciones')) {
                    $table->boolean('permite_correcciones')->default(true);
                }

                if (! Schema::hasColumn('Solicitudes', 'monto_entregado')) {
                    $table->decimal('monto_entregado', 19, 4)->nullable();
                }

                if (! Schema::hasColumn('Solicitudes', 'fecha_entrega_recurso')) {
                    $table->date('fecha_entrega_recurso')->nullable();
                }

                if (! Schema::hasColumn('Solicitudes', 'fecha_cierre_financiero')) {
                    $table->dateTime('fecha_cierre_financiero')->nullable();
                }

                if (! Schema::hasColumn('Solicitudes', 'cuv')) {
                    $table->string('cuv', 20)->nullable();
                    $table->index('cuv');
                }
            });
        }

        if (Schema::hasTable('Documentos_Expediente')) {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                if (! Schema::hasColumn('Documentos_Expediente', 'webview_link')) {
                    $table->string('webview_link', 500)->nullable();
                }

                if (! Schema::hasColumn('Documentos_Expediente', 'source_file_id')) {
                    $table->string('source_file_id', 200)->nullable();
                }

                if (! Schema::hasColumn('Documentos_Expediente', 'official_file_id')) {
                    $table->string('official_file_id', 200)->nullable();
                }

                if (! Schema::hasColumn('Documentos_Expediente', 'observaciones_revision')) {
                    $table->text('observaciones_revision')->nullable();
                }

                if (! Schema::hasColumn('Documentos_Expediente', 'revisado_por')) {
                    $table->unsignedInteger('revisado_por')->nullable();
                }

                if (! Schema::hasColumn('Documentos_Expediente', 'fecha_revision')) {
                    $table->dateTime('fecha_revision')->nullable();
                }
            });

            if (Schema::hasColumn('Documentos_Expediente', 'revisado_por')) {
                try {
                    Schema::table('Documentos_Expediente', function (Blueprint $table) {
                        $table->foreign('revisado_por')
                            ->references('id_usuario')->on('Usuarios')
                            ->nullOnDelete();
                    });
                } catch (\Throwable) {
                    // El constraint pudo existir en una ejecución previa.
                }
            }
        }

        if (! Schema::hasTable('Seguimiento_Solicitud')) {
            Schema::create('Seguimiento_Solicitud', function (Blueprint $table) {
                $table->increments('id_seguimiento');
                $table->unsignedInteger('fk_folio');
                $table->unsignedInteger('fk_id_directivo')->nullable();
                $table->string('sello_digital', 64)->nullable();
                $table->string('cuv', 20)->nullable();
                $table->string('estado_proceso', 30)->default('EN_PROCESO');
                $table->text('metadata_seguridad')->nullable();
                $table->dateTime('fecha_firma')->nullable();
                $table->dateTime('fecha_cierre')->nullable();
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->dateTime('fecha_actualizacion')->useCurrent();

                $table->foreign('fk_folio')
                    ->references('folio')->on('Solicitudes')
                    ->onDelete('cascade');

                $table->foreign('fk_id_directivo')
                    ->references('id_usuario')->on('Usuarios')
                    ->nullOnDelete();

                $table->index('cuv');
                $table->index(['fk_folio', 'estado_proceso']);
            });
        }

        if (! Schema::hasTable('Notificaciones')) {
            Schema::create('Notificaciones', function (Blueprint $table) {
                $table->increments('id_notificacion');
                $table->unsignedInteger('fk_id_usuario');
                $table->text('mensaje');
                $table->boolean('leido')->default(false);
                $table->string('evento', 40)->nullable();
                $table->string('canal', 20)->default('sistema');
                $table->text('data')->nullable();
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->dateTime('fecha_lectura')->nullable();

                $table->foreign('fk_id_usuario')
                    ->references('id_usuario')->on('Usuarios')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('Historico_Cierre')) {
            Schema::create('Historico_Cierre', function (Blueprint $table) {
                $table->increments('id_historico');
                $table->unsignedInteger('fk_folio');
                $table->unsignedInteger('fk_id_usuario_cierre')->nullable();
                $table->text('snapshot_json');
                $table->decimal('monto_entregado', 19, 4)->nullable();
                $table->date('fecha_entrega')->nullable();
                $table->string('folio_institucional', 40)->nullable();
                $table->string('ruta_pdf_final', 500)->nullable();
                $table->dateTime('fecha_creacion')->useCurrent();

                $table->foreign('fk_folio')
                    ->references('folio')->on('Solicitudes')
                    ->onDelete('cascade');

                $table->foreign('fk_id_usuario_cierre')
                    ->references('id_usuario')->on('Usuarios')
                    ->nullOnDelete();

                $table->index('fk_folio');
            });
        }

        $hitosBase = [
            ['clave' => 'PUBLICACION', 'nombre' => 'Publicacion'],
            ['clave' => 'RECEPCION', 'nombre' => 'Recepcion'],
            ['clave' => 'ANALISIS_ADMIN', 'nombre' => 'Analisis Administrativo'],
            ['clave' => 'RESULTADOS', 'nombre' => 'Resultados'],
            ['clave' => 'CIERRE', 'nombre' => 'Cierre'],
        ];

        if (Schema::hasTable('Apoyos') && Schema::hasTable('Hitos_Apoyo')) {
            $apoyos = DB::table('Apoyos')->select('id_apoyo', 'fecha_inicio', 'fecha_fin')->get();
            foreach ($apoyos as $apoyo) {
                $exists = DB::table('Hitos_Apoyo')->where('fk_id_apoyo', $apoyo->id_apoyo)->exists();
                if ($exists) {
                    continue;
                }

                foreach ($hitosBase as $idx => $hito) {
                    DB::table('Hitos_Apoyo')->insert([
                        'fk_id_apoyo' => $apoyo->id_apoyo,
                        'clave_hito' => $hito['clave'],
                        'nombre_hito' => $hito['nombre'],
                        'orden_hito' => $idx + 1,
                        'fecha_inicio' => $apoyo->fecha_inicio,
                        'fecha_fin' => $apoyo->fecha_fin,
                        'activo' => 1,
                        'fecha_creacion' => now(),
                        'fecha_actualizacion' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('Solicitudes') && Schema::hasTable('Seguimiento_Solicitud')) {
            $folios = DB::table('Solicitudes')->select('folio')->get();
            foreach ($folios as $solicitud) {
                $hasTracking = DB::table('Seguimiento_Solicitud')
                    ->where('fk_folio', $solicitud->folio)
                    ->exists();

                if (! $hasTracking) {
                    DB::table('Seguimiento_Solicitud')->insert([
                        'fk_folio' => $solicitud->folio,
                        'estado_proceso' => 'EN_PROCESO',
                        'fecha_creacion' => now(),
                        'fecha_actualizacion' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Historico_Cierre')) {
            Schema::dropIfExists('Historico_Cierre');
        }

        if (Schema::hasTable('Notificaciones')) {
            Schema::dropIfExists('Notificaciones');
        }

        if (Schema::hasTable('Seguimiento_Solicitud')) {
            Schema::dropIfExists('Seguimiento_Solicitud');
        }

        if (Schema::hasTable('Documentos_Expediente')) {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                foreach (['webview_link', 'source_file_id', 'official_file_id', 'observaciones_revision', 'revisado_por', 'fecha_revision'] as $column) {
                    if (Schema::hasColumn('Documentos_Expediente', $column)) {
                        try {
                            if ($column === 'revisado_por') {
                                $table->dropForeign(['revisado_por']);
                            }
                        } catch (\Throwable) {
                            // Ignore if FK does not exist.
                        }
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('Solicitudes')) {
            Schema::table('Solicitudes', function (Blueprint $table) {
                foreach (['folio_institucional', 'permite_correcciones', 'monto_entregado', 'fecha_entrega_recurso', 'fecha_cierre_financiero', 'cuv'] as $column) {
                    if (Schema::hasColumn('Solicitudes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('Hitos_Apoyo')) {
            Schema::dropIfExists('Hitos_Apoyo');
        }
    }
};
