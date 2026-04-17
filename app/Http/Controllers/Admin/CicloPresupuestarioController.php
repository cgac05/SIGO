<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CicloPresupuestarioController
 * 
 * CRUD para gestión de ciclos presupuestarios (años fiscales)
 * Accesible para Directivos (rol 2) y Financieros (rol 3)
 */
class CicloPresupuestarioController extends Controller
{
    private function authorizeDirectivo(): void
    {
        $user = Auth::user();
        
        if (!$user || !$user->isPersonal()) {
            abort(403, 'Solo administradores y directivos pueden gestionar ciclos presupuestarios');
        }
        
        if (!$user->relationLoaded('personal')) {
            $user->load('personal');
        }
        
        $personal = $user->personal;
        $userRole = $personal ? (int) $personal->fk_rol : null;
        
        // Permitir rol 2 (admin/directivo) y rol 3 (financieros)
        if (!$personal || ($userRole !== 2 && $userRole !== 3)) {
            abort(403, "Solo administradores (rol 2) y financieros (rol 3) pueden gestionar ciclos presupuestarios. Tu rol: {$userRole}");
        }
    }

    /**
     * Listar todos los ciclos presupuestarios
     * GET /admin/ciclos
     */
    public function index()
    {
        $this->authorizeDirectivo();

        $ciclos = CicloPresupuestario::orderByDesc('ano_fiscal')->get();

        return view('admin.ciclos.index', [
            'ciclos' => $ciclos,
        ]);
    }

    /**
     * Formulario para crear nuevo ciclo presupuestario
     * GET /admin/ciclos/crear
     */
    public function create()
    {
        $this->authorizeDirectivo();

        // Obtener año actual como sugerencia
        $ultimoCiclo = CicloPresupuestario::orderByDesc('ano_fiscal')->first();
        $proximoAño = $ultimoCiclo ? $ultimoCiclo->ano_fiscal + 1 : now()->year;

        return view('admin.ciclos.create', [
            'proximoAño' => $proximoAño,
        ]);
    }

    /**
     * Guardar nuevo ciclo presupuestario
     * POST /admin/ciclos
     */
    public function store(Request $request)
    {
        $this->authorizeDirectivo();

        try {
            $validated = $request->validate([
                'ano_fiscal' => 'required|integer|min:2020|max:2099|unique:ciclos_presupuestarios,ano_fiscal',
                'presupuesto_total' => 'required|numeric|min:0.01',
                'fecha_inicio' => 'required|date',
                'fecha_cierre' => 'nullable|date|after_or_equal:fecha_inicio',
            ], [
                'ano_fiscal.unique' => 'Ya existe un ciclo para el año ' . $request->ano_fiscal,
                'ano_fiscal.required' => 'El año fiscal es obligatorio',
                'ano_fiscal.integer' => 'El año fiscal debe ser un número entero',
                'ano_fiscal.min' => 'El año fiscal debe ser mayor a 2020',
                'ano_fiscal.max' => 'El año fiscal debe ser menor a 2099',
                'presupuesto_total.required' => 'El presupuesto total es obligatorio',
                'presupuesto_total.numeric' => 'El presupuesto total debe ser un número',
                'presupuesto_total.min' => 'El presupuesto debe ser mayor a 0',
                'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
                'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida',
                'fecha_cierre.date' => 'La fecha de cierre debe ser una fecha válida',
                'fecha_cierre.after_or_equal' => 'La fecha de cierre debe ser igual o posterior a la fecha de inicio',
            ]);

            DB::beginTransaction();

            $ciclo = CicloPresupuestario::create([
                'ano_fiscal' => $validated['ano_fiscal'],
                'estado' => 'ABIERTO',
                'presupuesto_total_inicial' => $validated['presupuesto_total'],
                'presupuesto_total_aprobado' => $validated['presupuesto_total'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_cierre' => $validated['fecha_cierre'],
                'creada_por' => Auth::id(),
                'cantidad_solicitudes_totales' => 0,
                'cantidad_solicitudes_aprobadas' => 0,
                'cantidad_beneficiarios_atendidos' => 0,
            ]);

            DB::commit();

            // Si es AJAX, retornar JSON
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => "Ciclo presupuestario {$ciclo->ano_fiscal} creado exitosamente. Ahora puedes agregar categorías.",
                    'ciclo_id' => $ciclo->id_ciclo,
                ]);
            }

            return redirect()
                ->route('admin.ciclos.show', $ciclo->id_ciclo)
                ->with('success', "✅ Ciclo presupuestario {$ciclo->ano_fiscal} creado exitosamente. Ahora puedes agregar categorías.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si es AJAX, retornar JSON con errores
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withInput()->withErrors($e->errors());

        } catch (\Exception $e) {
            DB::rollBack();
            
            $errorMessage = 'Error al crear ciclo: ' . $e->getMessage();
            
            // Si es AJAX, retornar JSON con error
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => []
                ], 500);
            }

            return back()->withInput()->with('error', $errorMessage);
        }
    }

    /**
     * Ver detalle del ciclo presupuestario
     * GET /admin/ciclos/{id}
     */
    public function show($id)
    {
        $this->authorizeDirectivo();

        $ciclo = CicloPresupuestario::with('categorias')->findOrFail($id);

        // Calcular ejecución
        $totalPresupuestoAsignado = $ciclo->categorias->sum('presupuesto_anual');
        $totalDisponible = $ciclo->categorias->sum('disponible');
        $totalUtilizado = $totalPresupuestoAsignado - $totalDisponible;
        $porcentajeEjecucion = $totalPresupuestoAsignado > 0 
            ? round(($totalUtilizado / $totalPresupuestoAsignado) * 100, 1)
            : 0;

        return view('admin.ciclos.show', [
            'ciclo' => $ciclo,
            'categorias' => $ciclo->categorias,
            'totalPresupuestoAsignado' => $totalPresupuestoAsignado,
            'totalDisponible' => $totalDisponible,
            'totalUtilizado' => $totalUtilizado,
            'porcentajeEjecucion' => $porcentajeEjecucion,
        ]);
    }

    /**
     * Formulario para editar ciclo presupuestario
     * GET /admin/ciclos/{id}/editar
     */
    public function edit($id)
    {
        $this->authorizeDirectivo();

        $ciclo = CicloPresupuestario::findOrFail($id);

        return view('admin.ciclos.edit', [
            'ciclo' => $ciclo,
        ]);
    }

    /**
     * Actualizar ciclo presupuestario
     * PUT /admin/ciclos/{id}
     */
    public function update(Request $request, $id)
    {
        $this->authorizeDirectivo();

        $ciclo = CicloPresupuestario::findOrFail($id);

        $validated = $request->validate([
            'presupuesto_total' => 'required|numeric|min:0.01',
            'fecha_cierre' => 'nullable|date|after_or_equal:' . $ciclo->fecha_inicio->format('Y-m-d'),
        ]);

        try {
            DB::beginTransaction();

            $ciclo->update([
                'presupuesto_total_aprobado' => $validated['presupuesto_total'],
                'fecha_cierre' => $validated['fecha_cierre'],
            ]);

            DB::commit();

            return redirect()
                ->route('admin.ciclos.show', $ciclo->id)
                ->with('success', "✅ Ciclo presupuestario actualizado exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar ciclo: ' . $e->getMessage());
        }
    }

    /**
     * Cerrar ciclo presupuestario
     * PATCH /admin/ciclos/{id}/cerrar
     */
    public function cerrar($id)
    {
        $this->authorizeDirectivo();

        $ciclo = CicloPresupuestario::findOrFail($id);

        if ($ciclo->isCerrado()) {
            return back()->with('warning', 'El ciclo ya está cerrado.');
        }

        try {
            $ciclo->cerrar();

            return back()->with('success', "✅ Ciclo presupuestario {$ciclo->año_fiscal} cerrado exitosamente.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al cerrar ciclo: ' . $e->getMessage());
        }
    }

    /**
     * Reabrir ciclo presupuestario
     * PATCH /admin/ciclos/{id}/reabrir
     */
    public function reabrir($id)
    {
        $this->authorizeDirectivo();

        $ciclo = CicloPresupuestario::findOrFail($id);

        if ($ciclo->isAbierto()) {
            return back()->with('warning', 'El ciclo ya está abierto.');
        }

        try {
            $ciclo->reabrir();

            return back()->with('success', "✅ Ciclo presupuestario {$ciclo->año_fiscal} reabierto exitosamente.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al reabrir ciclo: ' . $e->getMessage());
        }
    }

    /**
     * Agregar nueva categoría presupuestaria al ciclo
     * POST /admin/ciclos/{id}/categorias
     */
    public function storeCategoria(Request $request, $id)
    {
        $this->authorizeDirectivo();

        $ciclo = CicloPresupuestario::findOrFail($id);

        if (!$ciclo->isAbierto()) {
            $message = 'No se pueden agregar categorías a un ciclo cerrado.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string|max:500',
                'presupuesto_anual' => 'required|numeric|min:0.01',
            ]);

            PresupuestoCategoria::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'presupuesto_anual' => $validated['presupuesto_anual'],
                'disponible' => $validated['presupuesto_anual'],
                'id_ciclo' => $id,
                'activo' => true,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "✅ Categoría '{$validated['nombre']}' agregada exitosamente."
                ]);
            }

            return back()->with('success', "✅ Categoría '{$validated['nombre']}' agregada exitosamente.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al agregar categoría: ' . $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Error al agregar categoría: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar categoría presupuestaria
     * PUT /admin/ciclos/categorias/{categoriaId}
     */
    public function updateCategoria(Request $request, $categoriaId)
    {
        $this->authorizeDirectivo();

        $categoria = PresupuestoCategoria::findOrFail($categoriaId);
        $ciclo = $categoria->ciclo;

        if (!$ciclo->isAbierto()) {
            $message = 'No se pueden editar categorías de un ciclo cerrado.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string|max:500',
                'presupuesto_anual' => 'required|numeric|min:0.01',
            ]);

            // Calcular diferencia de presupuesto
            $diferencia = $validated['presupuesto_anual'] - $categoria->presupuesto_anual;
            
            $categoria->update([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'presupuesto_anual' => $validated['presupuesto_anual'],
                'disponible' => $categoria->disponible + $diferencia,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Categoría actualizada exitosamente.'
                ]);
            }

            return back()->with('success', "✅ Categoría actualizada exitosamente.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar categoría: ' . $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Error al actualizar categoría: ' . $e->getMessage());
        }
    }

    /**
     * Desactivar categoría presupuestaria
     * DELETE /admin/ciclos/categorias/{categoriaId}
     */
    public function deleteCategoria(Request $request, $categoriaId)
    {
        $this->authorizeDirectivo();

        $categoria = PresupuestoCategoria::findOrFail($categoriaId);
        $ciclo = $categoria->ciclo;

        if (!$ciclo->isAbierto()) {
            $message = 'No se pueden eliminar categorías de un ciclo cerrado.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        try {
            $categoria->update(['activo' => false]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Categoría desactivada exitosamente.'
                ]);
            }

            return back()->with('success', "✅ Categoría desactivada exitosamente.");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al desactivar categoría: ' . $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Error al desactivar categoría: ' . $e->getMessage());
        }
    }
}
