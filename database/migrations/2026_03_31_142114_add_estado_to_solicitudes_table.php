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
        Schema::table('solicitudes', function (Blueprint $table) {
            // Add estado relationship if not exists
            if (!Schema::hasColumn('solicitudes', 'fk_id_estado')) {
                $table->unsignedBigInteger('fk_id_estado')->nullable()
                      ->comment('Foreign key to Cat_EstadosSolicitud');
                
                $table->foreign('fk_id_estado')
                      ->references('id_estado')
                      ->on('Cat_EstadosSolicitud')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            if (Schema::hasColumn('solicitudes', 'fk_id_estado')) {
                $table->dropForeign(['fk_id_estado']);
                $table->dropColumn('fk_id_estado');
            }
        });
    }
};
