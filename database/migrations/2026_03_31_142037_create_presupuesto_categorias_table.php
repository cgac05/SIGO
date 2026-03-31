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
        Schema::create('presupuesto_categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('presupuesto_anual', 15, 2);
            $table->decimal('disponible', 15, 2);
            $table->unsignedBigInteger('id_ciclo');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('id_ciclo');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_categorias');
    }
};
