<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Controller para administrar los apoyos.
 *
 * Contiene métodos para listar los apoyos en HTML, devolver los apoyos en JSON
 * para recarga por AJAX y almacenar un nuevo apoyo junto con su registro
 * en `BD_Finanzas` o `BD_Inventario` según el tipo.
 *
 * Importante:
 * - Las operaciones de creación usan transacción para asegurar que
 *   si falla la inserción secundaria (finanzas/inventario) se haga rollback
 *   también del registro principal en `Apoyos`.
 */
class ApoyoController extends Controller
{
    /**
     * Mostrar la vista HTML con la tabla de Apoyos.
     *
     * Devuelve la vista `apoyos.index` con la colección de apoyos.
     */
    public function index()
    {
        $apoyos = DB::table('Apoyos')->orderBy('id_apoyo', 'desc')->get();
        // Cargar catálogos necesarios para el formulario
        $tiposDocumentos = DB::table('Cat_TiposDocumento')->select('id_tipo_doc', 'nombre_documento')->orderBy('nombre_documento')->get();

        return view('apoyos.index', compact('apoyos', 'tiposDocumentos'));
    }

    /**
     * Devuelve la lista de apoyos en JSON (para recarga por AJAX).
     *
     * Ruta: GET /apoyos/list
     * Respuesta: JSON array con objetos que contienen `id_apoyo`, `nombre_apoyo`,
     * `tipo_apoyo`, `monto_maximo` y `activo`.
     */
    public function list()
    {
        $apoyos = DB::table('Apoyos')
            ->select('id_apoyo', 'nombre_apoyo', 'tipo_apoyo', 'monto_maximo', 'activo')
            ->orderBy('id_apoyo', 'desc')
            ->get();

        return response()->json($apoyos);
    }

    /**
     * Almacenar nuevo apoyo y su registro en inventario/finanzas.
     *
     * Validaciones principales:
     * - `nombre_apoyo`: requerido, string, max 100
     * - `tipo_apoyo`: requerido, valores permitidos: 'Económico' o 'Especie'
     * - `monto_inicial_asignado`: requerido sólo si es 'Económico'
     * - `stock_inicial`: requerido sólo si es 'Especie'
     *
     * Flujo:
     * 1. Validar entrada.
     * 2. Ejecutar una transacción DB:
     *    - Insertar en `Apoyos` y obtener `id_apoyo`.
     *    - Insertar en `BD_Finanzas` o `BD_Inventario` según `tipo_apoyo`.
     * 3. Si todo OK, commit; si falla, rollback y devolver error.
     *
     * Respuesta:
     * - Si la petición es AJAX/JSON devuelve JSON con `success` y `message`.
     * - Si no, redirige a la lista con mensaje en sesión.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_apoyo' => 'required|string|max:100',
            'tipo_apoyo' => 'required|in:Económico,Especie',
            'monto_maximo' => 'nullable|numeric',
            'monto_inicial_asignado' => 'nullable|numeric',
            'stock_inicial' => 'nullable|integer',
            'activo' => 'nullable|boolean',
            'fechaInicio' => 'required|date',
            'fechafin' => 'required|date',
            'foto_ruta' => 'nullable|image|max:5120', // max 5MB
            'documentos_requeridos' => 'nullable|array',
            'documentos_requeridos.*' => 'integer|exists:Cat_TiposDocumento,id_tipo_doc',
        ]);

        // Ajustes según tipo: forzar campos requeridos según el tipo seleccionado
        if ($data['tipo_apoyo'] === 'Económico') {
            // Para apoyos económicos, requerimos monto inicial asignado
            $request->validate(['monto_inicial_asignado' => 'required|numeric']);
            // Si el usuario no indicó monto_maximo, lo inicializamos al monto asignado
            $data['monto_maximo'] = $data['monto_maximo'] ?? $data['monto_inicial_asignado'];
        } else {
            // Para apoyos en especie, requerimos stock inicial
            $request->validate(['stock_inicial' => 'required|integer']);
            $data['monto_maximo'] = $data['monto_maximo'] ?? 0;
        }

        // Leer el valor enviado por el formulario: true (1) o false (0)
        $activo = $request->boolean('activo');

        // Inicio de transacción: se asegura la atomicidad entre las tablas relacionadas
        DB::beginTransaction();
        try {
            // Procesar la imagen si se subió y generar ruta relativa
            $fotoRuta = null;
            if ($request->hasFile('foto_ruta')) {
                $file = $request->file('foto_ruta');
                $path = $file->store('apoyos', 'public'); // storage/app/public/apoyos/...
                $fotoRuta = 'storage/' . $path; // ruta relativa para usar desde public
            }

            // Preparar fechas como DATETIME
            $fechaInicio = Carbon::parse($data['fechaInicio'])->startOfDay();
            $fechaFin = Carbon::parse($data['fechafin'])->endOfDay();

            // Inserción principal en la tabla Apoyos. insertGetId devuelve el id_autoincremental
            $id = DB::table('Apoyos')->insertGetId([
                'nombre_apoyo' => $data['nombre_apoyo'],
                'tipo_apoyo' => $data['tipo_apoyo'],
                'monto_maximo' => $data['monto_maximo'],
                'activo' => $activo ? 1 : 0,
                'fecha_Creacion' => Carbon::now(),
                'fechaInicio' => $fechaInicio->toDateTimeString(),
                'fechafin' => $fechaFin->toDateTimeString(),
                'foto_ruta' => $fotoRuta,
            ], 'id_apoyo');

            // Inserción secundaria dependiendo del tipo: Finanzas o Inventario
            if ($data['tipo_apoyo'] === 'Económico') {
                DB::table('BD_Finanzas')->insert([
                    'fk_id_apoyo' => $id,
                    'monto_asignado' => $data['monto_inicial_asignado'],
                    'monto_ejercido' => 0,
                ]);
            } else {
                DB::table('BD_Inventario')->insert([
                    'fk_id_apoyo' => $id,
                    'stock_actual' => $data['stock_inicial'],
                ]);
            }

            // Insertar los requisitos seleccionados (si los hay)
            $documentos = $request->input('documentos_requeridos', []);
            if (is_array($documentos) && count($documentos) > 0) {
                foreach ($documentos as $docId) {
                    DB::table('Requisitos_Apoyo')->insert([
                        'fk_id_apoyo' => $id,
                        'fk_id_tipo_doc' => $docId,
                        'es_obligatorio' => 1,
                    ]);
                }
            }

            // Confirmar transacción
            DB::commit();

            // Si la petición espera JSON, devolver JSON útil con el registro insertado
            if ($request->wantsJson() || $request->ajax()) {
                $apoyo = DB::table('Apoyos')->where('id_apoyo', $id)->first();
                return response()->json(['success' => true, 'message' => 'Apoyo registrado correctamente.', 'apoyo' => $apoyo]);
            }

            // Si no es AJAX, redirigir a la lista con flash message
            return Redirect::route('apoyos.index')->with('success', 'Apoyo registrado correctamente.');
        } catch (\Exception $e) {
            // Si ocurre cualquier excepción, revertimos todos los cambios hechos en la transacción
            DB::rollBack();

            // Responder según el tipo de petición
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
            }

            return back()->withErrors(['msg' => 'Error al guardar: ' . $e->getMessage()])->withInput();
        }
    }
}
