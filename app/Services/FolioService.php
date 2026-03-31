<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * FolioService
 *
 * Servicio centralizado para generación y validación de folios institucionales.
 * Implementa:
 * - Dígito verificador (Algoritmo Verhoeff) para integridad
 * - Consecutivo anual secuencial
 * - Formato: SIGO-2026-TEP-NNNNN-C (donde C = dígito verificador)
 * - Auditoría mediante tabla auditoria_folios
 * - Validación de duplicados
 *
 * Compliance:
 * - LGPDP: Auditoría de folios en DB
 * - LFTAIPG: Transparencia en numeración
 * - Mexicana: Formato institucional estándar
 */
class FolioService
{
    /**
     * Tabla de permutaciones Verhoeff (para validación)
     */
    private const VERHOEFF_PERMUTATIONS = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];

    /**
     * Tabla de inversiones Verhoeff
     */
    private const VERHOEFF_INVERSE = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];

    /**
     * Tabla de multiplicación Verhoeff
     */
    private const VERHOEFF_MULTIPLY = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];

    /**
     * Generar folio institucional con dígito verificador
     *
     * Formato: SIGO-YYYY-MMM-NNNNN-C
     * Ejemplo: SIGO-2026-TEP-00001-3
     *
     * @param int|null $beneficiarioId ID beneficiario (para auditoría)
     * @return string Folio completo con dígito verificador
     */
    public function generarFolioInstitucional(?int $beneficiarioId = null): string
    {
        $year = now()->format('Y');
        $municipio = strtoupper((string) env('SIGO_MUNICIPIO_CODIGO', 'TEP'));

        // Obtener próximo consecutivo
        $consecutivo = $this->obtenerProximoConsecutivo($year);

        // Construir base del folio (sin dígito verificador)
        $baseNumerico = str_pad($consecutivo, 5, '0', STR_PAD_LEFT);
        $baseFolio = "SIGO-{$year}-{$municipio}-{$baseNumerico}";

        // Calcular dígito verificador
        $digitoVerificador = $this->calcularDigitoVerhoeff($baseNumerico);

        // Folio final
        $folioCompleto = "{$baseFolio}-{$digitoVerificador}";

        // Registrar en auditoría
        $this->registrarGeneracionFolio($folioCompleto, $baseNumerico, $digitoVerificador, $beneficiarioId);

        return $folioCompleto;
    }

    /**
     * Validar integridad de un folio existente
     *
     * @param string $folio Folio a validar (ej: SIGO-2026-TEP-00001-3)
     * @return array ['valido' => bool, 'mensaje' => string, 'error_tipo' => string|null]
     */
    public function validarFolio(string $folio): array
    {
        // Validar formato general
        if (!preg_match('/^SIGO-\d{4}-[A-Z]{3}-\d{5}-\d$/', $folio)) {
            return [
                'valido' => false,
                'mensaje' => 'Formato de folio inválido. Esperado: SIGO-YYYY-MMM-NNNNN-D',
                'error_tipo' => 'formato_invalido',
            ];
        }

        // Extraer componentes
        [$prefijo, $year, $mun, $numero, $digitoProporcionado] = explode('-', $folio);

        // Validar año razonable
        $yearActual = (int) now()->format('Y');
        $yearFolio = (int) $year;
        if ($yearFolio < 2024 || $yearFolio > $yearActual + 2) {
            return [
                'valido' => false,
                'mensaje' => "Año fuera de rango: {$yearFolio}",
                'error_tipo' => 'año_invalido',
            ];
        }

        // Calcular dígito verificador esperado
        $digitoEsperado = $this->calcularDigitoVerhoeff($numero);

        if ((int) $digitoProporcionado !== $digitoEsperado) {
            return [
                'valido' => false,
                'mensaje' => "Dígito verificador incorrecto. Esperado: {$digitoEsperado}, Recibido: {$digitoProporcionado}",
                'error_tipo' => 'digito_incorrecto',
            ];
        }

        // Validar que no sea duplicado
        $existe = DB::table('Solicitudes')
            ->where('folio_institucional', $folio)
            ->exists();

        if ($existe) {
            return [
                'valido' => false,
                'mensaje' => 'Folio ya existe en el sistema',
                'error_tipo' => 'folio_duplicado',
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'Folio válido e íntegro',
            'error_tipo' => null,
        ];
    }

    /**
     * Calcular dígito verificador usando algoritmo Verhoeff
     * Más robusta que Luhn para errores de transposición y sustitución
     *
     * @param string $numero Número sin dígito verificador (5 dígitos)
     * @return int Dígito verificador (0-9)
     */
    private function calcularDigitoVerhoeff(string $numero): int
    {
        $c = 0;
        $invertFlag = false;

        // Procesar de derecha a izquierda
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digit = (int) $numero[$i];
            $c = self::VERHOEFF_MULTIPLY[$c][self::VERHOEFF_PERMUTATIONS[$invertFlag ? 1 : 0][$digit]];
            $invertFlag = !$invertFlag;
        }

        return self::VERHOEFF_INVERSE[$c];
    }

    /**
     * Obtener próximo consecutivo del año
     *
     * @param string $year Año fiscal (ej: 2026)
     * @return int Próximo número consecutivo
     */
    private function obtenerProximoConsecutivo(string $year): int
    {
        $consecutivo = DB::table('auditoria_folios')
            ->where('año_fiscal', $year)
            ->count();

        if ($consecutivo === 0) {
            // Fallback: contar solicitudes ya creadas este año
            $consecutivo = DB::table('Solicitudes')
                ->whereYear('fecha_creacion', $year)
                ->count();
        }

        return $consecutivo + 1;
    }

    /**
     * Registrar generación de folio en auditoría
     *
     * @param string $folioCompleto SIGO-2026-TEP-00001-3
     * @param string $numero NNNNN sin dígito
     * @param int $digito Dígito verificador
     * @param int|null $beneficiarioId
     * @return void
     */
    private function registrarGeneracionFolio(
        string $folioCompleto,
        string $numero,
        int $digito,
        ?int $beneficiarioId = null
    ): void {
        DB::table('auditoria_folios')->insert([
            'folio_completo' => $folioCompleto,
            'numero_base' => $numero,
            'digito_verificador' => $digito,
            'fk_id_beneficiario' => $beneficiarioId,
            'año_fiscal' => now()->format('Y'),
            'fecha_generacion' => now(),
            'generado_por' => auth()->id(),
            'ip_generacion' => request()->ip(),
        ]);

        // También registrar en logs por redundancia
        Log::channel('folios')->info('Folio generado', [
            'folio_completo' => $folioCompleto,
            'numero_base' => $numero,
            'digito_verificador' => $digito,
            'beneficiario_id' => $beneficiarioId,
        ]);
    }

    /**
     * Validar múltiples folios
     */
    public function validarMultiplesFolios(array $folios): array
    {
        return array_map(fn($folio) => $this->validarFolio($folio), $folios);
    }

    /**
     * Obtener estadísticas de folios generados
     *
     * @param string|null $year Año específico (default: actual)
     * @return array Estadísticas de generación
     */
    public function obtenerEstadisticas(?string $year = null): array
    {
        $year = $year ?? now()->format('Y');

        $totalGenerados = DB::table('auditoria_folios')
            ->where('año_fiscal', $year)
            ->count();

        $usados = DB::table('auditoria_folios')
            ->where('año_fiscal', $year)
            ->whereNotNull('fk_folio_solicitud')
            ->count();

        $pendientes = $totalGenerados - $usados;
        $porcentajeUtilizacion = $totalGenerados > 0 ? round(($usados / $totalGenerados) * 100, 2) : 0;

        return [
            'año_fiscal' => $year,
            'total_generados' => $totalGenerados,
            'total_usados' => $usados,
            'total_pendientes' => $pendientes,
            'porcentaje_utilizacion' => $porcentajeUtilizacion . '%',
            'proximo_folio' => $this->anticiparProximoFolio($year),
        ];
    }

    /**
     * Anticipar próximo folio (sin generarlo)
     *
     * @param string $year Año fiscal
     * @return string Folio que será generado
     */
    private function anticiparProximoFolio(string $year): string
    {
        $municipio = strtoupper((string) env('SIGO_MUNICIPIO_CODIGO', 'TEP'));
        $proximoConsecutivo = $this->obtenerProximoConsecutivo($year);
        $numeroFormato = str_pad($proximoConsecutivo, 5, '0', STR_PAD_LEFT);
        $digito = $this->calcularDigitoVerhoeff($numeroFormato);

        return "SIGO-{$year}-{$municipio}-{$numeroFormato}-{$digito}";
    }

    /**
     * Regenerar folio en caso de error (operación muy sensible - requiere auditoría)
     *
     * @param int $folioId
     * @param string $motivoRechazo Razón de regeneración
     * @param string $notas Notas adicionales
     * @return array ['exitoso' => bool, 'folio_anterior' => string, 'folio_nuevo' => string]
     */
    public function regenerarFolio(int $folioId, string $motivoRechazo, string $notas = ''): array
    {
        $solicitud = DB::table('Solicitudes')->where('folio', $folioId)->first();

        if (!$solicitud) {
            return [
                'exitoso' => false,
                'mensaje' => 'Solicitud no encontrada',
            ];
        }

        // Solo permitir regeneración en estados iniciales
        if (!in_array($solicitud->fk_id_estado, [1, 2])) {
            return [
                'exitoso' => false,
                'mensaje' => 'No se puede regenerar folio en este estado de solicitud',
            ];
        }

        try {
            $folioAnterior = $solicitud->folio_institucional;
            $folioNuevo = $this->generarFolioInstitucional((int) $solicitud->fk_curp);

            // Crear registro de auditoría de regeneración
            DB::table('auditoria_regeneraciones_folio')->insert([
                'folio_anterior' => $folioAnterior,
                'folio_nuevo' => $folioNuevo,
                'fk_folio_solicitud' => $folioId,
                'motivo' => $motivoRechazo,
                'notas' => $notas,
                'generado_por' => auth()->id(),
                'fecha_regeneracion' => now(),
            ]);

            // Actualizar solicitud
            DB::table('Solicitudes')
                ->where('folio', $folioId)
                ->update(['folio_institucional' => $folioNuevo]);

            return [
                'exitoso' => true,
                'folio_anterior' => $folioAnterior,
                'folio_nuevo' => $folioNuevo,
            ];
        } catch (\Exception $e) {
            return [
                'exitoso' => false,
                'mensaje' => 'Error al regenerar folio: ' . $e->getMessage(),
            ];
        }
    }
}
