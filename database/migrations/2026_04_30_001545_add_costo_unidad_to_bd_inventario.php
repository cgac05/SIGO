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
        Schema::table('BD_Inventario', function (Blueprint $table) {
            $table->decimal('costo_unitario', 10, 2)->default(0)->nullable();
            $table->string('unidad_medida', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('BD_Inventario', function (Blueprint $table) {
            $table->dropColumn(['costo_unitario', 'unidad_medida']);
        });
    }
};
