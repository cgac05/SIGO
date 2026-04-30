<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RecursosFinancierosController extends Controller
{
    // Lista solicitudes firmadas por directivo (con CUV) pendientes de cierre financiero
    public function index()
{
    $user = auth()->user();

    if (!$user || !$user->isPersonal() || (int) optional($user->personal)->fk_rol !== 3) {
        abort(403, 'Acceso exclusivo para Recursos Financieros.');
    }

    $solicitudes = DB::table('Solicitudes')
        ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
        ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
        ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
        ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
        ->leftJoin('firmas_electronicas', function($join) {
            $join->on('Solicitudes.folio', '=', 'firmas_electronicas.folio_solicitud')
                 ->where('firmas_electronicas.tipo_firma', '=', 'Aprobación')
                 ->where('firmas_electronicas.estado', '=', 'activa');
        })
        ->leftJoin('BD_Inventario', 'Apoyos.id_apoyo', '=', 'BD_Inventario.fk_id_apoyo')
        ->select([
            'Solicitudes.folio',
            'Solicitudes.fk_curp',
            'Solicitudes.fk_id_apoyo',
            'Solicitudes.fk_id_estado',
            'Solicitudes.fecha_creacion',
            'Solicitudes.presupuesto_confirmado',
            'Solicitudes.monto_entregado',
            'Solicitudes.fecha_entrega_recurso',
            'Solicitudes.fecha_cierre_financiero',
            'Solicitudes.cuv',
            'Solicitudes.folio_institucional',
            'Apoyos.nombre_apoyo',
            'Apoyos.tipo_apoyo',
            'Apoyos.monto_maximo',
            'BD_Inventario.costo_unitario',
            'Beneficiarios.nombre',
            'Beneficiarios.apellido_paterno',
            'Beneficiarios.apellido_materno',
            'Beneficiarios.curp',
            'Beneficiarios.telefono',
            'Usuarios.email as correo',
            'Cat_EstadosSolicitud.nombre_estado as estado',
            'firmas_electronicas.fecha_firma as fecha_firma_directivo',
        ])
        ->whereNotNull('Solicitudes.cuv')
        ->whereNull('Solicitudes.fecha_cierre_financiero')
        ->orderByDesc('Solicitudes.folio')
        ->get();

    // Cargar documentos de forma segura
    $solicitudes = $solicitudes->map(function ($sol) {
        try {
            $sol->documentos = DB::table('Documentos_Expediente')
                ->join('Cat_TiposDocumento', 'Documentos_Expediente.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                ->where('Documentos_Expediente.fk_folio', $sol->folio)
                ->select([
                    'Documentos_Expediente.id_doc',
                    'Documentos_Expediente.estado_validacion',
                    'Documentos_Expediente.admin_status',
                    'Cat_TiposDocumento.nombre_documento',
                ])
                ->get();
        } catch (\Exception $e) {
            $sol->documentos = collect();
        }
        return $sol;
    });

    return view('finanzas.panel', compact('solicitudes'));
}

    // Procesa el cierre financiero de una solicitud
    public function cierreFinanciero(Request $request)
    {
        $data = $request->validate([
            'folio'              => 'required|integer|exists:Solicitudes,folio',
            'monto_entregado'    => 'required|numeric|min:0',
            'fecha_entrega'      => 'required|date',
            'folio_cheque'       => 'nullable|string|max:100',
            'observaciones'      => 'nullable|string|max:500',
        ], [
            'folio.required'           => 'El folio es obligatorio.',
            'monto_entregado.required' => 'El monto entregado es obligatorio.',
            'monto_entregado.numeric'  => 'El monto debe ser un número válido.',
            'fecha_entrega.required'   => 'La fecha de entrega es obligatoria.',
            'fecha_entrega.date'       => 'La fecha no tiene un formato válido.',
        ]);

        DB::beginTransaction();
        try {
            DB::table('Solicitudes')
                ->where('folio', $data['folio'])
                ->update([
                    'monto_entregado'        => $data['monto_entregado'],
                    'fecha_entrega_recurso'  => $data['fecha_entrega'],
                    'fecha_cierre_financiero'=> now(),
                    'folio_institucional'    => $data['folio_cheque'] ?? null,
                ]);
            // Enviar correo al beneficiario
            $solicitudInfo = DB::table('Solicitudes')
                ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
                ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
                ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
                ->where('Solicitudes.folio', $data['folio'])
                ->select('Usuarios.email', 'Beneficiarios.nombre', 'Apoyos.nombre_apoyo', 'Solicitudes.monto_entregado', 'Solicitudes.folio')
                ->first();

            if ($solicitudInfo && $solicitudInfo->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($solicitudInfo->email)->send(new \App\Mail\CierreFinancieroMail($solicitudInfo));
                } catch (\Exception $mailException) {
                    \Log::error('Error enviando correo de cierre financiero: ' . $mailException->getMessage());
                }
            }
            DB::commit();
            return redirect()->route('finanzas.panel')
                ->with('exito', 'Cierre financiero registrado correctamente para el folio #' . $data['folio'])
                ->with('comprobante_url', route('finanzas.comprobante', ['folio' => $data['folio']]));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar el cierre: ' . $e->getMessage());
        }
    }

    // Listado de solicitudes que ya fueron cerradas financieramente (Historial)
    public function historial()
    {
        $user = auth()->user();

        if (!$user || !$user->isPersonal() || (int) optional($user->personal)->fk_rol !== 3) {
            abort(403, 'Acceso exclusivo para Recursos Financieros.');
        }

        $solicitudes = DB::table('Solicitudes')
            ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
            ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
            ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->leftJoin('firmas_electronicas', function($join) {
                $join->on('Solicitudes.folio', '=', 'firmas_electronicas.folio_solicitud')
                     ->where('firmas_electronicas.tipo_firma', '=', 'Aprobación')
                     ->where('firmas_electronicas.estado', '=', 'activa');
            })
            ->leftJoin('BD_Inventario', 'Apoyos.id_apoyo', '=', 'BD_Inventario.fk_id_apoyo')
            ->select([
                'Solicitudes.folio',
                'Solicitudes.fk_curp',
                'Solicitudes.fk_id_apoyo',
                'Solicitudes.fk_id_estado',
                'Solicitudes.fecha_creacion',
                'Solicitudes.presupuesto_confirmado',
                'Solicitudes.monto_entregado',
                'Solicitudes.fecha_entrega_recurso',
                'Solicitudes.fecha_cierre_financiero',
                'Solicitudes.cuv',
                'Solicitudes.folio_institucional',
                'Apoyos.nombre_apoyo',
                'Apoyos.tipo_apoyo',
                'Apoyos.monto_maximo',
                'BD_Inventario.costo_unitario',
                'Beneficiarios.nombre',
                'Beneficiarios.apellido_paterno',
                'Beneficiarios.apellido_materno',
                'Beneficiarios.curp',
                'Beneficiarios.telefono',
                'Usuarios.email as correo',
                'Cat_EstadosSolicitud.nombre_estado as estado',
                'firmas_electronicas.fecha_firma as fecha_firma_directivo',
            ])
            ->whereNotNull('Solicitudes.fecha_cierre_financiero')
            ->orderByDesc('Solicitudes.fecha_cierre_financiero')
            ->get();

        return view('finanzas.historial', compact('solicitudes'));
    }

    // Genera el PDF del comprobante de pago o salida de inventario
    public function comprobante($folio)
    {
        $user = auth()->user();

        if (!$user || !$user->isPersonal() || (int) optional($user->personal)->fk_rol !== 3) {
            abort(403, 'Acceso exclusivo para Recursos Financieros.');
        }

        $solicitud = DB::table('Solicitudes')
            ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
            ->leftJoin('firmas_electronicas', function($join) {
                $join->on('Solicitudes.folio', '=', 'firmas_electronicas.folio_solicitud')
                     ->where('firmas_electronicas.tipo_firma', '=', 'Aprobación')
                     ->where('firmas_electronicas.estado', '=', 'activa');
            })
            ->select([
                'Solicitudes.*',
                'Apoyos.nombre_apoyo',
                'Apoyos.tipo_apoyo',
                'Apoyos.monto_maximo',
                'Beneficiarios.nombre',
                'Beneficiarios.apellido_paterno',
                'Beneficiarios.apellido_materno',
                'Beneficiarios.curp',
                'Beneficiarios.telefono',
                'firmas_electronicas.fecha_firma as fecha_firma_directivo',
                'firmas_electronicas.sello_digital'
            ])
            ->where('Solicitudes.folio', $folio)
            ->first();

        if (!$solicitud) {
            abort(404, 'Solicitud no encontrada.');
        }

        // Obtener el personal que generó el comprobante (usuario actual)
        $operadorNombre = $user->nombre;

        $pdf = Pdf::loadView('finanzas.comprobante', compact('solicitud', 'operadorNombre'));
        
        // Retornar el PDF pre-generado (stream o download, 'stream' abre en el browser)
        return $pdf->stream('Comprobante_' . $folio . '.pdf');
    }
}