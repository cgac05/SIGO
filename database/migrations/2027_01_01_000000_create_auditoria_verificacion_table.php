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
        Schema::create('auditoria_verificacion', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->unsignedBigInteger('id_historico');
            $table->string('tipo_verificacion', 100)->comment('Tipo de verificación realizada');
            $table->json('detalles')->nullable()->comment('Detalles de la verificación en formato JSON');
            $table->string('ip_terminal', 45)->nullable()->comment('IP desde donde se realizó la validación');
            $table->unsignedBigInteger('id_usuario_validador');
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_historico')
                ->references('id_historico')
                ->on('historico_cierre')
                ->onDelete('cascade');

            $table->foreign('id_usuario_validador')
                ->references('id_usuario')
                ->on('usuarios')
                ->onDelete('restrict');

            // Indexes
            $table->index('id_historico');
            $table->index('tipo_verificacion');
            $table->index('id_usuario_validador');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_verificacion');
    }
};
