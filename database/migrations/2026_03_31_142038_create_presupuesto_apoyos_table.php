<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('presupuesto_apoyos', function (Blueprint $table) {
            $table->id('id_presupuesto_apoyo');
            $table->unsignedBigInteger('id_apoyo');
            $table->unsignedBigInteger('id_categoria');
            $table->decimal('costo_estimado', 15, 2);
            $table->enum('estado', ['RESERVADO', 'APROBADO'])->default('RESERVADO');
            $table->datetime('fecha_reserva')->nullable();
            $table->datetime('fecha_aprobacion')->nullable();
            $table->unsignedBigInteger('id_directivo_aprobador')->nullable();
            $table->timestamps();
            
            $table->index('id_apoyo');
            $table->index('id_categoria');
            $table->index('estado');
            $table->index('id_directivo_aprobador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_apoyos');
    }
};
