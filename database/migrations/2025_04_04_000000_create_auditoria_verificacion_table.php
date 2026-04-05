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
            $table->bigIncrements('id_auditoria');
            $table->unsignedBigInteger('id_historico');
            $table->string('tipo_verificacion', 100);
            $table->longText('detalles')->nullable();
            $table->ipAddress('ip_terminal')->nullable();
            $table->unsignedBigInteger('id_usuario_validador')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_historico')
                ->references('id_historico')
                ->on('historicos_cierres')
                ->onDelete('cascade');

            $table->foreign('id_usuario_validador')
                ->references('id')
                ->on('usuarios')
                ->onDelete('set null');

            // Indexes
            $table->index('id_historico');
            $table->index('tipo_verificacion');
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
