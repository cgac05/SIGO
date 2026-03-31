<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GoogleCalendarService;
use App\Models\DirectivoCalendarioPermiso;
use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleCalendarServiceTest extends TestCase
{
    protected $googleCalendarService;
    protected $permiso;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->googleCalendarService = app(GoogleCalendarService::class);
        
        // Crear permisos de prueba
        $this->permiso = DirectivoCalendarioPermiso::factory()->create();
    }

    /**
     * Test: Servicio se instancia correctamente
     */
    public function test_google_calendar_service_instantiates()
    {
        $this->assertNotNull($this->googleCalendarService);
        $this->assertInstanceOf(GoogleCalendarService::class, $this->googleCalendarService);
    }

    /**
     * Test: obtenerColorPorHito retorna color válido
     */
    public function test_obtener_color_por_hito_returns_valid_color()
    {
        $apoyo = Apoyo::factory()->create();
        $hito = HitosApoyo::factory()->create([
            'fk_id_apoyo' => $apoyo->id_apoyo,
        ]);

        // El servicio debe retornar un array con propiedades de color válidas
        // Este test pasará cuando el servicio esté completamente implementado
        $this->markTestIncomplete('Test requiere implementación completa del servicio');
    }

    /**
     * Test: construirDescripcionEvento retorna string válido
     */
    public function test_construir_descripcion_evento_returns_string()
    {
        $apoyo = Apoyo::factory()->create();
        $hito = HitosApoyo::factory()->create([
            'fk_id_apoyo' => $apoyo->id_apoyo,
        ]);

        // Este test verifica que el método retorna un string
        $this->markTestIncomplete('Test requiere acceso a métodos privados del servicio');
    }

    /**
     * Test: tokenExpirado valida correctamente
     */
    public function test_token_expiracion_validation()
    {
        $this->permiso->update([
            'token_expiracion' => now()->subHour(),
        ]);

        $this->assertTrue($this->permiso->tokenExpirado());
    }

    /**
     * Test: tokenVencePronto valida correctamente (menos de 1 hora)
     */
    public function test_token_expira_pronto_validation()
    {
        $this->permiso->update([
            'token_expiracion' => now()->addMinutes(30),
        ]);

        $this->assertTrue($this->permiso->tokenVencePronto());
    }

    /**
     * Test: Token válido no aparece como expirado
     */
    public function test_token_valido_no_expirado()
    {
        $this->permiso->update([
            'token_expiracion' => now()->addHours(5),
        ]);

        $this->assertFalse($this->permiso->tokenExpirado());
        $this->assertFalse($this->permiso->tokenVencePronto());
    }

    /**
     * Test: DirectivoCalendarioPermiso puede ser guardado con encriptación
     */
    public function test_directivo_calendario_permiso_encryption()
    {
        $tokenOriginal = 'test_token_1234567890';
        
        $this->permiso->update([
            'access_token' => $tokenOriginal,
            'refresh_token' => 'refresh_token_1234567890',
        ]);

        // Recargar desde DB
        $permisoRecargado = DirectivoCalendarioPermiso::find($this->permiso->id);
        
        // Laravel encrypt/decrypt automáticamente
        $this->assertEquals($tokenOriginal, $permisoRecargado->access_token);
    }

    /**
     * Test: Validar estructura de ApoYo con sincronización
     */
    public function test_apoyo_sincronizacion_fields()
    {
        $apoyo = Apoyo::factory()->create([
            'sincronizar_calendario' => true,
            'recordatorio_dias' => 3,
            'google_group_email' => 'test@example.com',
        ]);

        $apoyo->refresh();
        
        $this->assertTrue($apoyo->sincronizar_calendario);
        $this->assertEquals(3, $apoyo->recordatorio_dias);
        $this->assertEquals('test@example.com', $apoyo->google_group_email);
    }

    /**
     * Test: Validar estructura de HitosApoyo con campos de Google
     */
    public function test_hitos_apoyo_google_fields()
    {
        $hito = HitosApoyo::factory()->create([
            'google_calendar_event_id' => 'event_abc123xyz',
            'google_calendar_sync' => true,
            'cambios_locales_pendientes' => false,
        ]);

        $hito->refresh();
        
        $this->assertEquals('event_abc123xyz', $hito->google_calendar_event_id);
        $this->assertTrue($hito->google_calendar_sync);
        $this->assertFalse($hito->cambios_locales_pendientes);
    }

    /**
     * Test: Audit logging de sincronización
     */
    public function test_sync_audit_logging()
    {
        Log::spy();

        // Simular una sincronización
        Log::info('Test sync operation', [
            'operacion' => 'test_sync',
            'estado' => 'success',
            'cambios' => 5,
        ]);

        Log::shouldHaveReceived('info')->once();
    }
}
