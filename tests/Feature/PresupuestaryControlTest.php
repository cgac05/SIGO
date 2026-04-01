<?php

namespace Tests\Feature;

use App\Models\Apoyo;
use App\Models\PresupuestoApoyo;
use App\Models\PresupuestoCategoria;
use App\Models\Solicitud;
use App\Services\PresupuestaryControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresupuestaryControlTest extends TestCase
{
    use RefreshDatabase;

    private PresupuestaryControlService $service;
    private PresupuestoCategoria $categoria;
    private Apoyo $apoyo;
    private Solicitud $solicitud;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PresupuestaryControlService();

        // Setup de presupuesto
        $this->categoria = PresupuestoCategoria::create([
            'ano_fiscal' => now()->year,
            'nombre_categoria' => 'Educación',
            'presupuesto_inicial' => 5000000, // $5M
            'reservado' => 0,
            'aprobado' => 0,
            'estado' => 'ABIERTO',
        ]);

        // Setup de apoyo
        $this->apoyo = Apoyo::create([
            'nombre' => 'Becas Universitarias',
            'descripcion' => 'Test',
            'monto_maximo' => 50000,
        ]);

        // Setup de solicitud
        $this->solicitud = Solicitud::create([
            'folio_institucional' => 'SIGO-2026-TEP-00001-0',
            'monto_solicitado' => 50000,
            'estado' => 'DOCUMENTOS_VERIFICADOS',
        ]);
        $this->solicitud->apoyo()->associate($this->apoyo)->save();
    }

    /**
     * Test: Validar presupuesto para crear apoyo (exitoso)
     */
    public function test_validar_presupuesto_para_apoyo_exitoso()
    {
        $costoEstimado = 2000000; // $2M de $5M disponibles

        $resultado = $this->service->validarPresupuestoParaApoyo(
            $this->categoria->id_presupuesto,
            $costoEstimado
        );

        $this->assertTrue($resultado['valido']);
        $this->assertStringContainsString('OK', $resultado['mensaje']);
    }

    /**
     * Test: Rechazar cuando presupuesto es insuficiente
     */
    public function test_validar_presupuesto_insuficiente()
    {
        $costoEstimado = 6000000; // $6M de $5M disponibles (insuficiente)

        $resultado = $this->service->validarPresupuestoParaApoyo(
            $this->categoria->id_presupuesto,
            $costoEstimado
        );

        $this->assertFalse($resultado['valido']);
        $this->assertStringContainsString('insuficiente', strtolower($resultado['mensaje']));
    }

    /**
     * Test: Reservar presupuesto al crear apoyo
     */
    public function test_reservar_presupuesto_apoyo()
    {
        $costoEstimado = 2000000;

        // Act
        $presupuestoApoyo = $this->service->reservarPresupuestoApoyo(
            $this->apoyo->id,
            $costoEstimado,
            $this->categoria->id_presupuesto
        );

        // Assert
        $this->assertNotNull($presupuestoApoyo);
        $this->assertEquals($costoEstimado, $presupuestoApoyo->presupuesto_total);
        $this->assertEquals($costoEstimado, $presupuestoApoyo->disponible);

        // Verificar que se restó de categoría
        $categoriaActualizada = PresupuestoCategoria::find($this->categoria->id_presupuesto);
        $this->assertEquals(3000000, $categoriaActualizada->disponible);
        $this->assertEquals(2000000, $categoriaActualizada->reservado);
    }

    /**
     * Test: Validar presupuesto para solicitud (exitoso)
     */
    public function test_validar_presupuesto_solicitud_exitoso()
    {
        // Primero reservar presupuesto
        $this->service->reservarPresupuestoApoyo(
            $this->apoyo->id,
            2000000,
            $this->categoria->id_presupuesto
        );

        // Act
        $resultado = $this->service->validarPresupuestoParaSolicitud($this->solicitud->id);

        // Assert
        $this->assertTrue($resultado['valido']);
        $this->assertArrayHasKey('datos', $resultado);
    }

    /**
     * Test: Validar presupuesto solicitado fuera de límites
     */
    public function test_validar_presupuesto_solicitud_excede()
    {
        // Presupuesto muy bajo
        $this->categoria->update(['aprobado' => 4990000]); // Quedan solo $10K

        // Solicitud por $50K
        $resultado = $this->service->validarPresupuestoParaSolicitud($this->solicitud->id);

        $this->assertFalse($resultado['valido']);
    }

    /**
     * Test: Asignar presupuesto (firma directivo)
     */
    public function test_asignar_presupuesto_solicitud()
    {
        // Setup: Reservar presupuesto primero
        $this->service->reservarPresupuestoApoyo(
            $this->apoyo->id,
            2000000,
            $this->categoria->id_presupuesto
        );

        // Act: Asignar presupuesto
        $resultado = $this->service->asignarPresupuestoSolicitud(
            $this->solicitud->id,
            1 // directivo_id
        );

        // Assert
        $this->assertTrue($resultado['exitoso']);
        
        // Verificar cambios en BD
        $solicitudActualizada = Solicitud::find($this->solicitud->id);
        $this->assertTrue($solicitudActualizada->presupuesto_confirmado);
        $this->assertEquals(1, $solicitudActualizada->directivo_autorizo);

        // Verificar presupuesto
        $presupuestoApoyo = PresupuestoApoyo::where('fk_id_apoyo', $this->apoyo->id)->first();
        $this->assertEquals(1950000, $presupuestoApoyo->disponible);
        $this->assertEquals(50000, $presupuestoApoyo->aprobado);
    }

    /**
     * Test: Liberar presupuesto por rechazo
     */
    public function test_liberar_presupuesto_rechazo()
    {
        // Setup: Asignar presupuesto
        $this->service->reservarPresupuestoApoyo($this->apoyo->id, 2000000, $this->categoria->id_presupuesto);
        $this->service->asignarPresupuestoSolicitud($this->solicitud->id, 1);

        // Verificar estado inicial
        $presupuestoApoyo = PresupuestoApoyo::where('fk_id_apoyo', $this->apoyo->id)->first();
        $aprobadoAntes = $presupuestoApoyo->aprobado;

        // Act: Liberar
        $resultado = $this->service->liberarPresupuestoSolicitud($this->solicitud->id, 1);

        // Assert
        $this->assertTrue($resultado['exitoso']);

        // Verificar reversión
        $presupuestoActualizado = PresupuestoApoyo::where('fk_id_apoyo', $this->apoyo->id)->first();
        $this->assertEquals(0, $presupuestoActualizado->aprobado);
        $this->assertEquals(2000000, $presupuestoActualizado->disponible);
    }

    /**
     * Test: Obtener resumen de presupuesto
     */
    public function test_obtener_resumen()
    {
        // Act
        $resumen = $this->service->obtenerResumen();

        // Assert
        $this->assertArrayHasKey('ano_fiscal', $resumen);
        $this->assertArrayHasKey('total_presupuesto', $resumen);
        $this->assertArrayHasKey('categorias', $resumen);
        
        $this->assertEquals(5000000, $resumen['total_presupuesto']);
    }
}
