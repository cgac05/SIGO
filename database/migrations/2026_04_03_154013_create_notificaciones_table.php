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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_beneficiario');
            $table->enum('tipo', ['documento_rechazado', 'hito_cambio', 'solicitud_rechazada']);
            $table->string('titulo');
            $table->text('mensaje');
            $table->json('datos')->nullable(); // Extra context (folio, motivo, etc)
            $table->string('accion_url')->nullable(); // Link a la solicitud
            $table->boolean('leida')->default(false);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('id_beneficiario')->references('id')->on('usuarios')->onDelete('cascade');
            
            // Indexes
            $table->index('id_beneficiario');
            $table->index('tipo');
            $table->index('leida');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
