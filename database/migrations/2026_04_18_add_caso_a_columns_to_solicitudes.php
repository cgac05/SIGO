<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar columnas necesarias para Caso A a tabla Solicitudes
     * 
     * Columnas agregadas:
     * - origen_solicitud: marca si vino de admin_caso_a (presencial) o flujo ordinario
     * - creada_por_admin: booleano indica si fue creada por admin
     * - admin_creador: id del admin que registró la presencia física
     */
    public function up(): void
    {
        Schema::table('Solicitudes', function (Blueprint $table) {
            // Agregar columnas si no existen
            if (!Schema::hasColumn('Solicitudes', 'origen_solicitud')) {
                $table->string('origen_solicitud', 50)->nullable()->default('ordinario')->after('folio');
            }
            
            if (!Schema::hasColumn('Solicitudes', 'creada_por_admin')) {
                $table->boolean('creada_por_admin')->default(false)->after('origen_solicitud');
            }
            
            if (!Schema::hasColumn('Solicitudes', 'admin_creador')) {
                $table->unsignedInteger('admin_creador')->nullable()->after('creada_por_admin');
            }
            
            if (!Schema::hasColumn('Solicitudes', 'beneficiario_id')) {
                $table->unsignedInteger('beneficiario_id')->nullable()->after('fk_curp');
            }

            if (!Schema::hasColumn('Solicitudes', 'apoyo_id')) {
                $table->unsignedInteger('apoyo_id')->nullable()->after('fk_id_apoyo');
            }

            if (!Schema::hasColumn('Solicitudes', 'estado_solicitud')) {
                $table->string('estado_solicitud', 50)->nullable()->after('fk_id_estado');
            }

            if (!Schema::hasColumn('Solicitudes', 'fecha_cambio_estado')) {
                $table->dateTime('fecha_cambio_estado')->nullable()->after('fecha_actualizacion');
            }
        });
    }

    /**
     * Revert the migrations.
     */
    public function down(): void
    {
        Schema::table('Solicitudes', function (Blueprint $table) {
            if (Schema::hasColumn('Solicitudes', 'origen_solicitud')) {
                $table->dropColumn('origen_solicitud');
            }
            if (Schema::hasColumn('Solicitudes', 'creada_por_admin')) {
                $table->dropColumn('creada_por_admin');
            }
            if (Schema::hasColumn('Solicitudes', 'admin_creador')) {
                $table->dropColumn('admin_creador');
            }
            if (Schema::hasColumn('Solicitudes', 'beneficiario_id')) {
                $table->dropColumn('beneficiario_id');
            }
            if (Schema::hasColumn('Solicitudes', 'apoyo_id')) {
                $table->dropColumn('apoyo_id');
            }
            if (Schema::hasColumn('Solicitudes', 'estado_solicitud')) {
                $table->dropColumn('estado_solicitud');
            }
            if (Schema::hasColumn('Solicitudes', 'fecha_cambio_estado')) {
                $table->dropColumn('fecha_cambio_estado');
            }
        });
    }
};
