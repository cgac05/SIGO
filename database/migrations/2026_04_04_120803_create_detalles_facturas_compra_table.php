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
        Schema::create('detalles_facturas_compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_id_factura');
            $table->unsignedBigInteger('fk_id_inventario');
            $table->decimal('cantidad_comprada', 10, 3);
            $table->decimal('costo_unitario', 12, 2);
            $table->string('lote_numero', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
            
            $table->foreign('fk_id_factura')->references('id_factura')->on('facturas_compra')->onDelete('cascade');
            $table->foreign('fk_id_inventario')->references('id_inventario')->on('inventario_material')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_facturas_compra');
    }
};
