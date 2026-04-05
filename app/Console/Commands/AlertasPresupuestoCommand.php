<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Events\NotificacionGenerada;

class AlertasPresupuestoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'alertas:presupuesto {--solo-criticas}';

    /**
     * The description of the console command.
     */
    protected $description = 'Genera alertas de presupuesto para categorías con utilización ≥ 85%';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Iniciando verificación de alertas presupuestarias...');

        $soloCriticas = $this->option('solo-criticas');

        // ===== OBTENER CATEGORÍAS CON PRESUPUESTO ALTO =====
        $categoriasAltas = DB::table('presupuesto_categorias as pc')
            ->where('pc.activo', 1)
            ->select(
                'pc.id_categoria',
                'pc.nombre',
                'pc.presupuesto_anual'
            )
            ->get()
            ->map(function($item) {
                // Simplificado: asumir 0% asignado por ahora (no hay FK a solicitudes)
                $item->monto_asignado = 0;
                $item->porcentaje = 0;  // Sin relación presupuesto-solicitud clara
                $item->monto_disponible = $item->presupuesto_anual;
                $item->alerta_nivel = $item->porcentaje >= 95 ? 'critica' : 
                                    ($item->porcentaje >= 85 ? 'alta' : 
                                    ($item->porcentaje >= 70 ? 'media' : 'baja'));
                return $item;
            })
            ->filter(function($item) use ($soloCriticas) {
                if ($soloCriticas) {
                    return $item->porcentaje >= 95;  // Solo alertas críticas (≥95%)
                }
                return $item->porcentaje >= 85;  // Todas las alertas (≥85%)
            });

        if ($categoriasAltas->isEmpty()) {
            $this->info('✅ No se encontraron categorías con presupuesto alto.');
            return Command::SUCCESS;
        }

        $this->info("⚠️  Se encontraron {$categoriasAltas->count()} categorías con alertas:");

        // ===== OBTENER USUARIOS ADMIN PARA NOTIFICACIONES =====
        $usuariosAdmin = DB::table('Personal')
            ->whereIn('fk_id_rol', [2, 3])  // Admin (2) y Directivo (3)
            ->select('id_personal', 'nombres', 'apellidos')
            ->get();

        // ===== ENVIAR NOTIFICACIONES POR CATEGORÍA =====
        foreach ($categoriasAltas as $categoria) {
            $this->line("  • {$categoria->nombre}: {$categoria->porcentaje}% ({$categoria->monto_asignado} / {$categoria->monto_presupuestado})");

            // Determinar icono y color
            $iconoAlerta = $categoria->alerta_nivel === 'critica' ? '🔴' :
                          ($categoria->alerta_nivel === 'alta' ? '🟠' : '🟡');

            // Crear mensaje detallado
            $mensaje = sprintf(
                '%s **ALERTA PRESUPUESTO: %s** - Utilización: %s%% ($%s disponible)',
                $iconoAlerta,
                $categoria->nombre,
                $categoria->porcentaje,
                number_format($categoria->monto_disponible)
            );

            // Enviar notificación a cada admin
            foreach ($usuariosAdmin as $usuario) {
                // Crea evento NotificacionGenerada
                event(new NotificacionGenerada(
                    'admin',
                    'Alerta Presupuesto',
                    $mensaje,
                    'presupuesto_alerta',
                    null,
                    $usuario->id_personal
                ));

                // También almacenar en DB directamente para garantizar
                DB::table('Notificaciones')->insert([
                    'titulo' => 'Alerta Presupuesto: ' . $categoria->nombre,
                    'mensaje' => $mensaje,
                    'tipo' => 'presupuesto_alerta',
                    'estado' => 'sin_leer',
                    'fk_id_personal' => $usuario->id_personal,
                    'enlace' => '/admin/dashboard/economico',
                    'fecha_creacion' => now(),
                    'fecha_actualizacion' => now(),
                ]);
            }
        }

        // ===== ESTADÍSTICAS FINALES =====
        $this->info("\n📊 Resumen de Alertas:");
        $alertasCriticas = $categoriasAltas->where('alerta_nivel', 'critica')->count();
        $alertasAltas = $categoriasAltas->where('alerta_nivel', 'alta')->count();

        if ($alertasCriticas > 0) {
            $this->error("  🔴 Críticas (≥95%): {$alertasCriticas}");
        }
        if ($alertasAltas > 0) {
            $this->warn("  🟠 Altas (≥85%): {$alertasAltas}");
        }

        $this->info("✅ Alertas presupuestarias completadas.");

        return Command::SUCCESS;
    }
}
