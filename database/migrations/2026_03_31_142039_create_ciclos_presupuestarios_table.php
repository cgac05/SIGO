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
        Schema::create('ciclos_presupuestarios', function (Blueprint $table) {
            $table->id('id_ciclo');
            $table->year('año_fiscal')->unique();
            $table->enum('estado', ['ABIERTO', 'CERRADO'])->default('ABIERTO');
            $table->decimal('presupuesto_total', 15, 2);
            $table->datetime('fecha_apertura')->useCurrent();
            $table->datetime('fecha_cierre_programado')->nullable();
            $table->datetime('fecha_cierre_efectivo')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->index('año_fiscal');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclos_presupuestarios');
    }
};
