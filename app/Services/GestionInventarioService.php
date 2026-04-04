<?php

namespace App\Services;

use App\Models\PresupuestoCategoria;
use App\Models\PresupuestoApoyo;
use App\Models\InventarioMaterial;
use App\Models\FacturaCompra;
use App\Models\DetalleFacturaCompra;
use Illuminate\Support\Facades\DB;
use Exception;

class GestionInventarioService
{
    /**
     * Crear factura de compra y registrar entrada de inventario
     */
    public function crearFacturaYRegistrarCompra(
        string $numeroFactura,
        string $nombreProveedor,
        float $montoTotal,
        int $registradoPor,
        array $detalles, // [['id_inventario' => int, 'cantidad' => float, 'costo_unitario' => float], ...]
        ?string $rutaArchivo = null,
        ?string $observaciones = null
    ): FacturaCompra {
        DB::beginTransaction();
        try {
            // Crear factura
            $factura = FacturaCompra::create([
                'numero_factura' => $numeroFactura,
                'nombre_proveedor' => $nombreProveedor,
                'monto_total' => $montoTotal,
                'registrado_por' => $registradoPor,
                'archivo_factura' => $rutaArchivo,
                'observaciones' => $observaciones,
                'estado' => 'Recibida',
            ]);

            // Crear detalles y actualizar inventario
            foreach ($detalles as $detalle) {
                DetalleFacturaCompra::create([
                    'fk_id_factura' => $factura->id_factura,
                    'fk_id_inventario' => $detalle['id_inventario'],
                    'cantidad_comprada' => $detalle['cantidad'],
                    'costo_unitario' => $detalle['costo_unitario'],
                ]);

                // Actualizar cantidad en inventario
                $inventario = InventarioMaterial::findOrFail($detalle['id_inventario']);
                $cantidadAnterior = $inventario->cantidad_actual;
                $cantidadNueva = $cantidadAnterior + $detalle['cantidad'];

                $inventario->update(['cantidad_actual' => $cantidadNueva]);

                // Registrar movimiento
                DB::table('movimientos_inventario')->insert([
                    'fk_id_inventario' => $detalle['id_inventario'],
                    'tipo_movimiento' => 'Entrada',
                    'cantidad' => $detalle['cantidad'],
                    'cantidad_anterior' => $cantidadAnterior,
                    'cantidad_nueva' => $cantidadNueva,
                    'fk_id_factura' => $factura->id_factura,
                    'motivo' => "Compra factura #{$numeroFactura}",
                    'realizado_por' => $registradoPor,
                    'fecha_movimiento' => now(),
                ]);
            }

            DB::commit();
            return $factura;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error al crear factura: " . $e->getMessage());
        }
    }

    /**
     * Validar disponibilidad de presupuesto para un apoyo
     */
    public function validarPresupuestoDisponible(int $idCategoria, float $montoRequerido): array
    {
        $categoria = PresupuestoCategoria::find($idCategoria);

        if (!$categoria) {
            return [
                'valido' => false,
                'razon' => 'Categoría presupuestaria no encontrada',
            ];
        }

        if ($categoria->disponible < $montoRequerido) {
            return [
                'valido' => false,
                'razon' => "Presupuesto insuficiente. Disponible: \${$categoria->disponible}, Requerido: \${$montoRequerido}",
                'disponible' => $categoria->disponible,
                'requerido' => $montoRequerido,
            ];
        }

        return [
            'valido' => true,
            'disponible' => $categoria->disponible,
        ];
    }

    /**
     * Reservar presupuesto para un apoyo
     */
    public function reservarPresupuestoApoyo(int $idApoyo, int $idCategoria, float $montoAsignado, int $usuarioId): PresupuestoApoyo
    {
        // Validar disponibilidad primero
        $validacion = $this->validarPresupuestoDisponible($idCategoria, $montoAsignado);
        if (!$validacion['valido']) {
            throw new Exception($validacion['razon']);
        }

        DB::beginTransaction();
        try {
            // Crear registro de presupuesto asociado al apoyo
            $presupuestoApoyo = PresupuestoApoyo::create([
                'id_apoyo' => $idApoyo,
                'id_categoria' => $idCategoria,
                'costo_estimado' => $montoAsignado,
                'estado' => 'RESERVADO',
                'id_directivo_aprobador' => $usuarioId,
                'fecha_reserva' => now(),
            ]);

            // Crear movimiento presupuestario
            DB::table('movimientos_presupuestarios')->insert([
                'id_categoria' => $idCategoria,
                'id_apoyo' => $idApoyo,
                'tipo_movimiento' => 'RESERVA',
                'monto' => $montoAsignado,
                'usuario_id' => $usuarioId,
                'fecha_movimiento' => now(),
                'descripcion' => "Reserva de presupuesto para apoyo #{$idApoyo}",
            ]);

            // Actualizar disponible en categoría
            $categoria = PresupuestoCategoria::find($idCategoria);
            $categoria->update([
                'disponible' => $categoria->disponible - $montoAsignado,
            ]);

            DB::commit();
            return $presupuestoApoyo;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error al reservar presupuesto: " . $e->getMessage());
        }
    }

    /**
     * Liberar presupuesto reservado (ej: cuando se rechaza una solicitud)
     */
    public function liberarPresupuestoApoyo(int $idPresupuestoApoyo, int $usuarioId): void
    {
        $presupuestApoyo = PresupuestoApoyo::findOrFail($idPresupuestoApoyo);

        if ($presupuestApoyo->estado !== 'RESERVADO') {
            throw new Exception("No se puede liberar presupuesto que no está reservado");
        }

        DB::beginTransaction();
        try {
            // Restaurar disponible en categoría
            $categoria = PresupuestoCategoria::find($presupuestApoyo->id_categoria);
            $categoria->update([
                'disponible' => $categoria->disponible + $presupuestApoyo->costo_estimado,
            ]);

            // Registrar movimiento de liberación
            DB::table('movimientos_presupuestarios')->insert([
                'id_categoria' => $presupuestApoyo->id_categoria,
                'id_apoyo' => $presupuestApoyo->id_apoyo,
                'tipo_movimiento' => 'LIBERACION',
                'monto' => -$presupuestApoyo->costo_estimado,
                'usuario_id' => $usuarioId,
                'fecha_movimiento' => now(),
                'descripcion' => "Liberación de presupuesto reservado",
            ]);

            // Cambiar estado
            $presupuestApoyo->update(['estado' => 'LIBERADO']);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error al liberar presupuesto: " . $e->getMessage());
        }
    }

    /**
     * Obtener resumen de inventario por apoyo
     */
    public function obtenerResumenInventarioApoyo(int $idApoyo): array
    {
        $materiales = InventarioMaterial::where('fk_id_apoyo', $idApoyo)->get();

        $totalUnidades = $materiales->sum('cantidad_actual');
        $valortotalEstimado = $materiales->sum(function ($m) {
            return $m->cantidad_actual * $m->costo_unitario;
        });
        $materialesConStockBajo = $materiales->where('necesitaReorden')->count();

        return [
            'total_unidades' => $totalUnidades,
            'valor_total_estimado' => $valortotalEstimado,
            'materiales_con_stock_bajo' => $materialesConStockBajo,
            'materiales' => $materiales->map(fn($m) => [
                'id' => $m->id_inventario,
                'codigo' => $m->codigo_material,
                'nombre' => $m->nombre_material,
                'cantidad_actual' => $m->cantidad_actual,
                'cantidad_minima' => $m->cantidad_minima,
                'costo_unitario' => $m->costo_unitario,
                'necesita_reorden' => $m->necesitaReorden(),
            ]),
        ];
    }
}
