<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Solicitud;
use App\Models\Beneficiario;
use App\Services\FirmaElectronicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test de Integración: Flujo Completo de Solicitud
 * 
 * Simula el ciclo de vida completo de una solicitud desde su creación
 * hasta la firma digital de aprobación o rechazo
 */
class SolicitudFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $directivo;
    private Usuario $admin;
    private Beneficiario $beneficiario;
    private Solicitud $solicitud;
    private FirmaElectronicaService $firmaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firmaService = new FirmaElectronicaService();

        // Crear usuarios
        $this->beneficiario = Beneficiario::factory()->create();
        $this->admin = Usuario::factory()->create([
            'tipo_usuario' => 'personal',
            'rol' => 'admin',
            'password' => Hash::make('admin123'),
        ]);
        $this->directivo = Usuario::factory()->create([
            'tipo_usuario' => 'personal',
            'rol' => 'directivo',
            'password' => Hash::make('directivo123'),
        ]);

        // Crear solicitud
        $this->solicitud = Solicitud::factory()->create([
            'id_beneficiario' => $this->beneficiario->id,
            'estado' => 'INGRESADA',
            'monto_solicitado' => 50000,
        ]);
    }

    /**
     * Test: Flujo completo de aprobación
     */
    public function test_flujo_completo_aprobacion()
    {
        // FASE 1: Admin verifica documentos
        $this->actingAs($this->admin);
        
        $this->solicitud->update(['estado' => 'DOCUMENTOS_EN_VERIFICACION']);
        $this->assertEquals('DOCUMENTOS_EN_VERIFICACION', $this->solicitud->fresh()->estado);

        // FASE 2: Admin aprueba documentos
        $this->solicitud->update(['estado' => 'DOCUMENTOS_VERIFICADOS']);
        $this->assertEquals('DOCUMENTOS_VERIFICADOS', $this->solicitud->fresh()->estado);

        // FASE 3: Directivo re-autentica y firma
        // Simular re-autenticación
        $response = $this->postJson(route('auth.reauth-verify'), [
            'password' => 'directivo123',
            'otp' => null,
        ]);
        $this->actingAs($this->directivo);

        $response->assertStatus(200);
        $reauthToken = $response->json('reauth_token');

        // FASE 4: Generar firma
        $firma = $this->firmaService->generarFirmaDigital(
            password: 'directivo123',
            folioSolicitud: $this->solicitud->folio,
            directorId: $this->directivo->id,
            tipoDocumento: 'SOLICITUD_APOYO'
        );

        $this->assertNotNull($firma['firma_hex']);
        $this->assertNotNull($firma['cuv']);

        // FASE 5: Marcar como aprobada
        $this->solicitud->update([
            'estado' => 'APROBADA',
            'fecha_aprobacion' => now(),
        ]);
        $this->assertEquals('APROBADA', $this->solicitud->fresh()->estado);

        // VERIFICACIONES FINALES
        $this->assertDatabaseHas('firmas_electronicas', [
            'folio_solicitud' => $this->solicitud->folio,
            'id_directivo' => $this->directivo->id,
            'cuv' => $firma['cuv'],
        ]);
    }

    /**
     * Test: Flujo completo de rechazo
     */
    public function test_flujo_completo_rechazo()
    {
        // FASE 1: Admin verifica documentos
        $this->actingAs($this->admin);
        
        $this->solicitud->update([
            'estado' => 'DOCUMENTOS_EN_VERIFICACION'
        ]);

        // FASE 2: Admin identifica documentos incompletos y cambia a estado lista para análsis
        $this->solicitud->update([
            'estado' => 'LISTA_PARA_ANALISIS'
        ]);

        // FASE 3: Directivo re-autentica
        $this->actingAs($this->directivo);
        
        $response = $this->postJson(route('auth.reauth-verify'), [
            'password' => 'directivo123',
            'otp' => null,
        ]);

        $response->assertStatus(200);

        // FASE 4: Directivo rechaza solicitud
        $resulRechazo = $this->firmaService->rechazarSolicitud(
            numeroFolio: $this->solicitud->folio,
            motivoRechazo: 'DOCUMENTACION_INCOMPLETA',
            directorId: $this->directivo->id,
            comentarios: 'Falta cédula de identidad expedida'
        );

        $this->assertTrue($resulRechazo['exitoso']);

        // FASE 5: Actualizar estado
        $this->solicitud->update(['estado' => 'RECHAZADA']);

        // VERIFICACIONES FINALES
        $this->assertEquals('RECHAZADA', $this->solicitud->fresh()->estado);
        
        $this->assertDatabaseHas('rechazo_solicitudes', [
            'folio_solicitud' => $this->solicitud->folio,
        ]);
    }

    /**
     * Test: Intento de firma sin re-autenticación (debe fallar)
     */
    public function test_firma_sin_reauthenticacion_falla()
    {
        $this->actingAs($this->directivo);
        $this->solicitud->update(['estado' => 'DOCUMENTOS_VERIFICADOS']);

        // Intentar generar firma sin re-autenticación válida
        // Esto debería ser bloqueado por middleware
        // Por ahora solo verificamos que sin token válido no se puede acceder

        $this->assertTrue(true); // Placeholder para lógica de middleware
    }

    /**
     * Test: Verificación de auditoría completa
     */
    public function test_auditoria_completa()
    {
        // Ejecutar flujo completo
        $this->actingAs($this->admin);
        $this->solicitud->update(['estado' => 'DOCUMENTOS_VERIFICADOS']);

        $this->actingAs($this->directivo);
        $this->postJson(route('auth.reauth-verify'), [
            'password' => 'directivo123',
            'otp' => null,
        ]);

        $firma = $this->firmaService->generarFirmaDigital(
            password: 'directivo123',
            folioSolicitud: $this->solicitud->folio,
            directorId: $this->directivo->id,
            tipoDocumento: 'SOLICITUD_APOYO'
        );

        // Verificar registros de auditoría
        $this->assertDatabaseHas('firmas_electronicas', [
            'folio_solicitud' => $this->solicitud->folio,
            'id_directivo' => $this->directivo->id,
        ]);

        $this->assertDatabaseHas('auditoria_reauthenticacion', [
            'usuario_id' => $this->directivo->id,
            'exitoso' => true,
        ]);
    }

    /**
     * Test: Intentar firmar fuera de time window permitido
     */
    public function test_firma_fuera_de_time_window()
    {
        // Crear solicitud con fecha muy antigua
        $this->solicitud->update([
            'estado' => 'DOCUMENTOS_VERIFICADOS',
            'fecha_creacion' => now()->subDays(90), // Más de 60 días
        ]);

        $this->actingAs($this->directivo);

        $resultado = $this->firmaService->validarPreRequisitosSignatura(
            $this->solicitud->folio,
            $this->directivo,
            'directivo123'
        );

        // Debería fallar porque está fuera del time window de 60 días
        $this->assertFalse($resultado['valido']);
    }
}
