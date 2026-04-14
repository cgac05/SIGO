# Problema: Tabla `auditoria_folios` no existe

## 🔴 Error Experimentado

```
SQLSTATE[42S02]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]
El nombre de objeto 'auditoria_folios' no es válido.
```

**Ruta afectada**: `http://localhost:8000/apoyos/47/solicitud` (Crear solicitud como beneficiario)

---

## 📋 Causa Raíz

La tabla `auditoria_folios` es requerida por:
1. **FolioService** - Para generar y auditar folios institucionales
2. **SolicitudController** - Para registrar cuando se usa un folio

Sin embargo, la tabla **no fue creada** en la base de datos durante la instalación.

---

## ✅ Solución Implementada

### Paso 1: Código Defensivo (Aplicado - INMEDIATO)

Modifiqué el código para que **funcione aunque la tabla no exista**:

**Archivos modificados:**
1. `app/Services/FolioService.php` - Verifica si tabla existe antes de usarla
2. `app/Http/Controllers/SolicitudController.php` - Maneja gracefully si auditoria falla

**Comportamiento**:
- ✅ Las solicitudes se crean normalmente
- ⚠️ La auditoría se omite si la tabla no existe
- 📝 Se registran warnings en los logs

### Paso 2: Crear la Tabla (REQUERIDO - A.S.A.P)

#### Opción A: Administrador de BD (RECOMENDADO)

1. Abre **SQL Server Management Studio**
2. Conéctate a `BD_SIGO` con credenciales de **administrador**
3. Abre archivo: `crear_tabla_auditoria_folios.sql`
4. **Ejecuta el script**
5. ✅ La tabla se crea
6. Reinicia la aplicación

**Vista previa del script:**
```sql
CREATE TABLE [dbo].[auditoria_folios] (
    [id_auditoria_folio] INT PRIMARY KEY IDENTITY(1,1),
    [folio_completo] VARCHAR(50) NOT NULL UNIQUE,
    [numero_base] VARCHAR(5) NOT NULL,
    [digito_verificador] INT NOT NULL,
    [fk_id_beneficiario] INT NULL,
    [fk_folio_solicitud] INT NULL,
    [año_fiscal] INT NOT NULL,
    [fecha_generacion] DATETIME2 NOT NULL DEFAULT GETDATE(),
    [generado_por] INT NULL,
    [ip_generacion] VARCHAR(45) NULL,
    [created_at] DATETIME2 NULL,
    [updated_at] DATETIME2 NULL
);
-- ... más índices
```

---

## 🗂️ Estructura de la Tabla

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id_auditoria_folio` | INT (PK) | ID único |
| `folio_completo` | VARCHAR(50) UNIQUE | Ej: SIGO-2026-00001-3 |
| `numero_base` | VARCHAR(5) | Números sin verificador |
| `digito_verificador` | INT | Dígito Verhoeff (0-9) |
| `fk_id_beneficiario` | INT NULL | Referencia beneficiario |
| `fk_folio_solicitud` | INT NULL | Referencia solicitud |
| `año_fiscal` | INT | Año (ej: 2026) |
| `fecha_generacion` | DATETIME2 | Cuándo se generó |
| `generado_por` | INT NULL | Usuario que generó |
| `ip_generacion` | VARCHAR(45) | IP origen |
| `created_at`, `updated_at` | DATETIME2 | Laravel timestamps |

---

## 🧪 Prueba Actual (Funciona sin tabla)

### Test 1: Crear Solicitud
```
GET http://localhost:8000/apoyos/47/solicitud
→ Debería mostrar formulario (sin errores)

POST (llenar y enviar)
→ Debería crear solicitud
→ Log: WARNING - tabla auditoria_folios no existe
→ Solicitud se crea normalmente ✅
```

### Test 2: Después de Crear Tabla
```
POST (mismo formulario)
→ Folio se registra en auditoría ✅
→ Log: INFO - Folio generado con auditoría ✅
```

---

## 📝 Logs

Ver logs con soporte para auditoría faltante:

```bash
# Terminal
tail -f storage/logs/laravel.log | grep -i "auditoria\|folio"

# Verás algo como:
[2026-04-13 15:30:45] WARNING: Tabla auditoria_folios no existe. 
  El folio no fue auditado. sugerencia: Ejecutar: CREATE_TABLA_AUDITORIA_FOLIOS.sql
```

Después de crear la tabla:

```
[2026-04-13 15:35:12] INFO: Folio generado con auditoría exitosa.
```

---

## 🚨 Estado Actual

| Funcionalidad | Estado | Notas |
|---------------|--------|-------|
| Crear solicitudes | ✅ Funciona | Tabla no necesaria (fallback) |
| Generar folios | ✅ Funciona | Fallback a contar Solicitudes |
| Auditoría folios | ⚠️ Omitida | Espera creación de tabla |
| Estadísticas folios | ✅ Funciona | Retorna ceros (tabla inexistente) |

---

## 📋 Archivos Relevantes

| Archivo | Ubicación | Propósito |
|---------|-----------|----------|
| `crear_tabla_auditoria_folios.sql` | Raíz del proyecto | Script SQL para crear tabla |
| `crear_tabla_auditoria_folios.php` | Raíz del proyecto | Script PHP (referencia) |
| `2026_04_13_000001_create_auditoria_folios_table.php` | `database/migrations/` | Migración Laravel |

---

## ✅ Próximos Pasos

### Inmediato (Hecho)
- ✅ Código defensivo aplicado
- ✅ Usuarios pueden crear solicitudes normalmente

### Corto Plazo (Esta  semana)
- ⏳ Administrador de BD crea tabla
- ⏳ Solicitud de permisos CREATE TABLE
- ⏳ Ejecutar `crear_tabla_auditoria_folios.sql`

### Mediano Plazo (Próximas semanas)
- ⏳ Migración exitosa
- ⏳ Auditoría de folios activada
- ⏳ Reportes de auditoría disponibles

---

## 🔐 Consideraciones de Seguridad

✅ **LGPDP**: La auditoría es importante para cumplimiento
✅ **Integridad de datos**: Los folios se validan con Verhoeff
⚠️ **Privacidad**: Tabla contiene ID de beneficiarios

La tabla debe estar en el mismo servidor y respaldada regularmente.

---

## 📞 Contacto / Soporte

Si el administrador de BD necesita ayuda:

1. **Requisitos**: Credenciales de administrador de SQL Server
2. **Tiempo estimado**: 5 minutos
3. **Sin downtime**: Tabla nuevapuede crearse sin parar aplicación
4. **Rollback fácil**: DROP TABLE auditoria_folios; (si es necesario)

---

**Última actualización**: 13 de Abril, 2026  
**Estado**: 🟡 Funcionando parcialmente (sin auditoría)  
**Prioridad**: Media (crear tabla esta semana)
