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
        Schema::create('auditoria_folios', function (Blueprint $table) {
            $table->id('id_auditoria_folio');
            $table->string('folio_completo', 50)->unique();
            $table->string('numero_base', 5);
            $table->integer('digito_verificador');
            $table->integer('fk_id_beneficiario')->nullable()->index();
            $table->integer('fk_folio_solicitud')->nullable()->index();
            $table->integer('año_fiscal')->index();
            $table->dateTime('fecha_generacion')->useCurrent();
            $table->integer('generado_por')->nullable();
            $table->string('ip_generacion', 45)->nullable();
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index('fecha_generacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_folios');
    }
};
