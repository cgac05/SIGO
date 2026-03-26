<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregación de campos para el módulo administrativo de verificación de documentos.
 * 
 * Campos agregados:
 * - admin_status: Estado de verificación por admin (pendiente, aceptado, rechazado)
 * - admin_observations: Observaciones del admin durante la verificación
 * - verification_token: Token único para validación QR
 * - id_admin: ID del usuario admin que verificó el documento
 * - fecha_verificacion: Fecha de verificación
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Documentos_Expediente')) {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                // Admin verification status
                if (!Schema::hasColumn('Documentos_Expediente', 'admin_status')) {
                    $table->enum('admin_status', ['pendiente', 'aceptado', 'rechazado'])
                          ->default('pendiente')
                          ->after('estado_validacion');
                }
                
                // Admin observations
                if (!Schema::hasColumn('Documentos_Expediente', 'admin_observations')) {
                    $table->text('admin_observations')->nullable()->after('admin_status');
                }
                
                // QR verification token
                if (!Schema::hasColumn('Documentos_Expediente', 'verification_token')) {
                    $table->string('verification_token', 255)->nullable()->unique()->after('admin_observations');
                }
                
                // Admin user ID
                if (!Schema::hasColumn('Documentos_Expediente', 'id_admin')) {
                    $table->unsignedInteger('id_admin')->nullable()->after('verification_token');
                }
                
                // Verification date
                if (!Schema::hasColumn('Documentos_Expediente', 'fecha_verificacion')) {
                    $table->dateTime('fecha_verificacion')->nullable()->after('id_admin');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Documentos_Expediente')) {
            Schema::table('Documentos_Expediente', function (Blueprint $table) {
                $columns = ['admin_status', 'admin_observations', 'verification_token', 'id_admin', 'fecha_verificacion'];
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn('Documentos_Expediente', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
