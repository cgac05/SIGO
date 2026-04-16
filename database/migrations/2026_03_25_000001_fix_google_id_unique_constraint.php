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
        // Si estamos en SQLite (Tests de GitHub), nos saltamos esto porque SQLite
        // no soporta la sintaxis de SQL Server y maneja los NULLs de forma distinta.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('Usuarios')) {
            // 1. google_id - permitir múltiples NULLs (OAuth opcional)
            DB::statement("
                IF EXISTS (SELECT 1 FROM sys.indexes WHERE name LIKE '%google_id%' AND OBJECT_NAME(object_id) = 'Usuarios')
                    ALTER TABLE Usuarios DROP CONSTRAINT [UQ__Usuarios__google_id]
            ");

            // 2. remember_token - permitir múltiples NULLs
            DB::statement("
                DECLARE @ConstraintName NVARCHAR(255)
                SELECT @ConstraintName = CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'remember_token'
                
                IF @ConstraintName IS NOT NULL
                    BEGIN
                        DECLARE @SQL NVARCHAR(MAX) = 'ALTER TABLE Usuarios DROP CONSTRAINT ['+ @ConstraintName + ']'
                        EXEC sp_executesql @SQL
                    END
            ");

            // 3. Crear filtered unique indexes para permitir NULLs duplicados en SQL Server
            DB::statement("
                IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UQ_Usuarios_google_id_filtered')
                    CREATE UNIQUE INDEX UQ_Usuarios_google_id_filtered 
                    ON Usuarios(google_id) 
                    WHERE google_id IS NOT NULL
            ");

            DB::statement("
                IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UQ_Usuarios_remember_token_filtered')
                    CREATE UNIQUE INDEX UQ_Usuarios_remember_token_filtered 
                    ON Usuarios(remember_token) 
                    WHERE remember_token IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('Usuarios')) {
            DB::statement("
                IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UQ_Usuarios_google_id_filtered')
                    DROP INDEX UQ_Usuarios_google_id_filtered ON Usuarios
            ");

            DB::statement("
                IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UQ_Usuarios_remember_token_filtered')
                    DROP INDEX UQ_Usuarios_remember_token_filtered ON Usuarios
            ");
        }
    }
};