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
        Schema::table('Cat_TiposDocumento', function (Blueprint $table) {
            // Agregar columna de peso máximo expresado en MB
            if (!Schema::hasColumn('Cat_TiposDocumento', 'peso_maximo_mb')) {
                $table->integer('peso_maximo_mb')
                    ->nullable()
                    ->default(5)
                    ->comment('Peso máximo permitido en MB para documentos de este tipo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Cat_TiposDocumento', function (Blueprint $table) {
            if (Schema::hasColumn('Cat_TiposDocumento', 'peso_maximo_mb')) {
                $table->dropColumn('peso_maximo_mb');
            }
        });
    }
};
