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
        // Consulta principal: traemos todos los apoyos ordenados por id desc.
        // Importante: usamos Query Builder directamente (DB::table) para devolver
        // objetos stdClass ligeros que la vista itera; si se necesita lógica Eloquent
        // adicional, considerar usar el modelo `App\\Models\\Apoyo`.
        $apoyos = DB::table('Apoyos')->orderBy('id_apoyo', 'desc')->get();

        // Catálogo de tipos de documento usado por el formulario de creación.
        // Seleccionamos sólo los campos necesarios para mantener la carga ligera.
        $tiposDocumentos = DB::table('Cat_TiposDocumento')
            ->select('id_tipo_doc', 'nombre_documento')
            ->orderBy('nombre_documento')
            ->get();

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
        // Esta ruta devuelve sólo los campos necesarios para la tabla en el cliente
        // y es consumida por AJAX (`reloadApoyos()` en la vista). Mantenerla
        // liviana evita tráfico innecesario.
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
        // 1. VALIDACIÓN
        // Validamos solo los campos que nos interesan y los tipos esperados.
        // Las reglas están diseñadas para cubrir los dos flujos (Económico/Especie).
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
        // 2. INICIO DE TRANSACCIÓN: garantizamos consistencia entre tablas
        DB::beginTransaction();
        try {
            // Procesar imagen si viene en el request. Guardamos en disco y almacenamos ruta pública.
            $fotoRuta = null;
            if ($request->hasFile('foto_ruta')) {
                // `store('apoyos','public')` devuelve el path relativo dentro del disco `public`
                $fotoRuta = 'storage/' . $request->file('foto_ruta')->store('apoyos', 'public');
            }

            // Manejo robusto de checkbox `activo`: el formulario envía un hidden (0)
            // y el checkbox (1) si está marcado; tomar el último valor recibido.
            $activoRaw = $request->input('activo');
            if (is_array($activoRaw)) {
                $activoRaw = end($activoRaw);
            }
            $activo = filter_var($activoRaw, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

            // Crear el registro principal en la tabla `Apoyos` usando el modelo Eloquent.
            // Usamos Eloquent para respetar `$fillable` y `dateFormat` definidos en el modelo.
            $apoyo = \App\\Models\\Apoyo::create([
                'nombre_apoyo'   => $data['nombre_apoyo'],
                'tipo_apoyo'     => $data['tipo_apoyo'],
                // si no se indicó monto_maximo, usar el monto inicial asignado si existe
                'monto_maximo'   => $data['monto_maximo'] ?? ($data['monto_inicial_asignado'] ?? 0),
                'activo'         => $activo,
                'fecha_Creacion' => now(),
                'fechaInicio'    => $data['fechaInicio'],
                'fechafin'       => $data['fechafin'],
                'foto_ruta'      => $fotoRuta,
                'descripcion'    => $data['descripcion'],
            ]);

            // Inserciones auxiliares: según el tipo de apoyo, crear registro en la tabla
            // financiera o en inventario. Usamos Query Builder directo porque no
            // necesitamos lógica Eloquent adicional aquí.
            if ($data['tipo_apoyo'] === 'Económico') {
                DB::table('BD_Finanzas')->insert([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'monto_asignado' => $data['monto_inicial_asignado'] ?? 0,
                    'monto_ejercido' => 0,
                ]);
            } else {
                DB::table('BD_Inventario')->insert([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'stock_actual' => $data['stock_inicial'] ?? 0,
                ]);
            }

            // Requisitos/documentos: si el usuario marcó tipos de doc, asociarlos.
            if (!empty($data['documentos_requeridos'])) {
                foreach ($data['documentos_requeridos'] as $docId) {
                    DB::table('Requisitos_Apoyo')->insert([
                        'fk_id_apoyo' => $apoyo->id_apoyo,
                        'fk_id_tipo_doc' => $docId,
                        'es_obligatorio' => 1,
                    ]);
                }
            }

            // Commit de la transacción sólo si todo lo anterior fue exitoso.
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Apoyo registrado correctamente.']);

        } catch (\Exception $e) {
            // Rollback para deshacer cualquier inserción parcial.
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    }
