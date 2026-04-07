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
        Schema::table('Usuarios', function (Blueprint $table) {
            // Foto de Perfil
            if (!Schema::hasColumn('Usuarios', 'foto_perfil')) {
                $table->string('foto_perfil')->nullable()->comment('Ruta a foto de perfil local');
            }

            // 2FA
            if (!Schema::hasColumn('Usuarios', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->comment('Autenticación de dos factores');
            }

            if (!Schema::hasColumn('Usuarios', 'two_factor_secret')) {
                $table->string('two_factor_secret')->nullable()->comment('Secret para 2FA');
            }

            // Preferencias de Notificaciones
            if (!Schema::hasColumn('Usuarios', 'notif_email_news')) {
                $table->boolean('notif_email_news')->default(true)->comment('Recibir noticias');
            }

            if (!Schema::hasColumn('Usuarios', 'notif_email_apoyos')) {
                $table->boolean('notif_email_apoyos')->default(true)->comment('Notificaciones de apoyos');
            }

            if (!Schema::hasColumn('Usuarios', 'notif_email_status')) {
                $table->boolean('notif_email_status')->default(true)->comment('Cambios de estado');
            }

            if (!Schema::hasColumn('Usuarios', 'notif_email_marketing')) {
                $table->boolean('notif_email_marketing')->default(false)->comment('Promociones');
            }

            // ARCO - Solicitudes
            if (!Schema::hasColumn('Usuarios', 'arco_cancelacion_solicitada')) {
                $table->boolean('arco_cancelacion_solicitada')->default(false)->comment('Cancelación ARCO solicitada');
            }

            if (!Schema::hasColumn('Usuarios', 'arco_cancelacion_fecha')) {
                $table->dateTime('arco_cancelacion_fecha')->nullable()->comment('Fecha de solicitud de cancelación');
            }

            if (!Schema::hasColumn('Usuarios', 'arco_cancelacion_razon')) {
                $table->text('arco_cancelacion_razon')->nullable()->comment('Razón de cancelación');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Usuarios', function (Blueprint $table) {
            $table->dropColumnIfExists('foto_perfil');
            $table->dropColumnIfExists('two_factor_enabled');
            $table->dropColumnIfExists('two_factor_secret');
            $table->dropColumnIfExists('notif_email_news');
            $table->dropColumnIfExists('notif_email_apoyos');
            $table->dropColumnIfExists('notif_email_status');
            $table->dropColumnIfExists('notif_email_marketing');
            $table->dropColumnIfExists('arco_cancelacion_solicitada');
            $table->dropColumnIfExists('arco_cancelacion_fecha');
            $table->dropColumnIfExists('arco_cancelacion_razon');
        });
    }
};
