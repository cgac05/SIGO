<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Permite que beneficiarios no registrados (manual entry) tengan fk_id_usuario = NULL
     * Estos son beneficiarios que el admin registra presencialmente sin usuario del sistema
     */
    public function up(): void
    {
        // Usar SQL raw para SQL Server con nombre de tabla exacto
        DB::statement('ALTER TABLE dbo.Beneficiarios ALTER COLUMN fk_id_usuario INT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE dbo.Beneficiarios ALTER COLUMN fk_id_usuario INT NOT NULL');
    }
};
