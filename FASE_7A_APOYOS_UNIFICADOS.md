# 📋 Fase 7A - Unificación de Formularios de Apoyos + Presupuestación

**Fecha:** 3 de Abril de 2026  
**Commit:** `1f908d0`  
**Estado:** ✅ COMPLETADO

---

## 🎯 Objetivos Logrados

### ✅ 1. Vista Unificada (form.blade.php)
- **Descripción:** Reemplaza `create.blade.php` y `edit.blade.php` con una sola vista multipropósito
- **Detección automática:** Determina modo crear/editar mediante `?mode=create|edit` o presencia de `$apoyo`
- **Beneficios:**
  - ✅ Errores de diseño corregidos (columnas desalineadas)
  - ✅ Mantenimiento único (futuros cambios en 1 archivo)
  - ✅ Código DRY (Don't Repeat Yourself)
  
**Ubicación:** `resources/views/apoyos/form.blade.php` (1,100+ líneas)

---

### ✅ 2. Integración de Presupuestación

#### 2a. ApoyoController Actualizado

**Cambios en `create()`:**
```php
// Agregar:
$categorias = PresupuestoCategoria::select('id_categoria', 'nombre', 'disponible')->activas()->get();
return view('apoyos.form', compact(..., 'categorias'));
```

**Cambios en `edit()`:**
```php
// Agregar:
$categorias = PresupuestoCategoria::select('id_categoria', 'nombre', 'disponible')->activas()->get();
return view('apoyos.form', compact(..., 'categorias'));
```

**Enhancements en `store()`:**
- ✅ Validar presupuesto disponible ANTES de crear apoyo
- ✅ Crear automáticamente reserva en `presupuesto_apoyos`
- ✅ Support para campo `id_categoria` (FK a categoría presupuestaria)
- ✅ Campos nuevos: `unidad_medida`, `costo_unitario`
- ✅ Crear registro en `inventario_material` para apoyos tipo "Especie"

---

### ✅ 3. Gestión de Inventario (Apoyos tipo Especie)

#### 3a. Service GestionInventarioService

**Ubicación:** `app/Services/GestionInventarioService.php`

**Métodos principales:**

```php
// Crear factura de compra y registrar entrada
crearFacturaYRegistrarCompra(
    numeroFactura, 
    nombreProveedor, 
    montoTotal, 
    registradoPor,
    detalles[], // array con cantidad y costo_unitario
    rutaArchivo, 
    observaciones
): FacturaCompra

// Validar presupuesto disponible
validarPresupuestoDisponible(idCategoria, montoRequerido): array

// Reservar presupuesto
reservarPresupuestoApoyo(idApoyo, idCategoria, monto, usuarioId): PresupuestoApoyo

// Liberar presupuesto (en caso de rechazo)
liberarPresupuestoApoyo(idPresupuestoApoyo, usuarioId): void

// Obtener resumen de inventario
obtenerResumenInventarioApoyo(idApoyo): array
```

---

### ✅ 4. Modelos Eloquent Nuevos

#### 4a. FacturaCompra
**Archivo:** `app/Models/FacturaCompra.php`

```php
Relaciones:
- registradoPor() → Usuario (quién registró la factura)
- actualizadoPor() → Usuario (quién la actualizó)
- detalles() → HasMany(DetalleFacturaCompra)

Scopes:
- recientes()
- porProveedor(string)
- pendientesRecepcion()

Atributos principales:
- numero_factura (UNIQUE)
- nombre_proveedor
- rfc_proveedor
- fecha_compra
- fecha_recepcion
- monto_total
- moneda (MXN por defecto)
- estado (Recibida, Parcial, Rechazada, Devuelta)
- resultado_factura (ruta archivo PDF/imagen)
```

#### 4b. DetalleFacturaCompra
**Archivo:** `app/Models/DetalleFacturaCompra.php`

```php
Relaciones:
- factura() → BelongsTo(FacturaCompra)
- inventario() → BelongsTo(InventarioMaterial)

Métodos:
- getCostoTotal(): float

Atributos:
- cantidad_comprada
- costo_unitario
- costo_total (GENERATED - automático)
- lote_numero
- fecha_vencimiento
- observaciones
```

#### 4c. InventarioMaterial
**Archivo:** `app/Models/InventarioMaterial.php`

```php
Relaciones:
- apoyo() → BelongsTo(Apoyo)
- facturasCompra() → HasMany(DetalleFacturaCompra)
- movimientos() → HasMany(MovimientoInventario)

Scopes:
- delApoyo(int)
- activos()
- stockBajo()

Métodos:
- tieneStock(float): bool
- cantidadDisponible(): float
- necesitaReorden(): bool

Atributos:
- codigo_material (UNIQUE)
- nombre_material
- descripcion
- unidad_medida
- cantidad_actual
- cantidad_minima
- costo_unitario
- proveedor_principal
```

#### 4d. MovimientoInventario (actualizado)
**Archivo:** `app/Models/MovimientoInventario.php`

```php
Relaciones:
- inventario() → BelongsTo(InventarioMaterial)
- factura() → BelongsTo(FacturaCompra) [NUEVA]
- salida() → BelongsTo(SalidaBeneficiario)
- usuario() → BelongsTo(Usuario)

Scopes:
- entradas()
- salidas()
- recientes()

Novedades:
- Campo fk_id_factura agregado (vincula a facturas_compra)
- Permite rastrear qué factura generó el movimiento
```

---

### ✅ 5. Tablas SQL Nuevas

**Archivo:** `database/sql/create_facturas_compra.sql`

#### 5a. facturas_compra
```sql
Columnas principales:
- id_factura (PK, IDENTITY)
- numero_factura (UNIQUE)
- nombre_proveedor
- rfc_proveedor
- fecha_compra (DEFAULT GETDATE())
- fecha_recepcion
- monto_total (MONEY)
- moneda (DEFAULT 'MXN')
- estado (Recibida, Parcial, Rechazada, Devuelta)
- archivo_factura (ruta a PDF/imagen)
- observaciones (NVARCHAR MAX)
- registrado_por (FK → Usuarios)
- actualizado_por (FK → Usuarios, NULL)
- created_at, updated_at

Índices:
- IX_facturas_numero
- IX_facturas_fecha
- IX_facturas_proveedor
```

#### 5b. detalle_facturas_compra
```sql
Columnas principales:
- id_detalle (PK, IDENTITY)
- fk_id_factura (FK → facturas_compra, CASCADE)
- fk_id_inventario (FK → inventario_material)
- cantidad_comprada (DECIMAL)
- costo_unitario (MONEY)
- costo_total (GENERATED - automático)
- lote_numero
- fecha_vencimiento (DATE)
- observaciones

Índices:
- IX_detalle_factura
- IX_detalle_inventario
```

#### 5c. Actualización de movimientos_inventario
```sql
Columna agregada (si no existe):
- fk_id_factura INT NULL (FK → facturas_compra, SET NULL)

Permite rastrear origen de cada movimiento de inventario
```

---

## 📊 Cambios en ApoyoController

| Método | Cambios |
|--------|---------|
| `create()` | ✅ Pasar `$categorias` |
| `store()` | ✅ Validar presupuesto, crear en `presupuesto_apoyos` |
| `edit()` | ✅ Pasar `$categorias` |
| `update()` | ✅ Permitir cambio de categoría/presupuesto |

---

## ⚙️ Flujo de Negocio (Económico)

```
1. Admin accede a crear apoyo
   ↓
2. Selecciona tipo = "Económico" + categoría presupuestaria
   ↓
3. Ingresa monto a asignar
   ↓
4. submit → ApoyoController::store()
   ↓
5. Validar presupuesto disponible
   ├─ SI: Continuar
   └─ NO: Error 422 + mensaje presupuesto insuficiente
   ↓
6. DB::transaction:
   ├─ Crear en Apoyos
   ├─ Crear en BD_Finanzas
   ├─ Reservar en presupuesto_apoyos
   ├─ Crear movimiento en movimientos_presupuestarios
   └─ Actualizar disponible en categoría
   ↓
7. Registrar documentos requeridos + hitos
   ↓
8. ✅ Success: "Apoyo registrado correctamente"
```

---

## ⚙️ Flujo de Negocio (Especie)

```
1. Admin accede a crear apoyo tipo "Especie"
   ↓
2. Selecciona tipo = "Especie"
   ↓
3. Ingresa:
   - stock_inicial
   - unidad_medida (pieza, kit, paquete, etc.)
   - costo_unitario (opcional, para valuación)
   ↓
4. submit → ApoyoController::store()
   ↓
5. DB::transaction:
   ├─ Crear en Apoyos
   ├─ Crear en BD_Inventario
   └─ Crear en inventario_material (con código MAT-{id_apoyo})
   ↓
6. Registrar documentos requeridos + hitos
   ↓
7. ✅ Success: "Apoyo registrado correctamente"

Luego (próximas fases):
→ Admin puede cargar facturas de compra
→ Sistema rastrea cada entrada/salida de inventario
→ Movimientos quedan registrados en movimientos_inventario
```

---

## 🔄 Próximos Pasos (Fase 7B+)

- [ ] **Controller FacturaCompraController** - CRUD para facturas
- [ ] **Vista para cargar facturas** - UI para registrar compras
- [ ] **Validación de inventario** - En salidas a beneficiarios
- [ ] **Testing end-to-end** - Crear/editar apoyos + presu/inventario
- [ ] **Documentación de API** - POST/PUT para facturas
- [ ] **Dashboard presupuesto** - Mostrar reservas y disponible
- [ ] **Alertas presupuesto** - Notificar cuando toque límite

---

## 📝 Comandos para ejecutar SQL

Si necesitas crear las tablas manualmente (sin migrations Laravel):

```bash
# Conectarse a BD_SIGO en SQL Server
cd c:\xampp\htdocs\SIGO

# Ejecutar script de creación
sqlcmd -S (local) -d BD_SIGO -i database/sql/create_facturas_compra.sql
```

---

## ✅ Validación de Cambios

```bash
# Ver cambios en git
git show 1f908d0 --stat

# Ver archivos creados/modificados
git show 1f908d0 --name-status

# Tests pendientes
php artisan test Feature/ApoyoFormTest
```

---

## 📌 Notas Importantes

1. **Vistas antiguas NO se eliminaron** - `create.blade.php` y `edit.blade.php` siguen existiendo como backup
   - **TODO: Eliminar después de confirmar que form.blade.php funcione correctamente**

2. **Presupuesto se valida en store()** no en UI
   - La validación es en el servidor (no confiar en frontend)
   - Se devuelve 422 si presupuesto insuficiente

3. **Transacciones SQL** en store() y update()
   - Si falla cualquier INSERT, todo se revierte (ROLLBACK)
   - Muy importante para integridad de datos

4. **GestionInventarioService** es reutilizable
   - Se puede llamar desde otros controllers (solicitudes, salidas, etc.)
   - Abstrae la lógica de presupuesto/inventario

---

## 🎓 Lecciones Aprendidas

- ✅ Vistas unificadas reducen duplicación y manutención
- ✅ Services separan lógica de controllers
- ✅ Transacciones son críticas para operaciones multi-tabla
- ✅ Validaciones deben estar en múltiples niveles (frontend + backend)
- ✅ Modelos Eloquent hacen el código más legible y mantenible

---

**Estado Final:** ✅ LISTO PARA TESTING

**Próximo:** Crear tests end-to-end y luego Fase 7B (Real-time features)
