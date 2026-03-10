<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudController extends Controller
{
    public function guardar(Request $request)
    {
        $curpBeneficiario = 'PART000000XXXXXX00'; // Tu CURP de prueba temporal

        DB::beginTransaction();

        try {
            // 1. Guardar la Solicitud
            $folio = DB::table('Solicitudes')->insertGetId([
                'fk_curp' => $curpBeneficiario,
                'fk_id_apoyo' => $request->apoyo,
                'fecha_creacion' => now(),
                'estado' => 'Pendiente'
            ], 'folio');

            // 2. Buscamos en SQL qué documentos requiere el Apoyo que seleccionó el usuario
            $requisitos = DB::table('Requisitos_Apoyo')
                ->where('fk_id_apoyo', $request->apoyo)
                ->get();

            // 3. Iteramos de forma dinámica
            foreach ($requisitos as $req) {
                $nombreInput = 'documento_' . $req->fk_id_tipo_doc; // Ej. documento_1, documento_2

                // Si el usuario subió el archivo...
                if ($request->hasFile($nombreInput)) {
                    $rutaArchivo = $request->file($nombreInput)->store('solicitudes', 'public');

                    // Guardamos la referencia en SQL
                    DB::table('Documentos_Expediente')->insert([
                        'fk_folio' => $folio,
                        'fk_id_tipo_doc' => $req->fk_id_tipo_doc,
                        'ruta_archivo' => $rutaArchivo,
                        'estado_validacion' => 'Pendiente',
                        'version' => 1,
                        'fecha_carga' => now()
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('exito', true);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }
}