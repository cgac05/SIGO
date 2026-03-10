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
    // 1. VALIDACIÓN (Ya no tiene el [...])
    $data = $request->validate([
        'nombre_apoyo' => 'required|string|max:100',
        'tipo_apoyo' => 'required|in:Económico,Especie',
        'monto_maximo' => 'nullable|numeric',
            'descripcion' => 'required|string',
        'monto_inicial_asignado' => 'nullable|numeric',
        'stock_inicial' => 'nullable|integer',
        'activo' => 'nullable|boolean',
        'fechaInicio' => 'required|date',
        'fechafin' => 'required|date',
        'foto_ruta' => 'nullable|image|max:5120', 
        'documentos_requeridos' => 'nullable|array',
        'documentos_requeridos.*' => 'integer|exists:Cat_TiposDocumento,id_tipo_doc',
    ]);

    DB::beginTransaction();
    try {
        // Procesar imagen
        $fotoRuta = null;
        if ($request->hasFile('foto_ruta')) {
            $fotoRuta = 'storage/' . $request->file('foto_ruta')->store('apoyos', 'public');
        }

        // 2. USO DEL MODELO (Para que funcione el $dateFormat y no truene la fecha)
        // Determinar valor de 'activo' de forma robusta cuando el formulario
        // envía tanto el hidden (0) como el checkbox (1) o sólo uno de ellos.
        $activoRaw = $request->input('activo');
        if (is_array($activoRaw)) {
            $activoRaw = end($activoRaw); // tomar el último valor enviado (checkbox sobrescribe hidden)
        }
        $activo = filter_var($activoRaw, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $apoyo = \App\Models\Apoyo::create([
            'nombre_apoyo'   => $data['nombre_apoyo'],
            'tipo_apoyo'     => $data['tipo_apoyo'],
            'monto_maximo'   => $data['monto_maximo'] ?? ($data['monto_inicial_asignado'] ?? 0),
            'activo'         => $activo,
            'fecha_Creacion' => now(),
            'fechaInicio'    => $data['fechaInicio'],
            'fechafin'       => $data['fechafin'],
            'foto_ruta'      => $fotoRuta,
            'descripcion'    => $data['descripcion'],
        ]);

        // 3. RELACIONES SECUNDARIAS
        if ($data['tipo_apoyo'] === 'Económico') {
            DB::table('BD_Finanzas')->insert([
                'fk_id_apoyo' => $apoyo->id_apoyo,
                'monto_asignado' => $data['monto_inicial_asignado'],
                'monto_ejercido' => 0,
            ]);
        } else {
            DB::table('BD_Inventario')->insert([
                'fk_id_apoyo' => $apoyo->id_apoyo,
                'stock_actual' => $data['stock_inicial'],
            ]);
        }

        // Documentos
        if (!empty($data['documentos_requeridos'])) {
            foreach ($data['documentos_requeridos'] as $docId) {
                DB::table('Requisitos_Apoyo')->insert([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'fk_id_tipo_doc' => $docId,
                    'es_obligatorio' => 1,
                ]);
            }
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Apoyo registrado correctamente.']);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
    }
}
    }
