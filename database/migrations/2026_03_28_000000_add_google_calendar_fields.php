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
        // Agregar campos a tabla hitos_apoyo para Google Calendar
        if (Schema::hasTable('Hitos_Apoyo') && !Schema::hasColumn('Hitos_Apoyo', 'google_calendar_event_id')) {
            Schema::table('Hitos_Apoyo', function (Blueprint $table) {
                $table->string('google_calendar_event_id')->nullable()->after('descripcion');
                $table->boolean('google_calendar_sync')->default(true)->after('google_calendar_event_id');
                $table->dateTime('ultima_sincronizacion')->nullable()->after('google_calendar_sync');
                $table->boolean('cambios_locales_pendientes')->default(false)->after('ultima_sincronizacion');
            });
        }

        // Agregar campos a tabla Apoyos para Google Calendar config
        if (Schema::hasTable('Apoyos') && !Schema::hasColumn('Apoyos', 'sincronizar_calendario')) {
            Schema::table('Apoyos', function (Blueprint $table) {
                $table->boolean('sincronizar_calendario')->default(true)->after('descripcion');
                $table->integer('recordatorio_dias')->default(3)->after('sincronizar_calendario');
                $table->string('google_group_email')->nullable()->after('recordatorio_dias');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('Hitos_Apoyo')) {
            Schema::table('Hitos_Apoyo', function (Blueprint $table) {
                $table->dropColumn([
                    'google_calendar_event_id',
                    'google_calendar_sync',
                    'ultima_sincronizacion',
                    'cambios_locales_pendientes',
                ]);
            });
        }

        if (Schema::hasTable('Apoyos')) {
            Schema::table('Apoyos', function (Blueprint $table) {
                $table->dropColumn([
                    'sincronizar_calendario',
                    'recordatorio_dias',
                    'google_group_email',
                ]);
            });
        }
    }
};
