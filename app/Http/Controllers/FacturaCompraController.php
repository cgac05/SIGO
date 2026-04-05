<?php

namespace App\Http\Controllers;

use App\Models\FacturaCompra;
use App\Models\DetalleFacturaCompra;
use App\Models\InventarioMaterial;
use App\Models\MovimientoInventario;
use App\Services\GestionInventarioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FacturaCompraController extends Controller
{
    protected $inventarioService;

    public function __construct(GestionInventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Listar todas las facturas de compra
     * GET /admin/facturas
     */
    public function index(Request $request)
    {
        $query = FacturaCompra::with('registradoPor', 'detalles');

        // Filtros
        if ($request->filled('proveedor')) {
            $query->porProveedor($request->proveedor);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_compra', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_compra', '<=', $request->fecha_hasta);
        }

        $facturas = $query->recientes()->paginate(15);

        return view('admin.facturas.index', compact('facturas'));
    }

    /**
     * Mostrar formulario de creación
     * GET /admin/facturas/create
     */
    public function create()
    {
        $inventarios = InventarioMaterial::activos()->get();
        $estados = ['Recibida', 'Pendiente Recepción', 'Rechazada', 'Cancelada'];
        $titulo = 'Crear Nueva Factura';

        return view('admin.facturas.create', compact('inventarios', 'estados', 'titulo'));
    }

    /**
     * Guardar nueva factura
     * POST /admin/facturas
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_factura' => 'required|unique:facturas_compra,numero_factura|max:50',
            'nombre_proveedor' => 'required|string|max:150',
            'fecha_compra' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'estado' => 'required|in:Recibida,Pendiente Recepción,Cancelada',
            'archivo_factura' => 'nullable|file|max:5120',
            'detalles' => 'required|array|min:1',
            'detalles.*.inventario_id' => 'required|exists:inventario_material,id_inventario',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Procesar archivo
            $rutaArchivo = null;
            if ($request->hasFile('archivo_factura')) {
                $rutaArchivo = 'factura_' . time() . '_' . $request->file('archivo_factura')->getClientOriginalName();
                $request->file('archivo_factura')->storeAs('facturas', $rutaArchivo, 'public');
            }

            // Crear factura
            $factura = FacturaCompra::create([
                'numero_factura' => $validated['numero_factura'],
                'nombre_proveedor' => $validated['nombre_proveedor'],
                'fecha_compra' => $validated['fecha_compra'],
                'monto_total' => $validated['monto_total'],
                'estado' => $validated['estado'],
                'archivo_factura' => $rutaArchivo,
                'registrado_por' => Auth::id(),
            ]);

            // Crear detalles y registrar movimientos de inventario
            foreach ($validated['detalles'] as $detalle) {
                DetalleFacturaCompra::create([
                    'fk_id_factura' => $factura->id_factura,
                    'fk_id_inventario' => $detalle['inventario_id'],
                    'cantidad_comprada' => $detalle['cantidad'],
                    'costo_unitario' => $detalle['precio_unitario'],
                ]);

                // Registrar movimiento de entrada en inventario
                MovimientoInventario::create([
                    'fk_id_inventario' => $detalle['inventario_id'],
                    'fk_id_factura' => $factura->id_factura,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad' => $detalle['cantidad'],
                    'observaciones' => "Compra de factura #{$factura->numero_factura}",
                    'registrado_por' => Auth::id(),
                    'fecha_movimiento' => now(),
                ]);

                // Actualizar stock actual de inventario
                $inventario = InventarioMaterial::find($detalle['inventario_id']);
                $inventario->increment('stock_actual', $detalle['cantidad']);
            }

            DB::commit();

            return redirect()->route('admin.facturas.show', $factura->id_factura)
                ->with('success', "✅ Factura #{$factura->numero_factura} registrada correctamente");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creando factura: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Error al registrar factura: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Ver detalle de factura
     * GET /admin/facturas/{id}
     */
    public function show(FacturaCompra $facturaCompra)
    {
        $facturaCompra->load('registradoPor', 'actualizadoPor', 'detalles.inventario');
        $factura = $facturaCompra;

        return view('admin.facturas.show', compact('factura'));
    }

    /**
     * Mostrar formulario de edición
     * GET /admin/facturas/{id}/edit
     */
    public function edit(FacturaCompra $facturaCompra)
    {
        $inventarios = InventarioMaterial::activos()->get();
        $estados = ['Recibida', 'Pendiente Recepción', 'Rechazada', 'Cancelada'];
        $factura = $facturaCompra;

        return view('admin.facturas.edit', compact('factura', 'inventarios', 'estados'));
    }

    /**
     * Actualizar factura
     * PUT /admin/facturas/{id}
     */
    public function update(Request $request, FacturaCompra $facturaCompra)
    {
        // Validar que no haya salido del estado inicial
        if ($facturaCompra->estado === 'Cancelada') {
            return redirect()->back()->withErrors(['error' => 'No se puede editar una factura cancelada']);
        }

        $validated = $request->validate([
            'nombre_proveedor' => 'required|string|max:150',
            'fecha_compra' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'estado' => 'required|in:Recibida,Pendiente Recepción,Cancelada',
            'archivo_factura' => 'nullable|file|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Procesar archivo nuevo
            if ($request->hasFile('archivo_factura')) {
                // Eliminar anterior
                if ($facturaCompra->archivo_factura && Storage::disk('public')->exists('facturas/' . $facturaCompra->archivo_factura)) {
                    Storage::disk('public')->delete('facturas/' . $facturaCompra->archivo_factura);
                }

                $rutaArchivo = 'factura_' . time() . '_' . $request->file('archivo_factura')->getClientOriginalName();
                $request->file('archivo_factura')->storeAs('facturas', $rutaArchivo, 'public');
                $validated['archivo_factura'] = $rutaArchivo;
            }

            $validated['actualizado_por'] = Auth::id();
            $facturaCompra->update($validated);

            DB::commit();

            return redirect()->route('admin.facturas.show', $facturaCompra->id_factura)
                ->with('success', "✅ Factura actualizada correctamente");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar factura: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Eliminar factura
     * DELETE /admin/facturas/{id}
     */
    public function destroy(FacturaCompra $facturaCompra)
    {
        DB::beginTransaction();

        try {
            // Reversar movimientos de inventario
            $detalles = $facturaCompra->detalles()->get();

            foreach ($detalles as $detalle) {
                // Restar cantidad del inventario
                $inventario = $detalle->inventario;
                $inventario->decrement('stock_actual', $detalle->cantidad_comprada);

                // Crear movimiento inverso (SALIDA)
                MovimientoInventario::create([
                    'fk_id_inventario' => $detalle->fk_id_inventario,
                    'fk_id_factura' => $facturaCompra->id_factura,
                    'tipo_movimiento' => 'SALIDA',
                    'cantidad' => $detalle->cantidad_comprada,
                    'observaciones' => "Reversa de factura #{$facturaCompra->numero_factura} (eliminada)",
                    'registrado_por' => Auth::id(),
                    'fecha_movimiento' => now(),
                ]);

                // Eliminar detalle
                $detalle->delete();
            }

            // Eliminar archivo
            if ($facturaCompra->archivo_factura && Storage::disk('public')->exists('facturas/' . $facturaCompra->archivo_factura)) {
                Storage::disk('public')->delete('facturas/' . $facturaCompra->archivo_factura);
            }

            // Eliminar factura
            $nroFactura = $facturaCompra->numero_factura;
            $facturaCompra->delete();

            DB::commit();

            return redirect()->route('admin.facturas.index')
                ->with('success', "✅ Factura #{$nroFactura} eliminada y movimientos reversados");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar factura: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Obtener inventarios disponibles (AJAX)
     * GET /api/inventarios
     */
    public function apiInventarios()
    {
        $inventarios = InventarioMaterial::with('apoyo')
            ->select('id_inventario', 'fk_id_apoyo', 'stock_actual')
            ->get()
            ->map(function($inv) {
                return [
                    'id' => $inv->id_inventario,
                    'nombre' => "Inventario #{$inv->id_inventario} (Apoyo #{$inv->fk_id_apoyo})",
                    'precio_unitario' => 0,
                    'cantidad' => $inv->stock_actual,
                ];
            });

        return response()->json(['data' => $inventarios]);
    }

    /**
     * API: Validar que se puede recibir cantidad
     * POST /api/facturas/validar-stock
     */
    public function apiValidarStock(Request $request)
    {
        $validated = $request->validate([
            'inventario_id' => 'required|exists:inventario_material,id_inventario',
            'cantidad' => 'required|numeric|min:0.01',
        ]);

        $inventario = InventarioMaterial::find($validated['inventario_id']);

        return response()->json([
            'valido' => true,
            'nombre' => $inventario->nombre_material,
            'cantidad_actual' => $inventario->cantidad_actual,
            'cantidad_solicitada' => $validated['cantidad'],
        ]);
    }
}
