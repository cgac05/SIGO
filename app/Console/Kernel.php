<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sincronizar Google Calendar cada hora
        $schedule->command('sync:google-calendar')
            ->hourly()
            ->runInBackground()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Google Calendar sync completado exitosamente');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Google Calendar sync falló');
            });

        // Limpiar estados OAuth expirados cada 30 minutos
        $schedule->command('oauth:cleanup')
            ->everyThirtyMinutes()
            ->runInBackground();

        // Limpiar cancelaciones que han cumplido período de gracia (30 días)
        // Nota: FASE 16 - Implementar después
        // $schedule->command('cleanup:pending-cancellations')
        //     ->daily()
        //     ->at('02:00');

        // ====================================================================
        // MÓDULO 7B: ALERTAS PRESUPUESTARIAS
        // ====================================================================
        // Verificar presupuestos altos diariamente a las 7:00 AM
        $schedule->command('alertas:presupuesto')
            ->dailyAt('07:00')
            ->runInBackground()
            ->skipWhenMaintenance()
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('✅ Alertas presupuestarias verificadas exitosamente');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('❌ Error en verificación de alertas presupuestarias');
            });

        // Verificación crítica cada 3 horas (solo presupuestos ≥95%)
        $schedule->command('alertas:presupuesto --solo-criticas')
            ->everyThreeHours()
            ->runInBackground()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::warning('Verificación crítica de presupuesto no completada');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
