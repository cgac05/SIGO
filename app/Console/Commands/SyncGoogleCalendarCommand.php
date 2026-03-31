<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleCalendarCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:google-calendar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza cambios de Google Calendar a SIGO (cada hora)';

    protected $googleCalendarService;

    /**
     * Create a new command instance.
     */
    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        parent::__construct();
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Iniciando sincronización de Google Calendar...');

            // Obtener todos los directivos con permisos de calendario activos
            $directivos = Usuario::whereHas('calendarioPermiso', function ($query) {
                $query->where('activo', 1);
            })->get();

            if ($directivos->isEmpty()) {
                $this->info('No hay directivos con permisos de calendario activos.');
                return 0;
            }

            $this->line("Sincronizando para {$directivos->count()} directivo(s)...");

            $cambios_totales = 0;
            $directivos_procesados = 0;
            $errores = [];

            foreach ($directivos as $directivo) {
                try {
                    $permiso = $directivo->calendarioPermiso;

                    if (!$permiso) {
                        continue;
                    }

                    // Verificar si el token ha expirado
                    if ($permiso->token_expiracion && $permiso->token_expiracion < now()) {
                        $this->line("Token expirado para {$directivo->email}. Omitiendo sincronización.");
                        continue;
                    }

                    // Realizar sincronización
                    $resultado = $this->googleCalendarService->sincronizarDesdeGoogle($directivo->id_usuario);

                    if ($resultado) {
                        $cambios = $resultado['cambios_procesados'] ?? 0;
                        $cambios_totales += $cambios;

                        $this->line("\t✓ {$directivo->email}: {$cambios} cambios sincronizados");
                        $directivos_procesados++;

                        if (!empty($resultado['errores'])) {
                            $errores[$directivo->email] = $resultado['errores'];
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("Error sincronizando directivo {$directivo->email}: " . $e->getMessage());
                    $errores[$directivo->email] = $e->getMessage();
                    Log::error("Error en SyncGoogleCalendarCommand para directivo {$directivo->id_usuario}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Resumen final
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("✓ Sincronización completada");
            $this->info("Directivos procesados: {$directivos_procesados}/{$directivos->count()}");
            $this->info("Cambios sincronizados: {$cambios_totales}");

            if (!empty($errores)) {
                $this->warn("\n⚠️ Errores detectados:");
                foreach ($errores as $directivo => $error_list) {
                    $this->warn("  - {$directivo}: " . (is_array($error_list) ? implode(', ', $error_list) : $error_list));
                }
            }

            Log::info("SyncGoogleCalendarCommand ejecutado: {$directivos_procesados} directivos, {$cambios_totales} cambios");

            return 0;

        } catch (\Exception $e) {
            $this->error('Error crítico en sincronización: ' . $e->getMessage());
            Log::critical("Error crítico en SyncGoogleCalendarCommand: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return 1;
        }
    }
}
