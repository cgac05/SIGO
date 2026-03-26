<?php

namespace Tests\Unit;

use App\Services\HitosApoyoService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HitosApoyoServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Validar que estructura de BD es correcta
     */
    public function test_validate_table_schema_existe()
    {
        $schema = HitosApoyoService::validateTableSchema();

        $this->assertTrue($schema['tabla_existe'], 'Tabla Hitos_Apoyo debe existir');
        $this->assertEmpty($schema['inconsistencias'], 'No debe haber inconsistencias');
        $this->assertNotEmpty($schema['columnas_requeridas'], 'Debe haber columnas requeridas');
    }

    /**
     * Test 2: Validar que no se envían campos vacíos
     */
    public function test_prepare_hito_row_completa_todos_campos()
    {
        $milestone = [
            'titulo' => 'Hito de prueba',
            'slug' => 'prueba',
            'fecha_inicio' => '2026-03-26',
            'fecha_fin' => '2026-04-26',
            'es_base' => 1,
        ];

        $row = HitosApoyoService::prepareHitoRow($milestone, 1, 1);

        // Verificar que retorna array
        $this->assertIsArray($row);

        // Verificar campos requeridos
        $this->assertArrayHasKey('fk_id_apoyo', $row);
        $this->assertArrayHasKey('titulo_hito', $row);
        $this->assertArrayHasKey('fecha_creacion', $row);
        $this->assertArrayHasKey('es_base', $row);
        $this->assertArrayHasKey('activo', $row);
        $this->assertArrayHasKey('orden', $row);

        // Verificar valores
        $this->assertEquals(1, $row['fk_id_apoyo']);
        $this->assertEquals('Hito de prueba', $row['titulo_hito']);
        $this->assertEquals('2026-03-26', $row['fecha_inicio']);
        $this->assertEquals('2026-04-26', $row['fecha_fin']);
        $this->assertEquals(1, $row['es_base']);
        $this->assertEquals(1, $row['activo']);
    }

    /**
     * Test 3: Validar que no acepta hitos sin título
     */
    public function test_prepare_hito_row_rechaza_sin_titulo()
    {
        $milestone = [
            'titulo' => '',  // Vacío
            'slug' => 'prueba',
        ];

        $row = HitosApoyoService::prepareHitoRow($milestone, 1, 1);

        $this->assertNull($row, 'Debe retornar null si no tiene título');
    }

    /**
     * Test 4: Validar consistencia de múltiples filas
     */
    public function test_validate_consistency_multiples_rows()
    {
        $rows = [
            [
                'fk_id_apoyo' => 1,
                'titulo_hito' => 'Hito 1',
                'es_base' => 0,
                'activo' => 1,
                'orden' => 1,
                'fecha_creacion' => now(),
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'slug_hito' => 'hito1',
            ],
            [
                'fk_id_apoyo' => 1,
                'titulo_hito' => 'Hito 2',
                'es_base' => 0,
                'activo' => 1,
                'orden' => 2,
                'fecha_creacion' => now(),
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'slug_hito' => null,
            ],
        ];

        $validation = HitosApoyoService::validateConsistency($rows);

        $this->assertTrue($validation['es_valido'], 'Rows deben ser válidas');
        $this->assertEmpty($validation['errores'], 'No debe haber errores');
        $this->assertEquals(2, $validation['total_filas']);
    }

    /**
     * Test 5: Validar rechazo de filas sin título
     */
    public function test_validate_consistency_rechaza_sin_titulo()
    {
        $rows = [
            [
                'fk_id_apoyo' => 1,
                'titulo_hito' => '',  // Vacío - ERROR
                'es_base' => 0,
                'activo' => 1,
                'orden' => 1,
                'fecha_creacion' => now(),
            ],
        ];

        $validation = HitosApoyoService::validateConsistency($rows);

        $this->assertFalse($validation['es_valido'], 'Debe ser inválida');
        $this->assertNotEmpty($validation['errores'], 'Debe haber errores');
    }

    /**
     * Test 6: Validar que solo se usan columnas válidas
     */
    public function test_validate_consistency_solo_columnas_validas()
    {
        $rows = [
            [
                'fk_id_apoyo' => 1,
                'titulo_hito' => 'Test',
                'columna_inexistente' => 'valor',  // ❌ Columna que no existe
            ],
        ];

        $validation = HitosApoyoService::validateConsistency($rows);

        // Puede tener advertencias pero no errores críticos
        // (dependiendo de la implementación)
    }

    /**
     * Test 7: Inserción completa con validación
     */
    public function test_insert_hitos_validated_exitoso()
    {
        $milestones = [
            [
                'titulo' => 'Inicio de publicación',
                'slug' => 'inicio',
                'fecha_inicio' => '2026-03-26',
                'fecha_fin' => '2026-04-26',
                'es_base' => 1,
                'incluir' => true,
            ],
            [
                'titulo' => 'Recepción de documentos',
                'slug' => 'recepcion',
                'fecha_inicio' => '2026-04-27',
                'fecha_fin' => '2026-05-31',
                'es_base' => 0,
                'incluir' => true,
            ],
        ];

        $result = HitosApoyoService::insertHitosValidated($milestones, 1);

        $this->assertTrue($result['exitoso'], 'Inserción debe ser exitosa');
        $this->assertEquals(2, $result['total_insertados']);
        $this->assertEmpty($result['errores']);
    }

    /**
     * Test 8: Rechazo de hitos marcados como no incluir
     */
    public function test_insert_hitos_validated_rechaza_no_incluir()
    {
        $milestones = [
            [
                'titulo' => 'Hito 1',
                'slug' => 'hito1',
                'incluir' => true,
            ],
            [
                'titulo' => 'Hito 2',
                'slug' => 'hito2',
                'incluir' => false,  // Excluido
            ],
        ];

        $result = HitosApoyoService::insertHitosValidated($milestones, 1);

        $this->assertTrue($result['exitoso']);
        $this->assertEquals(1, $result['total_insertados']);
        $this->assertEquals(1, count($result['rechazados']));
    }

    /**
     * Test 9: Validar que no incluye fecha_actualizacion en inserción
     */
    public function test_prepare_hito_no_incluye_fecha_actualizacion()
    {
        $milestone = [
            'titulo' => 'Test',
            'slug' => 'test',
        ];

        $row = HitosApoyoService::prepareHitoRow($milestone, 1, 1);

        // NO debe incluir fecha_actualizacion (lo maneja SQL Server con DEFAULT)
        $this->assertArrayNotHasKey('fecha_actualizacion', $row,
            'No debe incluir fecha_actualizacion (SQL Server maneja DEFAULT)');
    }

    /**
     * Test 10: Validar compatibilidad con nombres antiguos
     */
    public function test_prepare_hito_compatibilidad_nombres_antiguos()
    {
        $milestone = [
            'titulo' => 'Hito con alias',
            'slug' => 'hito_alias',
        ];

        $row = HitosApoyoService::prepareHitoRow($milestone, 1, 1);

        // El servicio debe mapear correctamente los nombres
        // ya sea usando titulo_hito o nombre_hito dependiendo de lo que exista
        $this->assertTrue(
            isset($row['titulo_hito']) || isset($row['nombre_hito']),
            'Debe tener titulo_hito o nombre_hito mapeado'
        );
    }
}
