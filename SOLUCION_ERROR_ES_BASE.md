# 🔧 Solución: Errores en Tabla Hitos_Apoyo

**Fecha**: 25 de Marzo de 2026  
**Errores Corregidos**: 
- ✅ Columna `es_base` no existe
- ✅ Columna `fecha_actualizacion` no acepta NULL  

**Tabla Afectada**: `Hitos_Apoyo`

---

## 🔍 Causas del Error

### Problema #1: Falta columna `es_base`
La migración Laravel usaba **sintaxis MySQL**:
```php
$table->unsignedInteger('fk_id_apoyo');  // ❌ MySQL
$table->unsignedSmallInteger('orden');   // ❌ MySQL
```

SQL Server **no soporta UNSIGNED**:
- Migración falló silenciosamente
- Tabla se creó **incompleta** o **manual**
- Falta la columna `es_base`
- Código intenta insertar en columna inexistente → **Error**

### Problema #2: `fecha_actualizacion` no permite NULL
El código inserta `'fecha_actualizacion' => null` (línea 149 de ApoyoController.php), pero:
- La tabla tiene `fecha_actualizacion` como `NOT NULL`
- SQL Server rechaza NULL → **Error**
- **Solución**: Cambiar a `DATETIME2 NULL`

---

## ✅ Solución (2 Opciones)

### **Opción A: SQL Server Direct (Recomendado - 5 minutos)**

1. Abre **SQL Server Management Studio**
2. Conéctate a `BD_SIGO`
3. Copia y ejecuta **TODO EL CONTENIDO** de:

```sql
database/sql/fix_hitos_apoyo_es_base.sql
```

**Qué hace el script**:
- ✅ Verifica si `es_base` existe → La agrega si falta
- ✅ Verifica si `titulo_hito` existe → La agrega si falta
- ✅ Remueve constraint DEFAULT de `fecha_actualizacion`
- ✅ Cambia `fecha_actualizacion` a **DATETIME2 NULL**
- ✅ Agrega `slug_hito` si falta
- ✅ Muestra estructura final validada

**Resultado Esperado**:
```
✓ [1/4] Columna es_base ya existe
✓ [2/4] Columna titulo_hito ya existe
✓ [3/4] fecha_actualizacion ahora permite NULL
✓ [4/4] Columna slug_hito ya existe

✅ Corrección completada.
```

---

### **Opción B: Laravel Migration (Alternativa)**

```bash
cd C:\xampp\htdocs\SIGO

# 1. Limpiar caché
php artisan config:clear

# 2. Ejecutar migraciones
php artisan migrate
```

**Nota**: Esto solo funciona si removemos la migración fallida anterior.

---

## 📋 Cambios Realizados

### Migración Laravel (Corregida)

#### ❌ ANTES (Sintaxis MySQL - No funciona en SQL Server)
```php
$table->unsignedInteger('fk_id_apoyo');              // ❌ MySQL
$table->unsignedSmallInteger('orden')->default(0);  // ❌ MySQL
$table->dateTime('fecha_actualizacion')->useCurrent(); // ❌ NOT NULL
```

#### ✅ AHORA (SQL Server Compatible)
```php
$table->integer('fk_id_apoyo');                      // ✅ SQL Server
$table->smallInteger('orden')->default(0);          // ✅ SQL Server
$table->dateTime('fecha_actualizacion')->nullable(); // ✅ Permite NULL
```

### Script SQL de Emergencia Mejorado

El script `database/sql/fix_hitos_apoyo_es_base.sql` ahora:
1. Agrega `es_base` si falta
2. Agrega/renombra `titulo_hito` si falta
3. **Remueve DEFAULT de `fecha_actualizacion`**
4. **Cambia a NULLABLE** la columna `fecha_actualizacion`
5. Agrega `slug_hito` si falta
6. Valida la estructura final

---

## 🛠️ Archivos Modificados

1. **Migración Corregida**: `database/migrations/2026_03_21_000002_create_hitos_apoyo_table.php`
   - Removidas directivas `->unsigned()`
   - Compatible con SQL Server

2. **Script SQL de Emergencia**: `database/sql/fix_hitos_apoyo_es_base.sql`
   - Agregue la columna directamente
   - Verifica integridad

---

## 📌 Estructura Correcta de `Hitos_Apoyo`

| # | Columna | Tipo | Null | Default |
|---|---------|------|------|---------|
| 1 | id_hito | INT IDENTITY | NO | - |
| 2 | fk_id_apoyo | INT | NO | - |
| 3 | slug_hito | NVARCHAR(80) | **SÍ** | NULL |
| 4 | titulo_hito | NVARCHAR(150) | NO | - |
| 5 | fecha_inicio | DATE | **SÍ** | NULL |
| 6 | fecha_fin | DATE | **SÍ** | NULL |
| 7 | orden | SMALLINT | NO | 0 |
| 8 | **es_base** | **BIT** | **NO** | **0** |
| 9 | **activo** | **BIT** | **NO** | **1** |
| 10 | fecha_creacion | DATETIME2 | NO | GETDATE() |
| 11 | **fecha_actualizacion** | **DATETIME2** | **✅ SÍ** | **NULL** |

**✨ Cambios Principales**:
- ✅ `es_base` agregada
- ✅ `fecha_actualizacion` ahora permite NULL
- ✅ Removed `NOT NULL` DEFAULT GETDATE() de `fecha_actualizacion`

---

## 🚀 Próximos Pasos

1. **Ejecuta el script**: `database/sql/fix_hitos_apoyo_es_base.sql`
2. **Verifica**: La tabla debe tener la columna `es_base`
3. **Prueba**: Intenta crear un apoyo nuevamente

---

## ✨ Resultado Esperado Después del Script

```sql
#  Columna                | Tipo        | Null?  | Default
1  | id_hito              | int         | NO     | 
2  | fk_id_apoyo          | int         | NO     | 
3  | slug_hito            | nvarchar    | YES    | NULL
4  | titulo_hito          | nvarchar    | NO     | 
5  | fecha_inicio         | date        | YES    | NULL
6  | fecha_fin            | date        | YES    | NULL
7  | orden                | smallint    | NO     | 0
8  | es_base              | bit         | NO     | 0         ✅ ARREGLADO
9  | activo               | bit         | NO     | 1         ✅ PRESENTE
10 | fecha_creacion       | datetime2   | NO     | (getdate())
11 | fecha_actualizacion  | datetime2   | YES    | NULL      ✅ AHORA ACEPTA NULL
```

---

## ⚠️ Verificación Post-Ejecución

Después de ejecutar el script, valida en SQL Server:

```sql
-- Verificar que la columna ahora acepta NULL
SELECT 
    COUNT(*) as total_hitos,
    COUNT(DISTINCT fk_id_apoyo) as apoyos_relacionados,
    COUNT(*) FILTER (WHERE fecha_actualizacion IS NULL) as hitos_sin_actualizacion
FROM Hitos_Apoyo;

-- Debe retornar sin errores
```

**Resultado Esperado**: Sin error de NULL → ✅ **Problema Resuelto**

---

**Última actualización**: 25 de Marzo de 2026  
**Versión**: 1.0  
**Estado**: ✅ Listo para Aplicar
