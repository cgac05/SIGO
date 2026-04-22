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
     * Cambia UNIQUE constraint en fk_id_usuario a FILTERED UNIQUE INDEX
     * Esto permite múltiples NULL (para beneficiarios sin usuario)
     * pero mantiene UNIQUE para valores no-NULL
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE dbo.[Beneficiarios] DROP CONSTRAINT [UQ__Benefici__1698AC3A0A5A23D3]');
        
        DB::statement('
            CREATE UNIQUE INDEX UQ_fk_id_usuario_not_null 
            ON dbo.[Beneficiarios] (fk_id_usuario)
            WHERE fk_id_usuario IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX UQ_fk_id_usuario_not_null ON dbo.[Beneficiarios]');
        
        DB::statement('
            ALTER TABLE dbo.[Beneficiarios] 
            ADD CONSTRAINT [UQ__Benefici__1698AC3A0A5A23D3] 
            UNIQUE (fk_id_usuario)
        ');
    }
};
