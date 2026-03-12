<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('Cat_TiposDocumento')) {
            return;
        }

        Schema::table('Cat_TiposDocumento', function (Blueprint $table) {
            if (! Schema::hasColumn('Cat_TiposDocumento', 'tipo_archivo_permitido')) {
                $table->string('tipo_archivo_permitido', 20)->default('pdf');
            }
            if (! Schema::hasColumn('Cat_TiposDocumento', 'validar_tipo_archivo')) {
                $table->boolean('validar_tipo_archivo')->default(true);
            }
        });

        DB::table('Cat_TiposDocumento')
            ->whereNull('tipo_archivo_permitido')
            ->update(['tipo_archivo_permitido' => 'pdf']);

        DB::table('Cat_TiposDocumento')
            ->whereNull('validar_tipo_archivo')
            ->update(['validar_tipo_archivo' => 1]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('Cat_TiposDocumento')) {
            return;
        }

        Schema::table('Cat_TiposDocumento', function (Blueprint $table) {
            if (Schema::hasColumn('Cat_TiposDocumento', 'tipo_archivo_permitido')) {
                $table->dropColumn('tipo_archivo_permitido');
            }
            if (Schema::hasColumn('Cat_TiposDocumento', 'validar_tipo_archivo')) {
                $table->dropColumn('validar_tipo_archivo');
            }
        });
    }
};
