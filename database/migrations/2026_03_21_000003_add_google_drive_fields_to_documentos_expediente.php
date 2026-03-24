<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Documentos_Expediente')) {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                if (!Schema::hasColumn('Documentos_Expediente', 'origen_archivo')) {
                    $table->string('origen_archivo', 20)->default('local')->after('ruta_archivo');
                }
                if (!Schema::hasColumn('Documentos_Expediente', 'google_file_id')) {
                    $table->string('google_file_id', 255)->nullable()->after('origen_archivo');
                }
                if (!Schema::hasColumn('Documentos_Expediente', 'google_file_name')) {
                    $table->string('google_file_name', 500)->nullable()->after('google_file_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Documentos_Expediente')) {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                if (Schema::hasColumn('Documentos_Expediente', 'origen_archivo')) {
                    $table->dropColumn('origen_archivo');
                }
                if (Schema::hasColumn('Documentos_Expediente', 'google_file_id')) {
                    $table->dropColumn('google_file_id');
                }
                if (Schema::hasColumn('Documentos_Expediente', 'google_file_name')) {
                    $table->dropColumn('google_file_name');
                }
            });
        }
    }
};
