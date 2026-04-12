<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DirectivoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isPersonal() || (int) optional($user->personal)->fk_rol !== 2) {
            abort(403, 'Acceso exclusivo para directivos.');
        }

        // Traer solicitudes con estado Aprobada o id_estado = 10
        $solicitudes = DB::table('Solicitudes')
            ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
            ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
            ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->select([
                'Solicitudes.folio',
                'Solicitudes.fk_curp',
                'Solicitudes.fk_id_apoyo',
                'Solicitudes.fk_id_estado',
                'Solicitudes.fecha_creacion',
                'Solicitudes.presupuesto_confirmado',
                'Solicitudes.monto_entregado',
                'Solicitudes.cuv',
                'Solicitudes.folio_institucional',
                'Apoyos.nombre_apoyo',
                'Apoyos.tipo_apoyo',
                'Apoyos.monto_maximo',
                'Beneficiarios.nombre',
                'Beneficiarios.apellido_paterno',
                'Beneficiarios.apellido_materno',
                'Beneficiarios.curp',
                'Beneficiarios.telefono',
                'Usuarios.email as correo',
                'Cat_EstadosSolicitud.nombre_estado as estado',
            ])
            ->where('Solicitudes.fk_id_estado', 10)
            ->whereNull('Solicitudes.cuv')
            ->orderByDesc('Solicitudes.folio')
            ->get();

        $solicitudes = $solicitudes->map(function ($sol) {
            $sol->documentos = DB::table('Documentos_Expediente')
                ->join('Cat_TiposDocumento', 'Documentos_Expediente.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                ->where('Documentos_Expediente.fk_folio', $sol->folio)
                ->select([
                    'Documentos_Expediente.id_doc',
                    'Documentos_Expediente.ruta_archivo',
                    'Documentos_Expediente.estado_validacion',
                    'Documentos_Expediente.admin_status',
                    'Documentos_Expediente.observaciones_revision',
                    'Cat_TiposDocumento.nombre_documento',
                ])
                ->get();
            return $sol;
        });

        return view('directivo.panel', compact('solicitudes'));
    }
}