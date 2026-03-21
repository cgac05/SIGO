<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('Comentarios_Apoyo')) {
            Schema::create('Comentarios_Apoyo', function (Blueprint $table) {
                $table->increments('id_comentario');
                $table->unsignedInteger('fk_id_apoyo');
                $table->unsignedInteger('fk_id_usuario');
                $table->unsignedInteger('fk_id_comentario_padre')->nullable();
                $table->text('contenido');
                $table->boolean('editado')->default(false);
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->dateTime('fecha_actualizacion')->nullable();

                $table->index('fk_id_apoyo');
                $table->index('fk_id_usuario');
                $table->index('fk_id_comentario_padre');

                $table->foreign('fk_id_apoyo')
                    ->references('id_apoyo')->on('Apoyos')
                    ->onDelete('cascade');

                $table->foreign('fk_id_usuario')
                    ->references('id_usuario')->on('Usuarios')
                    ->onDelete('cascade');

                $table->foreign('fk_id_comentario_padre')
                    ->references('id_comentario')->on('Comentarios_Apoyo')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('Reacciones_ComentarioApoyo')) {
            Schema::create('Reacciones_ComentarioApoyo', function (Blueprint $table) {
                $table->increments('id_reaccion');
                $table->unsignedInteger('fk_id_comentario');
                $table->unsignedInteger('fk_id_usuario');
                $table->string('tipo_reaccion', 20)->default('like');
                $table->dateTime('fecha_creacion')->useCurrent();

                $table->unique(['fk_id_comentario', 'fk_id_usuario', 'tipo_reaccion'], 'uq_reaccion_comentario_usuario_tipo');
                $table->index('fk_id_usuario');

                $table->foreign('fk_id_comentario')
                    ->references('id_comentario')->on('Comentarios_Apoyo')
                    ->onDelete('cascade');

                $table->foreign('fk_id_usuario')
                    ->references('id_usuario')->on('Usuarios')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('Reacciones_ComentarioApoyo');
        Schema::dropIfExists('Comentarios_Apoyo');
    }
};
