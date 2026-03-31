<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Solicitud;
use App\Services\FirmaElectronicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test suite para el flujo completo de firma digital de solicitudes
 * 
 * Cubre:
 * - Validación de pre-requisitos
 * - Generación de firma digital
 * - Auditoría de firma
 * - Verificación de integridad
 */
class FirmaElectronicaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private FirmaElectronicaService $firmaService;
    private Usuario $directivo;
    private Solicitud $solicitud;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->firmaService = new FirmaElectronicaService();
        
        // Crear directivo de prueba
        $this->directivo = Usuario::factory()->create([
            'tipo_usuario' => 'personal',
            'rol' => 'directivo',
            'password' => Hash::make('directivo123'),
        ]);

        // Crear solicitud de prueba (estado: DOCUMENTOS_VERIFICADOS)
        $this->solicitud = Solicitud::factory()->create([
            'estado' => 'DOCUMENTOS_VERIFICADOS',
            'monto_solicitado' => 50000,
        ]);
    }

    /**
     * Test: Validar pre-requisitos correctamente
     */
    public function test_validar_prerequisitos_exitoso()
    {
        // Act
        $resultado = $this->firmaService->validarPreRequisitosSignatura(
            $this->solicitud->folio,
            $this->directivo,
            'directivo123'
        );

        // Assert
        $this->assertTrue($resultado['valido']);
        $this->assertArrayHasKey('datos', $resultado);
    }

    /**
     * Test: Rechazar si contraseña es incorrecta
     */
    public function test_rechazar_si_contraseña_incorrecta()
    {
        $resultado = $this->firmaService->validarPreRequisitosSignatura(
            $this->solicitud->folio,
            $this->directivo,
            'contraseña_incorrecta'
        );

        $this->assertFalse($resultado['valido']);
        $this->assertStringContainsString('contraseña', strtolower($resultado['mensaje']));
    }

    /**
     * Test: Rechazar si la solicitud no existe
     */
    public function test_rechazar_si_solicitud_no_existe()
    {
        $resultado = $this->firmaService->validarPreRequisitosSignatura(
            9999, // ID inexistente
            $this->directivo,
            'directivo123'
        );

        $this->assertFalse($resultado['valido']);
    }

    /**
     * Test: Generar firma digital correctamente
     */
    public function test_generar_firma_digital()
    {
        // Pre-requisitos validados
        $validacion = $this->firmaService->validarPreRequisitosSignatura(
            $this->solicitud->folio,
            $this->directivo,
            'directivo123'
        );

        if (!$validacion['valido']) {
            $this->fail('Pre-requisitos no validados: ' . $validacion['mensaje']);
        }

        // Act - Generar firma
        $firma = $this->firmaService->generarFirmaDigital(
            password: 'directivo123',
            folioSolicitud: $this->solicitud->folio,
            directorId: $this->directivo->id,
            tipoDocumento: 'SOLICITUD_APOYO'
        );

        // Assert
        $this->assertIsArray($firma);
        $this->assertArrayHasKey('firma_hex', $firma);
        $this->assertArrayHasKey('cuv', $firma);
        $this->assertArrayHasKey('timestamp', $firma);
        
        // La firma debe ser un hash SHA256 (64 caracteres hexadecimales)
        $this->assertRegExp('/^[a-f0-9]{64}$/', $firma['firma_hex']);
        
        // El CUV debe tener formato específico
        $this->assertRegExp('/^CUV-\d{4}-\d{6}-[A-Z0-9]{8}$/', $firma['cuv']);
    }

    /**
     * Test: Rechazar firma en estado incorrecto
     */
    public function test_rechazar_firma_en_estado_incorrecto()
    {
        // Cambiar solicitud a estado no permitido
        $this->solicitud->update(['estado' => 'RECHAZADA']);

        $resultado = $this->firmaService->validarPreRequisitosSignatura(
            $this->solicitud->folio,
            $this->directivo,
            'directivo123'
        );

        $this->assertFalse($resultado['valido']);
    }

    /**
     * Test: Verificar integridad de firma
     */
    public function test_verificar_integridad_firma()
    {
        // Generar firma
        $firma = $this->firmaService->generarFirmaDigital(
            password: 'directivo123',
            folioSolicitud: $this->solicitud->folio,
            directorId: $this->directivo->id,
            tipoDocumento: 'SOLICITUD_APOYO'
        );

        // Verificar integridad
        $esValida = $this->firmaService->verificarIntegridad(
            numeroFolio: $this->solicitud->folio,
            firmaHex: $firma['firma_hex'],
            cuv: $firma['cuv']
        );

        $this->assertTrue($esValida);
    }

    /**
     * Test: Detectar firma adulterada
     */
    public function test_detectar_firma_adulterada()
    {
        // Generar firma válida
        $firma = $this->firmaService->generarFirmaDigital(
            password: 'directivo123',
            folioSolicitud: $this->solicitud->folio,
            directorId: $this->directivo->id,
            tipoDocumento: 'SOLICITUD_APOYO'
        );

        // Adulterar firma
        $firmaAdulterada = substr_replace($firma['firma_hex'], 'AA', 0, 2);

        // Verificación debe fallar
        $esValida = $this->firmaService->verificarIntegridad(
            numeroFolio: $this->solicitud->folio,
            firmaHex: $firmaAdulterada,
            cuv: $firma['cuv']
        );

        $this->assertFalse($esValida);
    }

    /**
     * Test: Registrar auditoría de firma
     */
    public function test_registrar_auditoria_firma()
    {
        $firma = $this->firmaService->generarFirmaDigital(
            password: 'directivo123',
            folioSolicitud: $this->solicitud->folio,
            directorId: $this->directivo->id,
            tipoDocumento: 'SOLICITUD_APOYO'
        );

        // Verificar que se registró en auditoría
        $this->assertDatabaseHas('firmas_electronicas', [
            'folio_solicitud' => $this->solicitud->folio,
            'id_directivo' => $this->directivo->id,
            'cuv' => $firma['cuv'],
        ]);
    }

    /**
     * Test: Rechazar solicitud (revoke de firma)
     */
    public function test_rechazar_solicitud()
    {
        // Pre-requisitos: solicitud ya fue generada con firma
        $firma = $this->firmaService->generarFirmaDigital(
            password: 'directivo123',
            folioSolicitud: $this->solicitud->folio,
            directorId: $this->directivo->id,
            tipoDocumento: 'SOLICITUD_APOYO'
        );

        // Act - Rechazar
        $resultado = $this->firmaService->rechazarSolicitud(
            numeroFolio: $this->solicitud->folio,
            motivoRechazo: 'Documentación incompleta',
            directorId: $this->directivo->id,
            comentarios: 'Se requiere cédula de identidad valida'
        );

        // Assert
        $this->assertTrue($resultado['exitoso']);
        
        // Verificar que se registró rechazo
        $this->assertDatabaseHas('rechazo_solicitudes', [
            'folio_solicitud' => $this->solicitud->folio,
            'id_directivo' => $this->directivo->id,
        ]);
    }
}
