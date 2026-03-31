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
        Schema::table('apoyos', function (Blueprint $table) {
            // Ensure apoyos table has all necessary presupuestación fields
            if (!Schema::hasColumn('apoyos', 'id_categoria')) {
                $table->unsignedBigInteger('id_categoria')->nullable()
                      ->comment('Default category for presupuesto');
                
                $table->foreign('id_categoria')
                      ->references('id_categoria')
                      ->on('presupuesto_categorias')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apoyos', function (Blueprint $table) {
            if (Schema::hasColumn('apoyos', 'id_categoria')) {
                $table->dropForeign(['id_categoria']);
                $table->dropColumn('id_categoria');
            }
        });
    }
};
