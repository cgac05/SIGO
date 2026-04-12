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
        // Verificar si la tabla existe
        if (!Schema::hasTable('presupuesto_apoyos')) {
            // Si no existe, crearla con estructura correcta
            Schema::create('presupuesto_apoyos', function (Blueprint $table) {
                $table->id('id_presupuesto_apoyo');
                $table->unsignedBigInteger('id_apoyo');
                $table->unsignedBigInteger('id_categoria');
                $table->year('ano_fiscal');
                $table->decimal('presupuesto_total', 15, 2);
                $table->decimal('reservado', 15, 2)->default(0);
                $table->decimal('aprobado', 15, 2)->default(0);
                $table->decimal('disponible', 15, 2)->storedAs('presupuesto_total - aprobado');
                $table->decimal('monto_maximo_beneficiario', 15, 2);
                $table->integer('cantidad_beneficiarios_planificada');
                $table->integer('cantidad_beneficiarios_aprobada')->default(0);
                $table->timestamp('fecha_creacion')->useCurrent();

                $table->index(['ano_fiscal', 'id_apoyo']);
                $table->index('id_apoyo');
                $table->index('id_categoria');
            });
            return;
        }

        // Si la tabla existe, verificar que tenga las columnas necesarias
        if (!Schema::hasColumn('presupuesto_apoyos', 'id_apoyo')) {
            // Si no tiene id_apoyo, renombramos o creamos
            if (Schema::hasColumn('presupuesto_apoyos', 'fk_id_apoyo')) {
                // Renombrar fk_id_apoyo a id_apoyo
                Schema::table('presupuesto_apoyos', function (Blueprint $table) {
                    $table->renameColumn('fk_id_apoyo', 'id_apoyo');
                });
            } else {
                // La columna no existe en ninguna forma - error
                throw new \Exception('Tabla presupuesto_apoyos existe pero no tiene columna id_apoyo ni fk_id_apoyo');
            }
        }

        if (!Schema::hasColumn('presupuesto_apoyos', 'id_categoria')) {
            if (Schema::hasColumn('presupuesto_apoyos', 'fk_id_categoria')) {
                Schema::table('presupuesto_apoyos', function (Blueprint $table) {
                    $table->renameColumn('fk_id_categoria', 'id_categoria');
                });
            } else {
                throw new \Exception('Tabla presupuesto_apoyos existe pero no tiene columna id_categoria ni fk_id_categoria');
            }
        }

        // Asegurar que ano_fiscal existe
        if (!Schema::hasColumn('presupuesto_apoyos', 'ano_fiscal')) {
            Schema::table('presupuesto_apoyos', function (Blueprint $table) {
                $table->year('ano_fiscal')->default(now()->year)->after('id_categoria');
            });
        }

        // Asegurar que presupuesto_total existe
        if (!Schema::hasColumn('presupuesto_apoyos', 'presupuesto_total')) {
            Schema::table('presupuesto_apoyos', function (Blueprint $table) {
                $table->decimal('presupuesto_total', 15, 2)->default(0)->after('ano_fiscal');
            });
        }

        // Asegurar que disponible existe
        if (!Schema::hasColumn('presupuesto_apoyos', 'disponible')) {
            Schema::table('presupuesto_apoyos', function (Blueprint $table) {
                $table->decimal('disponible', 15, 2)->default(0)->after('aprobado');
            });
        }

        // Crear índices si no existen
        try {
            DB::statement('CREATE INDEX idx_ano_fiscal_id_apoyo ON presupuesto_apoyos(ano_fiscal, id_apoyo)');
        } catch (\Exception $e) {
            // Index ya existe
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se revierte este fix
    }
};
