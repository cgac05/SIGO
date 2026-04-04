# 🚀 PRÓXIMOS PASOS - Fase 7B: Gestión de Facturas

**Estado Actual:** Fase 7A ✅ COMPLETADA (commit 1f908d0)  
**Siguiente:** Fase 7B - Gestión integral de facturas de compra  
**Estimado:** 3-4 horas de desarrollo

---

## 🎯 Objetivo General

Implementar un sistema completo de gestión de facturas de compra para apoyos tipo "Especie":
- Cargar facturas (archivo PDF/imagen)
- Registrar detalles por línea (cantidad, costo unitario)
- Actualizar automáticamente inventario
- Rastrear toda compra con auditoría completa

---

## 📋 Tasks de Fase 7B (Prioridad)

### TAREA 1: Crear FacturaCompraController [HIGH]

**Archivo:** `app/Http/Controllers/FacturaCompraController.php`

**Métodos Requeridos:**

```php
class FacturaCompraController extends Controller
{
    public function __construct(GestionInventarioService $inventarioService) { }
    
    // GET /facturas/{id_apoyo} - Lista facturas del apoyo
    public function index($id_apoyo): View
    
    // GET /facturas/{id_apoyo}/crear - Mostrar formulario
    public function create($id_apoyo): View
    
    // POST /facturas - Guardar nueva factura
    public function store(Request $request): JsonResponse
    
    // GET /facturas/{id} - Ver detalles factura
    public function show($id): View
    
    // GET /facturas/{id}/editar - Formulario editar
    public function edit($id): View
    
    // PUT /facturas/{id} - Actualizar factura
    public function update(Request $request, $id): JsonResponse
    
    // DELETE /facturas/{id} - Eliminar factura
    public function destroy($id): JsonResponse
    
    // POST /facturas/{id}/recibir - Marcar como recibida
    public function marcarRecibida($id): JsonResponse
}
```

**Validaciones Clave:**
- numero_factura: UNIQUE (no duplicar)
- monto_total MUST = sum(detalle.costo_total)
- Subir archivo PDF/JPG máx 5MB
- Fecha compra no puede ser futura

**GIT COMMIT:** "feat: Create FacturaCompraController with full CRUD"

---

### TAREA 2: Vistas para Gestión de Facturas [HIGH]

**Archivos a Crear:**

#### 2a. resources/views/apoyos/facturas/index.blade.php
```blade
<!-- Shows:
- Tabla de facturas (numero, proveedor, fecha, monto, estado)
- Botón agregar factura
- Botón ver detalles
- Botón eliminar
- Status badge: "Recibida" (verde), "Parcial" (amarillo), "Rechazada" (rojo)
- Empty state si no hay facturas
-->
```

#### 2b. resources/views/apoyos/facturas/create.blade.php
```blade
<!-- Shows:
- Formulario multipart (upload archivo)
- Campos: numero_factura, nombre_proveedor, rfc_proveedor, fecha_compra
- Tabla dinámica de detalles (add/remove filas)
  ├─ Seleccionar material (dropdown)
  ├─ Cantidad
  ├─ Costo unitario
  └─ Subtotal (calculado)
- Total factura (autocalculado)
- Botón "Registrar Factura"
-->
```

#### 2c. resources/views/apoyos/facturas/show.blade.php
```blade
<!-- Shows:
- Header: numero_factura, proveedor, fecha, estado
- Archivo factura (embed si es PDF, img si JPG)
- Tabla detalles: Material, Cantidad, Costo Unit., Subtotal
- Resumen: Monto Total, Impuestos (si aplica), Total
- Timeline: Registrada → Recibida → [Procesada]
- Acciones: Editar, Eliminar, Marcar Recibida
-->
```

**GIT COMMIT:** "feat: Add factura management views"

---

### TAREA 3: Rutas para Facturas [MEDIUM]

**Archivo:** `routes/web.php` (section apoyos)

```php
Route::prefix('apoyos/{id_apoyo}/facturas')->group(function () {
    Route::get('/', 'FacturaCompraController@index')->name('facturas.index');
    Route::get('crear', 'FacturaCompraController@create')->name('facturas.create');
    Route::post('/', 'FacturaCompraController@store')->name('facturas.store');
    Route::get('{factura_id}', 'FacturaCompraController@show')->name('facturas.show');
    Route::get('{factura_id}/editar', 'FacturaCompraController@edit')->name('facturas.edit');
    Route::put('{factura_id}', 'FacturaCompraController@update')->name('facturas.update');
    Route::delete('{factura_id}', 'FacturaCompraController@destroy')->name('facturas.destroy');
    Route::post('{factura_id}/recibir', 'FacturaCompraController@marcarRecibida')
        ->name('facturas.marcar-recibida');
});
```

**GIT COMMIT:** "feat: Add factura routes"

---

### TAREA 4: Integración con GestionInventarioService [MEDIUM]

**Método a Implementar en Service:**

```php
// app/Services/GestionInventarioService.php

public function crearFacturaYRegistrarCompra(
    string $numeroFactura,
    string $nombreProveedor,
    string $rfcProveedor,
    Carbon $fechaCompra,
    float $montoTotal,
    array $detalles, // [{ id_material, cantidad, costo_unitario }, ...]
    string $rutaArchivo,
    ?string $observaciones,
    int $registradoPor
): FacturaCompra
{
    DB::beginTransaction();
    try {
        // 1. Validar no exista factura con ese numero
        if (FacturaCompra::where('numero_factura', $numeroFactura)->exists()) {
            throw new \Exception("Factura #$numeroFactura ya existe");
        }
        
        // 2. Crear FacturaCompra
        $factura = FacturaCompra::create([
            'numero_factura' => $numeroFactura,
            'nombre_proveedor' => $nombreProveedor,
            'rfc_proveedor' => $rfcProveedor,
            'fecha_compra' => $fechaCompra,
            'monto_total' => $montoTotal,
            'archivo_factura' => $rutaArchivo,
            'observaciones' => $observaciones,
            'registrado_por' => $registradoPor,
            'estado' => 'Recibida' // Por defecto
        ]);
        
        // 3. Crear detalles + actualizar inventario
        $totalCalculado = 0;
        foreach ($detalles as $detalle) {
            $costo_total = $detalle['cantidad'] * $detalle['costo_unitario'];
            $totalCalculado += $costo_total;
            
            DetalleFacturaCompra::create([
                'fk_id_factura' => $factura->id_factura,
                'fk_id_inventario' => $detalle['id_material'],
                'cantidad_comprada' => $detalle['cantidad'],
                'costo_unitario' => $detalle['costo_unitario'],
            ]);
            
            // 4. Actualizar stock en BD_Inventario + movimientos
            $this->actualizarStockEntrada(
                $detalle['id_material'],
                $detalle['cantidad'],
                $factura->id_factura,     // Vincular a factura
                $registradoPor
            );
        }
        
        // 5. Validar total
        if (abs($totalCalculado - $montoTotal) > 0.01) {
            throw new \Exception("Monto total no coincide: Calculado=$totalCalculado, Registrado=$montoTotal");
        }
        
        // 6. Log auditoría
        $this->auditarMovimiento([
            'tipo_evento' => 'FACTURA_REGISTRADA',
            'id_factura' => $factura->id_factura,
            'realizado_por' => $registradoPor,
            'datos' => $factura->toArray()
        ]);
        
        DB::commit();
        return $factura;
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

// Auxiliar: Actualizar stock por entrada
private function actualizarStockEntrada(
    int $id_material,
    float $cantidad,
    int $id_factura,
    int $id_usuario
): void
{
    // Actualizar BD_Inventario
    DB::table('BD_Inventario')
        ->where('fk_id_material', $id_material)
        ->increment('stock_actual', $cantidad);
    
    // Registrar movimiento
    MovimientoInventario::create([
        'fk_id_material' => $id_material,
        'tipo_movimiento' => 'ENTRADA',
        'cantidad' => $cantidad,
        'fk_id_factura' => $id_factura,
        'realizado_por' => $id_usuario,
        'razon' => 'Compra por factura',
        'fecha_movimiento' => now()
    ]);
}
```

---

### TAREA 5: Validación en Aprobaciones de Solicitudes [MEDIUM]

**Archivo:** `app/Http/Controllers/SolicitudProcesoController.php`

**Cambio en firmaDirectiva():**

```php
public function firmaDirectiva(Request $request, $id)
{
    // ... auth/validation ...
    
    $solicitud = Solicitud::findOrFail($id);
    $apoyo = $solicitud->apoyo;
    
    // NUEVO: Si apoyo es tipo Especie, validar stock disponible
    if ($apoyo->tipo_apoyo === 'Especie') {
        $inventario = DB::table('BD_Inventario')
            ->where('fk_id_apoyo', $apoyo->id_apoyo)
            ->first();
        
        if (!$inventario || $inventario->stock_actual <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Apoyo Especie: Stock insuficiente para aprobar'
            ], 422);
        }
    }
    
    // NUEVO: Si apoyo es tipo Económico, validar presupuesto
    if ($apoyo->tipo_apoyo === 'Económico') {
        if (!$this->inventarioService->validarPresupuestoDisponible(
            $apoyo->id_categoria,
            $solicitud->monto_solicitado
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Presupuesto insuficiente en categoría'
            ], 422);
        }
    }
    
    // ... resto del código de aprobación ...
}
```

---

### TAREA 6: Testing End-to-End [MEDIUM]

**Archivo:** `tests/Feature/FacturaCompraTest.php`

```php
class FacturaCompraTest extends TestCase
{
    /** @test */
    public function registrar_factura_exitosamente()
    {
        $apoyo = Apoyo::factory()->state(['tipo_apoyo' => 'Especie'])->create();
        $usuario = Usuario::factory()->directivo()->create();
        $this->actingAs($usuario);
        
        $response = $this->post(route('facturas.store', $apoyo->id_apoyo), [
            'numero_factura' => 'FAC-2026-001',
            'nombre_proveedor' => 'Distribuidora XYZ',
            'rfc_proveedor' => 'DXY123456ABC',
            'fecha_compra' => now()->subDay(),
            'monto_total' => 5000,
            'detalles' => [
                [
                    'id_material' => 1,
                    'cantidad' => 100,
                    'costo_unitario' => 50
                ]
            ],
            'archivo_factura' => UploadedFile::fake()->create('factura.pdf')
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('facturas_compra', [
            'numero_factura' => 'FAC-2026-001'
        ]);
    }
    
    /** @test */
    public function validar_presupuesto_en_aprobacion()
    {
        // Test que presupuesto se valida correctamente
    }
    
    // +5 tests más
}
```

**Run:** `php artisan test tests/Feature/FacturaCompraTest.php`

---

### TAREA 7: Dashboard Presupuesto [LOW - Enhancement]

**Mostrar en dashboard:**
- Categoría presupuestaria con barra de progreso
- Total reservado vs aprobado
- Apoyos bajo presupuesto (alerts)
- Últimas facturas registradas

---

## ⏱️ Estimación de Tiempo

| Tarea | Estimado | Notas |
|-------|----------|-------|
| 1. Controller | 1.5h | Lógica CRUD + validaciones |
| 2. Vistas | 1h | 3 templates HTMLBladeJS |
| 3. Rutas | 15m | Copiar/pegar pattern |
| 4. Service Methods | 1h | Integración BD transac |
| 5. Validación | 30m | En SolicitudProcesoController |
| 6. Tests | 1h | 6-8 test cases |
| 7. Dashboard | 45m | Optional, puede hacerse después |
| **TOTAL** | **~5-6 horas** | Con pausas/debug |

---

## 📝 Checklist para Ejecución

```
ANTES DE EMPEZAR:
□ Verificar que form.blade.php funciona al 100%
□ Probar crear apoyo Económico + Especie en navegador
□ Confirmar presupuesto_apoyos se crea automáticamente
□ Backup BD (si estás en dev local es OK)

DURANTE DESARROLLO:
□ Crear FacturaCompraController
□ Escribir TAREA 1 tests primero (TDD)
□ Crear vistas una por una (create, index, show)
□ Integrar con GestionInventarioService
□ Testing manual: upload factura, verificar BD_Inventario actualizado
□ Testing presupuesto en aprobación

DESPUÉS:
□ Run full test suite: php artisan test
□ Verificar git status: git status
□ Commit todo: git add -A && git commit -m "feat: Complete factura management (7B)"
□ Push a repo si tienes
```

---

## 🎓 Conocimiento Previo Necesario

- ✅ Vistas Blade (ya lo has hecho)
- ✅ Controllers + Eloquent (ya lo has hecho)
- ✅ Transacciones BD (ya lo has hecho en presupuestación)
- ✅ Validaciones (ya lo has hecho en store())
- ✅ Upload de archivos (revisar Laravel docs storage)
- ✅ Testing con PHPUnit (ya tenemos tests)

---

## 📚 Recursos Útimos

**Storage de archivos:**
```php
// Guardar archivo
$path = $request->file('archivo_factura')->store('facturas-compra');
$factura->archivo_factura = $path;

// Obtener URL
url(Storage::url($factura->archivo_factura))
```

**Validar PDF/JPG:**
```php
'archivo_factura' => 'required|file|mimes:pdf,jpeg,jpg,png|max:5120'
```

**Download archivo:**
```php
return Storage::download($factura->archivo_factura);
```

---

## 🚨 Consideraciones Especiales

1. **Almacenamiento de Archivos**
   - Usar `storage/app/facturas-compra/` para archivos
   - Considerar virus scan si es producción
   - Linked via symbolic link: `php artisan storage:link`

2. **Presupuesto es INMUTABLE una vez aprobado**
   - No se puede cambiar después de que hay solicitudes aprobadas
   - Prevención vs data integrity

3. **Facturas son AUDITABLES**
   - Cada entrada/salida = movimiento_inventario
   - Cada factura = movimientos_presupuestarios (si aplica)
   - Rastrabilidad completa M de compliance LGPDP

4. **Estados de Factura**
   - "Recibida" (inicial)
   - "Parcial" (recepción parcial)
   - "Rechazada" (no conforme)
   - "Devuelta" (devolución)
   → Permite workflows complejos

---

## 🎯 Success Criteria (Fase 7B)

✅ Cuando esto esté completo:

```
1. Admin puede cargar factura en apoyo tipo Especie
2. Factura vincula a inventario_material (rastreabilidad)
3. Stock se actualiza automáticamente en BD_Inventario
4. Movimientos quedan registrados en audit trail
5. Presupuesto se valida en aprobación de solicitudes
6. Tests pasan al 100%
7. Todo funciona en navegador (responsivo)
```

---

**¿Listo para empezar Fase 7B?** 🚀

Avísame cuando estés listo y hacemos un resumen de implementación paso a paso.
