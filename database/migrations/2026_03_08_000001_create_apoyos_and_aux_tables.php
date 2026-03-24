<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración que crea las tablas necesarias para la funcionalidad de Apoyos.
 *
 * Tablas creadas:
 * - Apoyos: registro maestro de tipos de apoyo.
 * - BD_Finanzas: registros financieros asociados a apoyos de tipo 'Económico'.
 * - BD_Inventario: registros de stock asociados a apoyos de tipo 'Especie'.
 *
 * Atención:
 * - Las FK están configuradas para eliminar en cascada los registros secundarios
 *   si se borra el apoyo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal: Apoyos
        // Tabla principal: Apoyos
        // Comentarios:
        // - `id_apoyo` es la PK autoincremental usada en toda la lógica del módulo.
        // - `monto_maximo` se guarda como decimal(19,4) para compatibilidad con SQL Server.
        Schema::create('Apoyos', function (Blueprint $table) {
            $table->increments('id_apoyo');
            $table->string('nombre_apoyo', 100);
            $table->string('tipo_apoyo', 20);
            // Se usa decimal para compatibilidad con SQL Server MONEY/DECIMAL
            $table->decimal('monto_maximo', 19, 4)->default(0);
            $table->boolean('activo')->default(true);
        });

        // Tabla auxiliar para apoyos económicos
        // Tabla auxiliar para apoyos económicos: BD_Finanzas
        // - `fk_id_apoyo` referencia a `Apoyos.id_apoyo` y se elimina en cascada.
        Schema::create('BD_Finanzas', function (Blueprint $table) {
            $table->increments('id_finanza');
            $table->unsignedInteger('fk_id_apoyo');
            $table->decimal('monto_asignado', 19, 4)->default(0);
            $table->decimal('monto_ejercido', 19, 4)->default(0);
            $table->foreign('fk_id_apoyo')->references('id_apoyo')->on('Apoyos')->onDelete('cascade');
        });

        // Tabla auxiliar para apoyos en especie
        // Tabla auxiliar para apoyos en especie: BD_Inventario
        Schema::create('BD_Inventario', function (Blueprint $table) {
            $table->increments('id_inventario');
            $table->unsignedInteger('fk_id_apoyo');
            $table->integer('stock_actual')->default(0);
            $table->foreign('fk_id_apoyo')->references('id_apoyo')->on('Apoyos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('BD_Inventario');
        Schema::dropIfExists('BD_Finanzas');
        Schema::dropIfExists('Apoyos');
    }
};
