<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudController extends Controller
{
    public function guardar(Request $request)
    {
        // TRUCO TEMPORAL: Usamos el CURP que creamos en SQL Server.
        // Mañana que sirva el login, cambiarás esto por: $curpBeneficiario = auth()->user()->curp;
        $curpBeneficiario = 'PART000000XXXXXX00'; 

        // Iniciamos una Transacción para proteger la base de datos
        DB::beginTransaction();

        try {
            // 1. Guardar la Solicitud y obtener el Folio generado
            $folio = DB::table('Solicitudes')->insertGetId([
                'fk_curp' => $curpBeneficiario,
                'fk_id_apoyo' => $request->apoyo,
                'fecha_creacion' => now(),
                'estado' => 'Pendiente'
            ], 'folio');

            // 2. Mapear los inputs del HTML con el ID del Catálogo de Documentos
            $documentos = [
                ['input' => 'doc_acta', 'tipo_id' => 1],
                ['input' => 'doc_ine', 'tipo_id' => 2],
                ['input' => 'doc_curp', 'tipo_id' => 3],
                ['input' => 'doc_domicilio', 'tipo_id' => 4],
                ['input' => 'doc_estudios', 'tipo_id' => 5],
                ['input' => 'doc_historial', 'tipo_id' => 6],
                ['input' => 'doc_foto', 'tipo_id' => 7],
            ];

            // 3. Guardar los archivos físicos y hacer los Inserts
            foreach ($documentos as $doc) {
                if ($request->hasFile($doc['input'])) {
                    // Guarda el archivo en storage/app/public/solicitudes
                    $rutaArchivo = $request->file($doc['input'])->store('solicitudes', 'public');

                    DB::table('Documentos_Expediente')->insert([
                        'fk_folio' => $folio,
                        'fk_id_tipo_doc' => $doc['tipo_id'],
                        'ruta_archivo' => $rutaArchivo,
                        'estado_validacion' => 'Pendiente',
                        'version' => 1,
                        'fecha_carga' => now()
                    ]);
                }
            }

            DB::commit(); // Todo chido, confirmamos los cambios en SQL

            // Regresamos a la pantalla activando la variable de sesión 'exito'
            return redirect()->back()->with('exito', true);

        } catch (\Exception $e) {
            DB::rollBack(); // Hubo error, cancelamos todo
            return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }
}