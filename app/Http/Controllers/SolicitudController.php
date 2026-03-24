<?php

namespace App\Http\Controllers;

use App\Rules\Recaptcha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SolicitudController extends Controller
{
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

        $apoyo = DB::table('Apoyos')->where('id_apoyo', $request->apoyo)->first();
        if (! $apoyo || (int) ($apoyo->activo ?? 0) !== 1) {
            return redirect()->back()->with('error', 'El apoyo seleccionado no se encuentra disponible.');
        }

        $hoy = now();
        if (! empty($apoyo->fecha_inicio) && $hoy->lt(Carbon::parse($apoyo->fecha_inicio))) {
            return redirect()->back()->with('error', 'El periodo de recepcion aun no inicia para este apoyo.');
        }

        if (! empty($apoyo->fecha_fin) && $hoy->gt(Carbon::parse($apoyo->fecha_fin))) {
            return redirect()->back()->with('error', 'El periodo de recepcion ya finalizo para este apoyo.');
        }

        if (Schema::hasTable('Hitos_Apoyo')) {
            $hitos = DB::table('Hitos_Apoyo')
                ->where('fk_id_apoyo', $request->apoyo)
                ->where('activo', 1)
                ->orderBy('orden_hito')
                ->get();

            if ($hitos->isNotEmpty()) {
                $hitoActual = $hitos->first();
                foreach ($hitos as $hito) {
                    $inicio = $hito->fecha_inicio ? Carbon::parse($hito->fecha_inicio) : null;
                    $fin = $hito->fecha_fin ? Carbon::parse($hito->fecha_fin) : null;
                    if ($inicio && $fin && $hoy->betweenIncluded($inicio, $fin)) {
                        $hitoActual = $hito;
                        break;
                    }
                }

                if (strtoupper((string) $hitoActual->clave_hito) !== 'RECEPCION') {
                    return redirect()->back()->with('error', 'No se pueden registrar solicitudes fuera del hito de recepcion.');
                }
            }
        }

        DB::beginTransaction();

        try {
            $payloadSolicitud = [
                'fk_curp' => $curpBeneficiario,
                'fk_id_apoyo' => $request->apoyo,
                'fk_id_estado' => 1,
                'fecha_creacion' => now(),
            ];

            if (Schema::hasColumn('Solicitudes', 'permite_correcciones')) {
                $payloadSolicitud['permite_correcciones'] = 1;
            }

            $folio = DB::table('Solicitudes')->insertGetId($payloadSolicitud, 'folio');

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

            if (Schema::hasTable('Seguimiento_Solicitud')) {
                DB::table('Seguimiento_Solicitud')->insert([
                    'fk_folio' => $folio,
                    'estado_proceso' => 'EN_PROCESO',
                    'fecha_creacion' => now(),
                    'fecha_actualizacion' => now(),
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