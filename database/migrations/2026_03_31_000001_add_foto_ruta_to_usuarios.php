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
        // Agregar foto_ruta a tabla Usuarios
        if (!Schema::hasColumn('Usuarios', 'foto_ruta')) {
            Schema::table('Usuarios', function (Blueprint $table) {
                $table->string('foto_ruta', 500)->nullable()->after('google_avatar')
                    ->comment('Ruta local de la foto del usuario (beneficiario o personal)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('Usuarios', 'foto_ruta')) {
                $table->dropColumn('foto_ruta');
            }
        });
    }
};
