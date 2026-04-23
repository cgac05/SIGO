# CONFIGURACIÓN COMPLETA: Beneficiarios Parciales - LOCAL ✅ | PRODUCCIÓN 📋

## ESTADO ACTUAL (LOCAL)

✅ **COMPLETADO EN LOCAL:**
1. ✅ Hicimos `fk_id_usuario` NULLABLE en Beneficiarios
2. ✅ Hicimos `beneficiario_id` NULLABLE en claves_seguimiento_privadas  
3. ✅ Cambiamos UNIQUE constraint → FILTERED UNIQUE INDEX
4. ✅ Probado: Múltiples beneficiarios sin usuario funcionan

---

## PASO-A-PASO PARA PRODUCCIÓN (Azure)

### 1️⃣ EJECUTA EN SQL SERVER (Azure)

Abre **Query Editor** en Azure Portal o conecta con **SQL Server Management Studio**:

```sql
-- ===================================
-- PASO 1: Hacer fk_id_usuario nullable
-- ===================================
ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL;

-- ===================================
-- PASO 2: Hacer beneficiario_id nullable
-- ===================================
ALTER TABLE dbo.[claves_seguimiento_privadas] ALTER COLUMN [beneficiario_id] INT NULL;

-- ===================================
-- PASO 3: Cambiar UNIQUE constraint a FILTERED INDEX
-- ===================================
-- Primero, eliminar el constraint UNIQUE existente
ALTER TABLE dbo.[Beneficiarios] DROP CONSTRAINT [UQ__Benefici__1698AC3A0A5A23D3];

-- Luego, crear índice UNIQUE FILTRADO (permite múltiples NULL)
CREATE UNIQUE INDEX UQ_fk_id_usuario_not_null 
ON dbo.[Beneficiarios] (fk_id_usuario)
WHERE fk_id_usuario IS NOT NULL;
```

**Presiona F5 para ejecutar** (o el equivalente en Azure Portal)

---

### 2️⃣ VERIFICA LOS CAMBIOS

Ejecuta esto para confirmar:

```sql
-- Verificar que fk_id_usuario es nullable
SELECT COLUMN_NAME, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'Beneficiarios' AND COLUMN_NAME = 'fk_id_usuario';
-- Resultado esperado: IS_NULLABLE = YES

-- Verificar que beneficiario_id es nullable
SELECT COLUMN_NAME, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'claves_seguimiento_privadas' AND COLUMN_NAME = 'beneficiario_id';
-- Resultado esperado: IS_NULLABLE = YES

-- Verificar que el índice filtrado existe
SELECT name, is_unique, filter_definition
FROM sys.indexes
WHERE object_id = OBJECT_ID('Beneficiarios')
AND name LIKE '%fk_id_usuario%';
-- Resultado esperado: UQ_fk_id_usuario_not_null, is_unique=1, filter=[fk_id_usuario] IS NOT NULL
```

---

### 3️⃣ ACTUALIZA CÓDIGO (Laravel)

Los archivos Laravel ya están preparados. Solo necesita git push:

**Archivos modificados:**
- ✅ `app/Services/CasoADocumentService.php` - Método `crearBeneficiarioPartial()` + actualizado `crearExpedientePresencial()`
- ✅ `database/migrations/2026_04_18_164833_make_fk_id_usuario_nullable_in_beneficiarios.php`
- ✅ `database/migrations/2026_04_18_165235_make_beneficiario_id_nullable_in_claves_seguimiento_privadas.php`
- ✅ `database/migrations/2026_04_20_155504_fix_unique_constraint_on_fk_id_usuario_in_beneficiarios.php`

```bash
git add -A
git commit -m "feat: Beneficiarios parciales (sin usuario) para Caso A"
git push
```

---

### 4️⃣ DEPLOY A PRODUCCIÓN

Una vez que los cambios SQL se ejecuten en Azure:

```bash
# En tu servidor de producción:
php artisan migrate

# Esto registrará las migraciones en la tabla migrations
# (Los cambios SQL ya estarán hechos manualmente)
```

---

## 🎯 CÓMO FUNCIONA AHORA

### Caso: Beneficiario NO REGISTRADO

1. Admin va a `/admin/caso-a/momento-uno`
2. Busca CURP que NO existe en el sistema
3. Llena formulario manual:
   - Nombre Completo
   - CURP *(obligatorio)*
   - Email *(opcional)*
   - Teléfono *(opcional, formato: (123) 456-7890)*

4. **AL GUARDAR:**
   - ✅ Crea registro en `Beneficiarios` con:
     - `curp` = valor capturado
     - `nombre`, `apellido_paterno`, `apellido_materno` = valor capturado
     - `telefono` = valor capturado
     - **`fk_id_usuario = NULL`** ← Sin usuario del sistema
   
   - ✅ Crea `Solicitud` con:
     - `beneficiario_id = NULL` (sin referencia a usuario)
     - `fk_curp` = CURP capturado
     - `origen_solicitud = 'admin_caso_a'`
     - `estado_solicitud = 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN'`
   
   - ✅ Crea `claves_seguimiento_privadas`:
     - `folio` = auto-generado
     - `clave_alfanumerica` = generada aleatoriamente
     - **`beneficiario_id = NULL`** ← Sin vinculación a usuario
   
   - ✅ Redirige a página de **Resumen** con:
     - FOLIO
     - CLAVE PRIVADA
     - Datos del beneficiario

5. **Beneficiario accede sin login:**
   - Va a `/consulta-privada`
   - Ingresa: Folio + Clave Privada
   - Sin contraseña, sin usuario ✅

---

## 📊 COMPARACIÓN: ANTES vs DESPUÉS

| Aspecto | ANTES | DESPUÉS |
|--------|-------|---------|
| Beneficiario no registrado | ❌ Error FK | ✅ Crea registro parcial |
| `fk_id_usuario` | NOT NULL | **NULL** |
| `beneficiario_id` (claves) | FK constraint | **NULL** |
| UNIQUE en `fk_id_usuario` | Bloquea múltiples NULL | ✅ FILTERED INDEX |
| Múltiples beneficiarios sin usuario | ❌ Imposible | ✅ Permitido |
| Acceso a expediente sin login | ❌ Requería usuario | ✅ Solo folio+clave |

---

## ⚠️ ROLLBACK (Si es necesario)

Si algo falla y necesitas revertir:

```sql
-- Revertir cambios
DROP INDEX UQ_fk_id_usuario_not_null ON dbo.[Beneficiarios];

ALTER TABLE dbo.[Beneficiarios] 
ADD CONSTRAINT [UQ__Benefici__1698AC3A0A5A23D3] 
UNIQUE (fk_id_usuario);

ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NOT NULL;
ALTER TABLE dbo.[claves_seguimiento_privadas] ALTER COLUMN [beneficiario_id] INT NOT NULL;
```

---

## ✅ CHECKLIST PRODUCCIÓN

- [ ] Ejecuté los 3 comandos SQL en Azure
- [ ] Verifiqué que los cambios se aplicaron correctamente
- [ ] Hice `git push` del código
- [ ] Ejecuté `php artisan migrate` en producción
- [ ] Probé flujo completo en producción: crear beneficiario no registrado → acceso con folio+clave
- [ ] Confirmé que beneficiarios registrados siguen funcionando normal

---

¿Listo? Una vez que ejecutes el SQL en Azure, me confirmas y testamos juntos! 🚀
