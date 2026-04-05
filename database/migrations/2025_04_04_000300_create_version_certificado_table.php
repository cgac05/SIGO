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
        Schema::create('version_certificado', function (Blueprint $table) {
            $table->id('id_version');
            $table->unsignedBigInteger('id_historico');
            $table->unsignedInteger('numero_version')->default(1)->comment('Número secuencial de versión');
            $table->string('tipo_cambio', 100)->comment('Tipo de cambio: ARCHIVADO_INICIAL, RESTAURACION, DESCARGA_ARCHIVO, etc.');
            $table->json('datos_version')->nullable()->comment('Snapshot de datos en esa versión');
            $table->text('descripcion')->nullable()->comment('Descripción del cambio');
            $table->unsignedBigInteger('id_usuario')->comment('Usuario que realizó el cambio');
            $table->string('ip_terminal', 45)->nullable()->comment('IP desde la que se realizó la acción');
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_historico')
                ->references('id_historico')
                ->on('historico_cierre')
                ->onDelete('cascade');

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuarios')
                ->onDelete('restrict');

            // Indexes
            $table->index('id_historico');
            $table->index('numero_version');
            $table->index('tipo_cambio');
            $table->index('id_usuario');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_certificado');
    }
};
