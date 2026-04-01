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
        Schema::table('Solicitudes', function (Blueprint $table) {
            // Agregar campos de presupuesto tracking
            $table->boolean('presupuesto_confirmado')
                ->default(false)
                ->comment('Flag que indica si el presupuesto fue confirmado (irreversible)');

            $table->dateTime('fecha_confirmacion_presupuesto')
                ->nullable()
                ->comment('Timestamp de cuando se confirmó el presupuesto (al firmar directiva)');

            $table->unsignedInteger('directivo_autorizo')
                ->nullable()
                ->comment('ID del directivo que autorizó la solicitud y presupuesto')
                ->constrained('usuarios', 'id_usuario')
                ->onDelete('set null');

            // Índices para queries comunes
            $table->index('presupuesto_confirmado');
            $table->index(['directivo_autorizo', 'fecha_confirmacion_presupuesto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Solicitudes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('directivo_autorizo');
            $table->dropColumn(['presupuesto_confirmado', 'fecha_confirmacion_presupuesto']);
        });
    }
};
