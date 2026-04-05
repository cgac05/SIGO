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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_id_inventario');
            $table->unsignedBigInteger('fk_id_factura')->nullable();
            $table->enum('tipo_movimiento', ['ENTRADA', 'SALIDA', 'AJUSTE'])->default('ENTRADA');
            $table->decimal('cantidad', 10, 3);
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->dateTime('fecha_movimiento');
            $table->timestamps();
            
            $table->foreign('fk_id_inventario')->references('id_inventario')->on('inventario_material')->onDelete('cascade');
            $table->foreign('fk_id_factura')->references('id_factura')->on('facturas_compra')->onDelete('set null');
            $table->foreign('registrado_por')->references('id_usuario')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
