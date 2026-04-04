<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to avoid Laravel's quoting issues with SQL Server
        DB::statement('IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'Cat_TiposDocumento\' AND COLUMN_NAME = \'peso_maximo_mb\') 
            ALTER TABLE Cat_TiposDocumento ADD peso_maximo_mb INT DEFAULT 5 NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'Cat_TiposDocumento\' AND COLUMN_NAME = \'peso_maximo_mb\') 
            ALTER TABLE Cat_TiposDocumento DROP COLUMN peso_maximo_mb');
    }
};
