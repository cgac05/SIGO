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
        Schema::table('ciclos_presupuestarios', function (Blueprint $table) {
            // Add presupuesto_total_inicial if it doesn't exist
            if (!Schema::hasColumn('ciclos_presupuestarios', 'presupuesto_total_inicial')) {
                $table->decimal('presupuesto_total_inicial', 15, 2)->nullable()->after('presupuesto_total');
            }
            
            // Add fecha_inicio if it doesn't exist  
            if (!Schema::hasColumn('ciclos_presupuestarios', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('fecha_apertura');
            }
            
            // Add fecha_fin if it doesn't exist
            if (!Schema::hasColumn('ciclos_presupuestarios', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ciclos_presupuestarios', function (Blueprint $table) {
            if (Schema::hasColumn('ciclos_presupuestarios', 'presupuesto_total_inicial')) {
                $table->dropColumn('presupuesto_total_inicial');
            }
            if (Schema::hasColumn('ciclos_presupuestarios', 'fecha_inicio')) {
                $table->dropColumn('fecha_inicio');
            }
            if (Schema::hasColumn('ciclos_presupuestarios', 'fecha_fin')) {
                $table->dropColumn('fecha_fin');
            }
        });
    }
};