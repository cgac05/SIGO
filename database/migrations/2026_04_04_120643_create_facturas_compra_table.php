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
        Schema::create('facturas_compra', function (Blueprint $table) {
            $table->id('id_factura');
            $table->string('numero_factura', 50)->unique();
            $table->string('nombre_proveedor', 150);
            $table->string('rfc_proveedor', 13)->nullable();
            $table->dateTime('fecha_compra');
            $table->dateTime('fecha_recepcion')->nullable();
            $table->decimal('monto_total', 12, 2);
            $table->string('estado', 50)->default('Pendiente Recepción');
            $table->text('observaciones')->nullable();
            $table->string('archivo_factura')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamps();
            
            $table->foreign('registrado_por')->references('id_usuario')->on('usuarios')->onDelete('set null');
            $table->foreign('actualizado_por')->references('id_usuario')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas_compra');
    }
};
