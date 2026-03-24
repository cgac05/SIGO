<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolicitudWorkflowService
{
    public const HITOS_BASE = [
        'PUBLICACION',
        'RECEPCION',
        'ANALISIS_ADMIN',
        'RESULTADOS',
        'CIERRE',
    ];

    public function getTimelineByFolio(int $folio): array
    {
        $solicitud = DB::table('Solicitudes')->where('folio', $folio)->first();
        if (! $solicitud) {
            throw ValidationException::withMessages([
                'folio' => 'No existe la solicitud indicada.',
            ]);
        }

        $hitos = DB::table('Hitos_Apoyo')
            ->where('fk_id_apoyo', $solicitud->fk_id_apoyo)
            ->where('activo', 1)
            ->orderBy('orden_hito')
            ->get();

        if ($hitos->isEmpty()) {
            throw ValidationException::withMessages([
                'hitos' => 'No hay hitos configurados para este apoyo.',
            ]);
        }

        $current = $this->resolveCurrentHito($hitos->all());

        $timeline = [];
        foreach ($hitos as $hito) {
            $status = 'future';
            if ($hito->orden_hito < $current->orden_hito) {
                $status = 'completed';
            } elseif ($hito->orden_hito === $current->orden_hito) {
                $status = 'current';
            }

            $timeline[] = [
                'id_hito' => (int) $hito->id_hito,
                'clave_hito' => $hito->clave_hito,
                'nombre_hito' => $hito->nombre_hito,
                'orden_hito' => (int) $hito->orden_hito,
                'fecha_inicio' => $hito->fecha_inicio,
                'fecha_fin' => $hito->fecha_fin,
                'status' => $status,
            ];
        }

        return [
            'solicitud' => $solicitud,
            'hito_actual' => $current,
            'timeline' => $timeline,
        ];
    }

    public function assertHitoActual(int $folio, string $hitoEsperado): void
    {
        $hitoEsperado = strtoupper(trim($hitoEsperado));

        $result = $this->getTimelineByFolio($folio);
        $actual = strtoupper((string) ($result['hito_actual']->clave_hito ?? ''));

        if ($actual !== $hitoEsperado) {
            throw ValidationException::withMessages([
                'hito' => 'La accion no esta permitida en el hito actual. Actual: ' . $actual . ', esperado: ' . $hitoEsperado,
            ]);
        }
    }

    public function generarSelloDigital(int $folio, array $documentos, int $idDirectivo): string
    {
        $payload = $folio
            . '|'
            . json_encode($documentos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '|'
            . $idDirectivo
            . '|'
            . config('app.key');

        return hash('sha256', $payload);
    }

    public function generarCuv(int $length = 18): string
    {
        $length = max(16, min(20, $length));

        do {
            $raw = strtoupper(bin2hex(random_bytes(16)));
            $cuv = substr($raw, 0, $length);

            $existsInTracking = DB::table('Seguimiento_Solicitud')->where('cuv', $cuv)->exists();
            $existsInSolicitudes = DB::table('Solicitudes')->where('cuv', $cuv)->exists();
        } while ($existsInTracking || $existsInSolicitudes);

        return $cuv;
    }

    public function generarFolioInstitucional(): string
    {
        $year = now()->format('Y');
        $municipio = strtoupper((string) env('SIGO_MUNICIPIO_CODIGO', 'XAL'));

        $consecutivo = DB::table('Solicitudes')
            ->whereYear('fecha_creacion', now()->year)
            ->count() + 1;

        return sprintf('SIGO-%s-%s-%04d', $year, $municipio, $consecutivo);
    }

    private function resolveCurrentHito(array $hitos): object
    {
        $now = now();

        foreach ($hitos as $hito) {
            $inicio = $hito->fecha_inicio ? Carbon::parse($hito->fecha_inicio) : null;
            $fin = $hito->fecha_fin ? Carbon::parse($hito->fecha_fin) : null;

            if ($inicio && $fin && $now->betweenIncluded($inicio, $fin)) {
                return $hito;
            }
        }

        foreach ($hitos as $hito) {
            $inicio = $hito->fecha_inicio ? Carbon::parse($hito->fecha_inicio) : null;
            if ($inicio && $now->lt($inicio)) {
                return $hito;
            }
        }

        return end($hitos);
    }
}
