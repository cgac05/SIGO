<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('Hitos_Apoyo')) {
            Schema::create('Hitos_Apoyo', function (Blueprint $table) {
                $table->increments('id_hito');
                $table->unsignedInteger('fk_id_apoyo');
                $table->string('slug_hito', 80)->nullable();
                $table->string('titulo_hito', 150);
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->unsignedSmallInteger('orden')->default(0);
                $table->boolean('es_base')->default(false);
                $table->boolean('activo')->default(true);
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->dateTime('fecha_actualizacion')->nullable();

                $table->index('fk_id_apoyo');
                $table->index(['fk_id_apoyo', 'orden']);

                $table->foreign('fk_id_apoyo')
                    ->references('id_apoyo')->on('Apoyos')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('Hitos_Apoyo');
    }
};
