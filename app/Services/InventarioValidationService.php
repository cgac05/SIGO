<?php

namespace App\Services;

use App\Models\Apoyo;
use App\Models\InventarioMaterial;
use Illuminate\Support\Facades\DB;

class InventarioValidationService
{
    /**
     * Validar si hay inventario disponible para una solicitud de apoyo tipo Especie
     * 
     * @param int $folio - Folio de la solicitud
     * @return array - ['valido' => bool, 'razon' => string, 'inventarios_faltantes' => array]
     */
    public function validarInventarioParaSolicitud(int $folio): array
    {
        try {
            // Obtener solicitud y apoyo asociado
            $solicitud = DB::table('Solicitudes')
                ->where('folio', $folio)
                ->first();

            if (!$solicitud) {
                return [
                    'valido' => false,
                    'razon' => 'Solicitud no encontrada',
                    'inventarios_faltantes' => []
                ];
            }

            // Obtener apoyo
            $apoyo = Apoyo::find($solicitud->fk_id_apoyo);
            if (!$apoyo) {
                return [
                    'valido' => false,
                    'razon' => 'Apoyo no encontrado',
                    'inventarios_faltantes' => []
                ];
            }

            // Si no es tipo Especie, no requiere validación de inventario
            if ($apoyo->tipo_apoyo !== 'Especie') {
                return [
                    'valido' => true,
                    'razon' => 'Apoyo no es tipo Especie - no requiere validación de inventario',
                    'inventarios_faltantes' => []
                ];
            }

            // Para apoyos en especie, cada solicitud aprobada representa 1 beneficiario
            // por lo que se descuenta 1 unidad (paquete/kit/beneficio) del inventario total de cupos
            $cantidadSolicitada = 1;

            // Validar si hay suficiente inventario en BD_Inventario
            $inventario = InventarioMaterial::where('fk_id_apoyo', $apoyo->id_apoyo)
                ->first();

            if (!$inventario) {
                return [
                    'valido' => false,
                    'razon' => "No hay inventario registrado para este apoyo Especie (ID: {$apoyo->id_apoyo})",
                    'inventarios_faltantes' => [
                        [
                            'id_apoyo' => $apoyo->id_apoyo,
                            'nombre_apoyo' => $apoyo->nombre_apoyo,
                            'cantidad_disponible' => 0,
                            'cantidad_solicitada' => $cantidadSolicitada,
                            'falta' => $cantidadSolicitada
                        ]
                    ]
                ];
            }

            $stockActual = $inventario->stock_actual ?? 0;

            if ($stockActual < $cantidadSolicitada) {
                return [
                    'valido' => false,
                    'razon' => "Inventario insuficiente para {$apoyo->nombre_apoyo}. Disponible: {$stockActual}, Solicitado: {$cantidadSolicitada}",
                    'inventarios_faltantes' => [
                        [
                            'id_inventario' => $inventario->id_inventario,
                            'id_apoyo' => $apoyo->id_apoyo,
                            'nombre_apoyo' => $apoyo->nombre_apoyo,
                            'cantidad_disponible' => $stockActual,
                            'cantidad_solicitada' => $cantidadSolicitada,
                            'falta' => $cantidadSolicitada - $stockActual
                        ]
                    ]
                ];
            }

            return [
                'valido' => true,
                'razon' => "✅ Inventario disponible: {$stockActual} unidades de {$apoyo->nombre_apoyo}",
                'inventarios_faltantes' => []
            ];

        } catch (\Exception $e) {
            return [
                'valido' => false,
                'razon' => "Error validando inventario: {$e->getMessage()}",
                'inventarios_faltantes' => []
            ];
        }
    }

    /**
     * Registrar movimiento de SALIDA de inventario cuando se aprueba una solicitud
     * 
     * @param int $folio - Folio de la solicitud
     * @param int $usuario_id - ID del usuario que aprueba
     * @return array - ['exito' => bool, 'mensaje' => string]
     */
    public function registrarSalidaInventario(int $folio, int $usuario_id): array
    {
        try {
            DB::beginTransaction();

            // Obtener solicitud y apoyo
            $solicitud = DB::table('Solicitudes')
                ->where('folio', $folio)
                ->first();

            if (!$solicitud) {
                return ['exito' => false, 'mensaje' => 'Solicitud no encontrada'];
            }

            $apoyo = Apoyo::find($solicitud->fk_id_apoyo);
            if (!$apoyo || $apoyo->tipo_apoyo !== 'Especie') {
                return ['exito' => true, 'mensaje' => 'Apoyo no requiere movimiento de inventario'];
            }

            $cantidadSolicitada = 1;

            // Obtener inventario
            $inventario = InventarioMaterial::where('fk_id_apoyo', $apoyo->id_apoyo)
                ->first();

            if (!$inventario) {
                DB::rollBack();
                return ['exito' => false, 'mensaje' => 'Inventario no encontrado'];
            }

            // Registrar movimiento SALIDA
            DB::table('movimientos_inventario')->insert([
                'fk_id_inventario' => $inventario->id_inventario,
                'tipo_movimiento' => 'SALIDA',
                'cantidad' => $cantidadSolicitada,
                'observaciones' => "Aprobación de solicitud #{$folio} - Apoyo: {$apoyo->nombre_apoyo}",
                'registrado_por' => $usuario_id,
                'fecha_movimiento' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Decrementar stock
            $inventario->decrement('stock_actual', $cantidadSolicitada);

            DB::commit();

            return [
                'exito' => true,
                'mensaje' => "✅ Registrado movimiento SALIDA de {$cantidadSolicitada} unidades del inventario"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'exito' => false,
                'mensaje' => "Error registrando salida de inventario: {$e->getMessage()}"
            ];
        }
    }
}
