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
        Schema::create('archivo_certificado', function (Blueprint $table) {
            $table->id('id_archivo');
            $table->unsignedBigInteger('id_historico');
            $table->string('uuid_archivo', 36)->unique()->comment('Identificador único del archivo');
            $table->string('nombre_archivo', 255)->comment('Nombre del archivo ZIP');
            $table->text('ruta_almacenamiento')->comment('Ruta completa de almacenamiento');
            $table->unsignedBigInteger('tamanio_bytes')->comment('Tamaño en bytes');
            $table->string('hash_integridad', 64)->comment('Hash SHA-256 para verificación');
            $table->string('tipo_compresion', 50)->default('zip')->comment('Tipo de compresión utilizado');
            $table->text('motivo_archivado')->nullable()->comment('Motivo del archivamiento');
            $table->boolean('activo')->default(true)->comment('Indica si el archivo está activo');
            $table->unsignedBigInteger('id_usuario_archivador')->comment('Usuario que archivó');
            $table->datetime('fecha_eliminacion')->nullable()->comment('Fecha cuando se marcó como inactivo');
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_historico')
                ->references('id_historico')
                ->on('historico_cierre')
                ->onDelete('cascade');

            $table->foreign('id_usuario_archivador')
                ->references('id_usuario')
                ->on('usuarios')
                ->onDelete('restrict');

            // Indexes
            $table->index('id_historico');
            $table->index('activo');
            $table->index('uuid_archivo');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivo_certificado');
    }
};
