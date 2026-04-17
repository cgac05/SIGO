<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla 1: Presupuesto por Categoría (Anual)
        Schema::create('presupuesto_categorias', function (Blueprint $table) {
            $table->id('id_presupuesto');
            $table->year('ano_fiscal');
            $table->string('nombre_categoria', 100);
            $table->decimal('presupuesto_inicial', 15, 2);
            $table->decimal('reservado', 15, 2)->default(0);
            $table->decimal('aprobado', 15, 2)->default(0);

            // PARCHE PARA TESTS (Evita el error en SQLite/GitHub)
            if (config('database.default') === 'sqlite' || DB::getDriverName() === 'sqlite') {
                $table->decimal('disponible', 15, 2)->default(0);
            } else {
                $table->decimal('disponible', 15, 2)->storedAs('presupuesto_inicial - aprobado');
            }

            $table->decimal('porcentaje_utilizacion', 5, 2)->default(0);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->enum('estado', ['ABIERTO', 'CERRADO'])->default('ABIERTO');
            $table->unsignedInteger('creada_por')->nullable();

            $table->unique(['ano_fiscal', 'nombre_categoria']);
            $table->index('ano_fiscal');
        });

        // Tabla 2: Presupuesto por Apoyo
        Schema::create('presupuesto_apoyos', function (Blueprint $table) {
            $table->id('id_presupuesto_apoyo');
            $table->unsignedBigInteger('fk_id_apoyo');
            $table->unsignedBigInteger('fk_id_categoria');
            $table->year('ano_fiscal');
            $table->decimal('presupuesto_total', 15, 2);
            $table->decimal('reservado', 15, 2)->default(0);
            $table->decimal('aprobado', 15, 2)->default(0);

            // PARCHE PARA TESTS
            if (config('database.default') === 'sqlite' || DB::getDriverName() === 'sqlite') {
                $table->decimal('disponible', 15, 2)->default(0);
            } else {
                $table->decimal('disponible', 15, 2)->storedAs('presupuesto_total - aprobado');
            }

            $table->decimal('monto_maximo_beneficiario', 15, 2);
            $table->integer('cantidad_beneficiarios_planificada');
            $table->integer('cantidad_beneficiarios_aprobada')->default(0);
            $table->timestamp('fecha_creacion')->useCurrent();

            $table->foreign('fk_id_apoyo')->references('id')->on('apoyos')->onDelete('restrict');
            $table->foreign('fk_id_categoria')->references('id_presupuesto')->on('presupuesto_categorias')->onDelete('cascade');
        });

        // Tabla 3: Movimientos
        Schema::create('movimientos_presupuestarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_id_solicitud')->nullable();
            $table->unsignedBigInteger('fk_id_apoyo');
            $table->unsignedBigInteger('fk_id_categoria');
            $table->enum('tipo_movimiento', ['RESERVA', 'ASIGNACION_DIRECTIVO', 'LIBERACION', 'RECHAZO']);
            $table->decimal('monto_movimiento', 15, 2);
            $table->year('ano_fiscal');
            $table->unsignedBigInteger('directivo_id')->nullable();
            $table->timestamp('fecha_movimiento')->useCurrent();
            $table->enum('estado_movimiento', ['PENDIENTE', 'CONFIRMADO', 'REVERTIDO'])->default('PENDIENTE');
            $table->text('observaciones')->nullable();

            $table->foreign('fk_id_solicitud')->references('id')->on('solicitudes')->onDelete('set null');
            $table->foreign('fk_id_apoyo')->references('id')->on('apoyos')->onDelete('restrict');
            $table->foreign('fk_id_categoria')->references('id_presupuesto')->on('presupuesto_categorias')->onDelete('restrict');
        });

        // Tabla 4: Ciclos
        Schema::create('ciclos_presupuestarios', function (Blueprint $table) {
            $table->id();
            $table->year('ano_fiscal')->unique();
            $table->enum('estado', ['ABIERTO', 'CERRADO'])->default('ABIERTO');
            $table->date('fecha_inicio');
            $table->date('fecha_cierre')->nullable();
            $table->decimal('presupuesto_total_inicial', 15, 2);
            $table->decimal('presupuesto_total_aprobado', 15, 2)->default(0);
            $table->timestamps();
        });

        // Tabla 5: Alertas
        Schema::create('alertas_presupuesto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_id_categoria');
            $table->enum('nivel_alerta', ['NORMAL', 'AMARILLA', 'ROJA', 'CRITICA']);
            $table->string('mensaje');
            $table->timestamp('fecha_alerta')->useCurrent();
            $table->boolean('vista')->default(false);
            $table->foreign('fk_id_categoria')->references('id_presupuesto')->on('presupuesto_categorias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_presupuesto');
        Schema::dropIfExists('ciclos_presupuestarios');
        Schema::dropIfExists('movimientos_presupuestarios');
        Schema::dropIfExists('presupuesto_apoyos');
        Schema::dropIfExists('presupuesto_categorias');
    }
};