<?php

namespace App\Http\Controllers;

use App\Rules\Recaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudController extends Controller
{
    public function create(Request $request, int $id)
    {
        $user = $request->user()->loadMissing('beneficiario');
        $curpBeneficiario = $user->beneficiario?->curp;

        if (! $user->isBeneficiario() || ! $curpBeneficiario) {
            return redirect()->route('apoyos.index')->with('error', 'Debes iniciar sesión como beneficiario para registrar una solicitud.');
        }

        $apoyo = DB::table('Apoyos')
            ->where('id_apoyo', $id)
            ->first();

        if (! $apoyo) {
            return redirect()->route('apoyos.index')->with('error', 'El apoyo seleccionado no existe.');
        }

        $requisitos = DB::table('Requisitos_Apoyo')
            ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
            ->where('Requisitos_Apoyo.fk_id_apoyo', $id)
            ->select([
                'Requisitos_Apoyo.fk_id_tipo_doc',
                'Requisitos_Apoyo.es_obligatorio',
                'Cat_TiposDocumento.nombre_documento',
                'Cat_TiposDocumento.tipo_archivo_permitido',
                'Cat_TiposDocumento.validar_tipo_archivo',
            ])
            ->orderBy('Cat_TiposDocumento.nombre_documento')
            ->get();

        $solicitudActiva = DB::table('Solicitudes')
            ->join('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->where('Solicitudes.fk_curp', $curpBeneficiario)
            ->where('Solicitudes.fk_id_apoyo', $id)
            ->whereNotIn('Cat_EstadosSolicitud.nombre_estado', ['Rechazada'])
            ->orderByDesc('Solicitudes.fecha_creacion')
            ->select([
                'Solicitudes.folio',
                'Cat_EstadosSolicitud.nombre_estado as estado',
                'Solicitudes.fecha_creacion',
            ])
            ->first();

        return view('solicitudes.create', compact('apoyo', 'requisitos', 'solicitudActiva'));
    }

    public function guardar(Request $request)
    {
        $user = $request->user()->loadMissing('beneficiario');
        $curpBeneficiario = $user->beneficiario?->curp;
        $captchaRules = app()->environment('testing') ? ['nullable'] : ['required', new Recaptcha];

        if (! $user->isBeneficiario() || ! $curpBeneficiario) {
            return redirect()->back()->with('error', 'Debes iniciar sesion como beneficiario para registrar una solicitud.');
        }

        $request->validate([
            'apoyo' => ['required'],
            'g-recaptcha-response' => $captchaRules,
        ], [
            'g-recaptcha-response.required' => 'El token de seguridad es obligatorio.',
        ]);

        $solicitudActiva = DB::table('Solicitudes')
            ->join('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->where('Solicitudes.fk_curp', $curpBeneficiario)
            ->where('Solicitudes.fk_id_apoyo', $request->apoyo)
            ->whereNotIn('Cat_EstadosSolicitud.nombre_estado', ['Rechazada'])
            ->exists();

        if ($solicitudActiva) {
            return redirect()->back()->with('error', 'Ya tienes una solicitud en proceso para este apoyo.');
        }

        DB::beginTransaction();

        try {
            $folio = DB::table('Solicitudes')->insertGetId([
                'fk_curp' => $curpBeneficiario,
                'fk_id_apoyo' => $request->apoyo,
                'fk_id_estado' => 1,
                'fecha_creacion' => now(),
            ], 'folio');

            $requisitos = DB::table('Requisitos_Apoyo')
                ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                ->where('fk_id_apoyo', $request->apoyo)
                ->select([
                    'Requisitos_Apoyo.fk_id_tipo_doc',
                    'Requisitos_Apoyo.es_obligatorio',
                    'Cat_TiposDocumento.nombre_documento',
                    'Cat_TiposDocumento.tipo_archivo_permitido',
                    'Cat_TiposDocumento.validar_tipo_archivo',
                ])
                ->get();

            foreach ($requisitos as $req) {
                $nombreInput = 'documento_' . $req->fk_id_tipo_doc;

                $archivo = $request->file($nombreInput);

                if (! $archivo) {
                    if ((int) $req->es_obligatorio === 1) {
                        throw new \RuntimeException('Falta adjuntar el documento obligatorio: ' . $req->nombre_documento . '.');
                    }
                    continue;
                }

                $debeValidarTipo = ! isset($req->validar_tipo_archivo) || (bool) $req->validar_tipo_archivo;
                if ($debeValidarTipo) {
                    $tipo = $req->tipo_archivo_permitido ?? 'pdf';
                    $mimes = match ($tipo) {
                        'image' => 'jpg,jpeg,png,webp',
                        'word' => 'doc,docx',
                        'excel' => 'xls,xlsx,csv',
                        'zip' => 'zip,rar,7z',
                        'any' => null,
                        default => 'pdf',
                    };

                    if ($mimes) {
                        validator([
                            $nombreInput => $archivo,
                        ], [
                            $nombreInput => 'file|mimes:' . $mimes . '|max:10240',
                        ], [
                            $nombreInput . '.mimes' => 'El archivo para "' . $req->nombre_documento . '" no coincide con el tipo permitido (' . strtoupper($tipo) . ').',
                            $nombreInput . '.max' => 'El archivo para "' . $req->nombre_documento . '" excede el tamaño máximo permitido (10 MB).',
                        ])->validate();
                    }
                }

                $rutaArchivo = $archivo->store('solicitudes', 'public');

                DB::table('Documentos_Expediente')->insert([
                    'fk_folio' => $folio,
                    'fk_id_tipo_doc' => $req->fk_id_tipo_doc,
                    'ruta_archivo' => $rutaArchivo,
                    'estado_validacion' => 'Pendiente',
                    'version' => 1,
                    'fecha_carga' => now()
                ]);
            }

            DB::commit();

            return redirect()->back()->with('exito', true);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }
}