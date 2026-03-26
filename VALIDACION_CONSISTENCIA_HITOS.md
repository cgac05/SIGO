# 🔐 Validación y Consistencia de Hitos_Apoyo

**Fecha**: 25 de Marzo de 2026  
**Estado**: ✅ Implementado  
**Propósito**: Garantizar que NO se envíen campos vacíos/NULL innecesarios y validar consistencia con modelo de datos

---

## 📋 Problema Original

El código anterior insertaba hitos con problemas:

```php
// ❌ PROBLEMA 1: Envía NULL innecesariamente
'fecha_actualizacion' => null,  // Causa error en SQL Server

// ❌ PROBLEMA 2: Puede enviar datos inconsistentes
'fecha_inicio' => $start,  // Podría ser NULL sin validación
'fecha_fin' => $end,       // Podría ser NULL sin validación

// ❌ PROBLEMA 3: No valida que campos existan en BD
if (Schema::hasColumn('Hitos_Apoyo', 'titulo_hito')) {
    $row['titulo_hito'] = $title;  // ¿Qué pasa si no existe?
}
```

---

## ✅ Solución Implementada

### 1. **Servicio: `HitosApoyoService`**

**Ubicación**: `app/Services/HitosApoyoService.php`

**Responsabilidades**:
- ✅ Validar estructura de BD contra esquema esperado
- ✅ Completar campos TODOS los campos requeridos
- ✅ Mapear nombres antiguos a nuevas columnas
- ✅ Validar que datos están es consistencia con BD
- ✅ Realizar inserciones con validación completa

### 2. **Comando Artisan: `validate:hitos-structure`**

**Ubicación**: `app/Console/Commands/ValidateHitosStructure.php`

**Uso**:
```bash
php artisan validate:hitos-structure
```

**Salida**:
```
🔍 Validando estructura de tabla Hitos_Apoyo...

✅ Tabla existe

📋 COLUMNAS REQUERIDAS:
  ✓ fk_id_apoyo [int] (NOT NULL)
  ✓ titulo_hito [nvarchar] (NOT NULL)
  ✓ fecha_creacion [datetime2] (NOT NULL)
  ✓ es_base [bit] (NOT NULL)
  ✓ activo [bit] (NOT NULL)

📝 COLUMNAS OPCIONALES:
  ○ slug_hito [nvarchar] (NULL)
  ○ fecha_inicio [date] (NULL)
  ○ fecha_fin [date] (NULL)
  ○ orden [smallint] (NOT NULL)
  ○ fecha_actualizacion [datetime2] (NULL)

✅ Estructura validada exitosamente
```

---

## 🔧 Cómo Integrar en ApoyoController

### ANTES (Código actual - problemático):

```php
// app/Http/Controllers/ApoyoController.php línea ~180

private function saveApoyoMilestones($apoyoId, $milestones): void
{
    // ... código de normalización ...

    if (! empty($rows)) {
        DB::table('Hitos_Apoyo')->insert($rows);  // ❌ Sin validación
    }
}
```

### DESPUÉS (Código mejorado - usar servicio):

```php
// app/Http/Controllers/ApoyoController.php

use App\Services\HitosApoyoService;

private function saveApoyoMilestones($apoyoId, $milestones): void
{
    // Usar el servicio que valida y completa todos los campos
    $result = HitosApoyoService::insertHitosValidated($milestones, $apoyoId);

    // Verificar resultado
    if (!$result['exitoso']) {
        throw new \Exception(
            'Error al insertar hitos: ' . implode(', ', $result['errores'])
        );
    }

    // Registrar en logs
    Log::info('Hitos insertados', [
        'apoyo_id' => $apoyoId,
        'total' => $result['total_insertados'],
        'rechazados' => count($result['rechazados']),
    ]);
}
```

---

## 🎯 Validaciones Implementadas

### Validación #1: Estructura de Tabla

```php
$schemaValidation = HitosApoyoService::validateTableSchema();

// Verifica:
// ✓ Tabla existe
// ✓ Columnas requeridas existen
// ✓ Columnas no permiten NULL donde no deben
// ✓ Tipos de datos correctos
```

### Validación #2: Preparación de Fila

```php
$row = HitosApoyoService::prepareHitoRow($milestone, $apoyoId, $order);

// Verifica:
// ✓ Título no está vacío (REQUERIDO)
// ✓ Fechas se parsean correctamente
// ✓ Slug se procesa correctamente
// ✓ TODOS los campos se incluyen (no NULL innecesarios)
// ✓ Se aplican aliases de compatibilidad
```

### Validación #3: Consistencia Global

```php
$consistency = HitosApoyoService::validateConsistency($rowsToInsert);

// Verifica:
// ✓ Cada fila tiene título_hito y fk_id_apoyo
// ✓ Campos NULL solo en columnas que permiten NULL
// ✓ Solo se usan columnas que existen en BD
// ✓ Tipos de datos son válidos
```

---

## 📊 Estructura de Filas - Antes vs Después

### ❌ ANTES - Incompleto y problemático:

```php
[
    'fk_id_apoyo' => 1,
    'fecha_inicio' => null,      // ❌ NULL sin validación
    'fecha_fin' => null,         // ❌ NULL sin validación
    'es_base' => 1,
    'activo' => 1,
    'fecha_creacion' => now(),
    'fecha_actualizacion' => null,  // ❌ Causa error
    'slug_hito' => 'MIGRACION',
    'titulo_hito' => 'Inicio de publicación',
    // ⚠️ Falta 'orden' en algunos casos
]
```

### ✅ DESPUÉS - Completo y validado:

```php
[
    'fk_id_apoyo' => 1,           // ✓ Siempre presente
    'titulo_hito' => 'Inicio...',  // ✓ Siempre presente
    'slug_hito' => 'MIGRACION',   // ✓ Validado
    'fecha_inicio' => null,       // ✓ NULL validado (permitido)
    'fecha_fin' => null,          // ✓ NULL validado (permitido)
    'es_base' => 1,               // ✓ Booleano convertido a INT
    'activo' => 1,                // ✓ Siempre 1 en inserción
    'orden' => 1,                 // ✓ SIEMPRE presente
    'fecha_creacion' => now(),    // ✓ Timestamp válido
    // ⚠️ NO incluir fecha_actualizacion (SQL Server usa DEFAULT)
]
```

---

## 🔍 Métodos Disponibles

### 1. `validateTableSchema(): array`
Valida la estructura actual de la tabla contra esquema esperado

```php
$report = HitosApoyoService::validateTableSchema();

// Retorna:
[
    'tabla_existe' => true,
    'columnas_requeridas' => [...],
    'columnas_opcionales' => [...],
    'columnas_extra' => [...],
    'inconsistencias' => [],  // Array de errores
]
```

### 2. `prepareHitoRow(array, int, int): ?array`
Prepara y valida una fila individual

```php
$row = HitosApoyoService::prepareHitoRow($milestone, $apoyoId, $order);

// Retorna array completo y validado, o null si falla
```

### 3. `validateConsistency(array): array`
Valida que filas estén consistentes con BD

```php
$validation = HitosApoyoService::validateConsistency($rowsToInsert);

// Retorna:
[
    'es_valido' => true/false,
    'errores' => [],
    'advertencias' => [],
]
```

### 4. `insertHitosValidated(array, int): array`
Inserta hitos con validación completa ANTES

```php
$result = HitosApoyoService::insertHitosValidated($milestones, $apoyoId);

// Retorna:
[
    'exitoso' => true/false,
    'total_intentados' => 5,
    'total_insertados' => 4,
    'rechazados' => ['Hito 2: No tiene título'],
    'errores' => [],
]
```

---

## 🚀 Pasos de Implementación

### Paso 1: Validar Estructura Actual

```bash
php artisan validate:hitos-structure
```

**Esperar**: ✅ "Estructura validada exitosamente"

### Paso 2: Integrar Servicio en ApoyoController

Reemplazar en `saveApoyoMilestones()`:

```php
// ❌ Viejo:
DB::table('Hitos_Apoyo')->insert($rows);

// ✅ Nuevo:
$result = HitosApoyoService::insertHitosValidated($normalizedMilestones, $apoyoId);
if (!$result['exitoso']) {
    throw new \Exception('Error: ' . implode(', ', $result['errores']));
}
```

### Paso 3: Probar Inserción

```bash
# Crear un nuevo apoyo con hitos
# Debería completar TODOS los campos sin NULL innecesarios
```

### Paso 4: Verificar Datos en BD

```sql
-- Verificar que no hay NULL donde no debería
SELECT 
    id_hito,
    fk_id_apoyo,
    titulo_hito,
    orden,
    es_base,
    activo,
    fecha_creacion,
    fecha_actualizacion
FROM Hitos_Apoyo
ORDER BY id_hito DESC
LIMIT 10;
```

---

## 📝 Logging

El servicio registra automáticamente:

```php
// Exitoso
Log::info('Hitos insertados exitosamente', [
    'apoyo_id' => 1,
    'cantidad' => 5,
]);

// Error
Log::error('Error insertando hitos', [
    'apoyo_id' => 1,
    'error' => 'No tiene título',
    'rows' => $rowsToInsert,
]);

// Advertencias
Log::warning('Hito sin título rechazado', [
    'milestone' => $data,
]);
```

---

## ✨ Beneficios

✅ **Sin NULL innecesarios**: Todos los campos requeridos se completan  
✅ **Validación previa**: Se valida ANTES de insertar, no después  
✅ **Consistencia garantizada**: Los datos match con la estructura de BD  
✅ **Errores claros**: Mensajes de error específicos y trazables  
✅ **Auditoría**: Logs completos de inserciones y rechazos  
✅ **Mantenibilidad**: Código centralizado y reutilizable  
✅ **Escalabilidad**: Fácil agregar nuevas validaciones  

---

**Última actualización**: 25 de Marzo de 2026  
**Versión**: 1.0  
**Estado**: ✅ Listo para Implementar
