<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\HitosApoyo;
use App\Models\Apoyo;
use App\Models\DirectivoCalendarioPermiso;
use App\Events\HitoCambiado;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class GoogleCalendarIntegrationTest extends TestCase
{
    protected $directivo;
    protected $apoyo;
    protected $hito;
    protected $googleCalendarService;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear directivo de test
        $this->directivo = User::factory()->create([
            'tipo_usuario' => 'Directivo',
        ]);

        // Crear apoyo de test
        $this->apoyo = Apoyo::factory()->create([
            'sincronizar_calendario' => true,
            'recordatorio_dias' => 3,
        ]);

        // Crear hito de test
        $this->hito = HitosApoyo::factory()->create([
            'fk_id_apoyo' => $this->apoyo->id_apoyo,
            'google_calendar_sync' => true,
        ]);

        // Crear permisos de calendario para directivo
        DirectivoCalendarioPermiso::factory()->create([
            'fk_id_directivo' => $this->directivo->id_usuario,
            'activo' => true,
        ]);

        $this->googleCalendarService = app(GoogleCalendarService::class);
    }

    /**
     * Test: Evento HitoCambiado se dispara cuando se actualiza un hito
     */
    public function test_hito_cambio_event_is_dispatched()
    {
        Event::fake();

        // Actualizar hito
        $this->hito->update([
            'descripcion' => 'Descripción actualizada',
        ]);

        // Verificar que evento fue disparado
        Event::assertDispatchedTimes(HitoCambiado::class, 1);
    }

    /**
     * Test: Listener SincronizarHitoACalendario se ejecuta correctamente
     */
    public function test_sincronizar_hito_listener_execute()
    {
        Event::fake([HitoCambiado::class]);

        // Este test verifica que el listener está registrado
        // en EventServiceProvider
        $this->markTestIncomplete('Test completo requiere mock de Google Calendar API');
    }

    /**
     * Test: Modelo HitosApoyo tiene métodos de sincronización
     */
    public function test_hitos_apoyo_model_has_sync_methods()
    {
        $this->assertTrue(method_exists($this->hito, 'marcarComSincronizado'));
        $this->assertTrue(method_exists($this->hito, 'marcarCambiosPendientes'));
    }

    /**
     * Test: Modelo Apoyo tiene relación con HitosApoyo
     */
    public function test_apoyo_has_hitos_relationship()
    {
        $hitos = $this->apoyo->hitos()->get();
        $this->assertGreaterThanOrEqual(1, $hitos->count());
        $this->assertTrue($hitos->contains($this->hito));
    }

    /**
     * Test: DirectivoCalendarioPermiso se valida correctamente
     */
    public function test_directivo_calendario_permiso_token_validation()
    {
        $permiso = $this->directivo->calendarioPermiso;

        // Token válido
        $permiso->update([
            'token_expiracion' => now()->addHours(2),
        ]);
        $this->assertFalse($permiso->tokenExpirado());
        $this->assertFalse($permiso->tokenVencePronto());

        // Token expirado
        $permiso->update([
            'token_expiracion' => now()->subHour(),
        ]);
        $this->assertTrue($permiso->tokenExpirado());

        // Token vence pronto (menos de 1 hora)
        $permiso->update([
            'token_expiracion' => now()->addMinutes(30),
        ]);
        $this->assertTrue($permiso->tokenVencePronto());
    }

    /**
     * Test: User model tiene relación calendarioPermiso
     */
    public function test_user_model_has_calendario_permiso_relationship()
    {
        $this->assertNotNull($this->directivo->calendarioPermiso);
        $this->assertEquals($this->directivo->id_usuario, $this->directivo->calendarioPermiso->fk_id_directivo);
    }

    /**
     * Test: Scopes en HitosApoyo funcionan correctamente
     */
    public function test_hitos_apoyo_scopes()
    {
        // Scope: pendienteSincronizacion
        $this->hito->marcarCambiosPendientes();
        $pendientes = HitosApoyo::pendienteSincronizacion()->pluck('id_hito')->toArray();
        $this->assertContains($this->hito->id_hito, $pendientes);

        // Scope: sincronizacionActiva
        $activos = HitosApoyo::sincronizacionActiva()->pluck('id_hito')->toArray();
        $this->assertContains($this->hito->id_hito, $activos);
    }

    /**
     * Test: Scheduler command puede ser ejecutado
     */
    public function test_sync_google_calendar_command_can_run()
    {
        $this->artisan('sync:google-calendar')
            ->expectsOutput('Iniciando sincronización de Google Calendar...')
            ->assertExitCode(0);
    }
}
