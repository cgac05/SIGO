<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Beneficiario;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PadronController extends Controller
{
    /**
     * Mostrar padrón de usuarios (beneficiarios + personal)
     */
    public function index(Request $request)
    {
        // Obtener filtros
        $tipo = $request->get('tipo', 'todos');  // todos, beneficiarios, personal
        $rol = $request->get('rol', '');  // Para personal
        $estado = $request->get('estado', 'activo');  // activo, inactivo
        $busqueda = $request->get('busqueda', '');
        $ordenar = $request->get('ordenar', 'nombre');

        // Inicializar query base
        $query = User::query();

        // FILTRO POR TIPO
        if ($tipo === 'beneficiarios') {
            $query = $this->queryBeneficiarios($query, $busqueda, $ordenar, $estado);
        } elseif ($tipo === 'personal') {
            $query = $this->queryPersonal($query, $busqueda, $ordenar, $estado, $rol);
        } else {
            // TODOS: Combinar beneficiarios y personal
            $query = $this->queryTodos($query, $busqueda, $ordenar, $estado, $rol);
        }

        // Paginar
        $usuarios = $query->paginate(20);

        // Estadísticas
        $estadisticas = $this->obtenerEstadisticas();

        // Roles disponibles para filtro
        $roles = $this->obtenerRolesDisponibles();

        return view('admin.padron.index', compact(
            'usuarios',
            'estadisticas',
            'roles',
            'tipo',
            'rol',
            'estado',
            'busqueda',
            'ordenar'
        ));
    }

    /**
     * Query para beneficiarios
     */
    private function queryBeneficiarios($query, $busqueda, $ordenar, $estado)
    {
        return $query
            ->whereHas('beneficiario')
            ->where('tipo_usuario', 'Beneficiario')
            ->where('activo', $estado === 'activo' ? 1 : 0)
            ->when($busqueda, function ($q) use ($busqueda) {
                return $q->where(function ($subq) use ($busqueda) {
                    $subq->whereHas('beneficiario', function ($subsubq) use ($busqueda) {
                        $subsubq->where('nombre', 'LIKE', "%$busqueda%")
                               ->orWhere('apellido_paterno', 'LIKE', "%$busqueda%")
                               ->orWhere('apellido_materno', 'LIKE', "%$busqueda%")
                               ->orWhere('curp', 'LIKE', "%$busqueda%");
                    })
                    ->orWhere('email', 'LIKE', "%$busqueda%");
                });
            })
            ->orderBy($this->mapearOrdenamiento($ordenar, 'beneficiario'));
    }

    /**
     * Query para personal administrativo y directivos
     */
    private function queryPersonal($query, $busqueda, $ordenar, $estado, $rol)
    {
        return $query
            ->whereHas('personal')
            ->whereIn('tipo_usuario', ['personal', 'administrativo', 'directivo'])
            ->where('activo', $estado === 'activo' ? 1 : 0)
            ->when($rol, function ($q) use ($rol) {
                return $q->whereHas('personal', function ($subq) use ($rol) {
                    $subq->where('fk_rol', (int)$rol);
                });
            })
            ->when($busqueda, function ($q) use ($busqueda) {
                return $q->where(function ($subq) use ($busqueda) {
                    $subq->whereHas('personal', function ($subsubq) use ($busqueda) {
                        $subsubq->where('nombre', 'LIKE', "%$busqueda%")
                               ->orWhere('apellido_paterno', 'LIKE', "%$busqueda%")
                               ->orWhere('apellido_materno', 'LIKE', "%$busqueda%")
                               ->orWhere('numero_empleado', 'LIKE', "%$busqueda%");
                    })
                    ->orWhere('email', 'LIKE', "%$busqueda%");
                });
            })
            ->orderBy($this->mapearOrdenamiento($ordenar, 'personal'));
    }

    /**
     * Query para todos (beneficiarios + personal)
     */
    private function queryTodos($query, $busqueda, $ordenar, $estado, $rol)
    {
        return $query
            ->where('activo', $estado === 'activo' ? 1 : 0)
            ->where(function ($subq) {
                $subq->whereHas('beneficiario')
                    ->orWhereHas('personal');
            })
            ->when($busqueda, function ($q) use ($busqueda) {
                return $q->where(function ($subq) use ($busqueda) {
                    // Buscar en beneficiarios
                    $subq->whereHas('beneficiario', function ($subsubq) use ($busqueda) {
                        $subsubq->where('nombre', 'LIKE', "%$busqueda%")
                               ->orWhere('apellido_paterno', 'LIKE', "%$busqueda%")
                               ->orWhere('curp', 'LIKE', "%$busqueda%");
                    })
                    // O en personal
                    ->orWhereHas('personal', function ($subsubq) use ($busqueda) {
                        $subsubq->where('nombre', 'LIKE', "%$busqueda%")
                               ->orWhere('numero_empleado', 'LIKE', "%$busqueda%");
                    })
                    // O en email directo
                    ->orWhere('email', 'LIKE', "%$busqueda%");
                });
            })
            ->orderBy($this->mapearOrdenamiento($ordenar, null));
    }

    /**
     * Mapear campo de ordenamiento (solo columnas que existen en Usuarios)
     */
    private function mapearOrdenamiento($ordenar, $tipo = null)
    {
        $mapeo = [
            'nombre' => 'email',  // Fallback a email ya que nombre está en relacionadas
            'email' => 'email',
            'creacion' => 'fecha_creacion',
            'acceso' => 'ultima_conexion',
        ];

        return $mapeo[$ordenar] ?? 'email';
    }

    /**
     * Obtener estadísticas de usuarios
     */
    private function obtenerEstadisticas()
    {
        $totalBeneficiarios = Beneficiario::count();
        $totalPersonal = Personal::count();
        $totalActivos = User::where('activo', 1)->count();
        $totalInactivos = User::where('activo', 0)->count();

        // Desglose de personal por rol
        $desglocePersonal = DB::table('Personal')
            ->select('fk_rol', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('fk_rol')
            ->get();

        return [
            'total_beneficiarios' => $totalBeneficiarios,
            'total_personal' => $totalPersonal,
            'total_activos' => $totalActivos,
            'total_inactivos' => $totalInactivos,
            'total_general' => $totalBeneficiarios + $totalPersonal,
            'desglose_personal' => $desglocePersonal,
        ];
    }

    /**
     * Obtener roles disponibles para filtro
     */
    private function obtenerRolesDisponibles()
    {
        return DB::table('Cat_Roles')->get();
    }

    /**
     * Ver detalles de un usuario
     */
    public function show($id)
    {
        $usuario = User::findOrFail($id);

        $datos = [
            'usuario' => $usuario,
            'tipo' => $usuario->tipo_usuario,
        ];

        if ($usuario->isBeneficiario()) {
            $datos['beneficiario'] = $usuario->beneficiario;
        } elseif ($usuario->isPersonal()) {
            $datos['personal'] = $usuario->personal;
        }

        return view('admin.padron.show', $datos);
    }

    /**
     * Exportar padrón a CSV
     */
    public function exportar(Request $request)
    {
        $tipo = $request->get('tipo', 'todos');
        $estado = $request->get('estado', 'activo');
        $busqueda = $request->get('busqueda', '');

        // Construir query
        $query = User::query();

        if ($tipo === 'beneficiarios') {
            $query = $this->queryBeneficiarios($query, $busqueda, 'nombre', $estado);
        } elseif ($tipo === 'personal') {
            $query = $this->queryPersonal($query, $busqueda, 'nombre', $estado, '');
        }

        $usuarios = $query->get();

        // Crear CSV
        $filename = "padron_usuarios_" . now()->format('Ymd_His') . ".csv";

        return response()->stream(function () use ($usuarios, $tipo) {
            $output = fopen('php://output', 'w');

            // Headers
            if ($tipo === 'beneficiarios') {
                fputcsv($output, ['CURP', 'Nombre', 'Apellido Paterno', 'Apellido Materno', 'Email', 'Teléfono', 'Estado', 'Fecha Registro']);
            } else {
                fputcsv($output, ['Nº Empleado', 'Nombre', 'Apellido Paterno', 'Apellido Materno', 'Email', 'Rol', 'Estado', 'Fecha Registro']);
            }

            // Datos
            foreach ($usuarios as $usuario) {
                if ($tipo === 'beneficiarios') {
                    $beneficiario = $usuario->beneficiario;
                    fputcsv($output, [
                        $beneficiario->curp,
                        $beneficiario->nombre,
                        $beneficiario->apellido_paterno,
                        $beneficiario->apellido_materno,
                        $usuario->email,
                        $beneficiario->telefono,
                        $usuario->activo ? 'Activo' : 'Inactivo',
                        $usuario->fecha_creacion,
                    ]);
                } else {
                    $personal = $usuario->personal;
                    fputcsv($output, [
                        $personal->numero_empleado,
                        $personal->nombre,
                        $personal->apellido_paterno,
                        $personal->apellido_materno,
                        $usuario->email,
                        $personal->fk_rol,
                        $usuario->activo ? 'Activo' : 'Inactivo',
                        $usuario->fecha_creacion,
                    ]);
                }
            }

            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}
