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
        Schema::create('movimientos_presupuestarios', function (Blueprint $table) {
            $table->id('id_movimiento');
            $table->unsignedBigInteger('id_presupuesto_apoyo');
            $table->unsignedBigInteger('id_solicitud')->nullable();
            $table->enum('tipo_movimiento', ['RESERVACION', 'ASIGNACION_DIRECTIVO', 'CANCELACION', 'REITERACION']);
            $table->decimal('monto', 15, 2);
            $table->unsignedBigInteger('id_usuario_responsable');
            $table->text('notas')->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->datetime('fecha_movimiento')->useCurrent();
            $table->timestamps();
            
            $table->index('id_presupuesto_apoyo');
            $table->index('id_solicitud');
            $table->index('tipo_movimiento');
            $table->index('id_usuario_responsable');
            $table->index('fecha_movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_presupuestarios');
    }
};
