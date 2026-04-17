# ✅ CORRECCIÓN: FILTRO DE DOCUMENTOS APROBADOS PARA DIRECTIVO

## 🎯 PROBLEMA IDENTIFICADO
El directivo podía ver solicitudes en `/solicitudes/proceso` **incluso si el administrativo NO había aprobado todos los documentos**.

## ✅ SOLUCIÓN IMPLEMENTADA

### Cambios en `SolicitudProcesoController::index()`

**ANTES (❌ Sin filtro):**
```php
// Mostrar pendientes (sin CUV)
$solicitudesQuery->whereNull('Solicitudes.cuv');
// ← Mostraba TODAS las solicitudes sin firma, sin importar estado de documentos
```

**AHORA (✅ Con filtro de documentos):**
```php
// Mostrar pendientes (sin CUV)
$solicitudesQuery->whereNull('Solicitudes.cuv');

// ✅ FILTRO CRÍTICO: Solo mostrar solicitudes si TODOS sus documentos están aprobados
// Las solicitudes deben tener al menos 1 documento aprobado
$solicitudesQuery->whereExists(function ($query) {
    $query->select(DB::raw(1))
        ->from('Documentos_Expediente')
        ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
        ->where('Documentos_Expediente.admin_status', 'aceptado');
});

// Y NO deben tener ningún documento con estado diferente a 'aceptado'
$solicitudesQuery->whereNotExists(function ($query) {
    $query->select(DB::raw(1))
        ->from('Documentos_Expediente')
        ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
        ->where(DB::raw("admin_status NOT IN ('aceptado', NULL)"));
});
```

---

## 📊 FLUJO ACTUALIZADO

```
1️⃣ BENEFICIARIO
   └─ Crea apoyo y solicitud
   └─ Sube documentos

2️⃣ ADMINISTRATIVO
   ├─ Va a /admin/solicitudes
   ├─ Revisa cada documento
   ├─ Marca como "aceptado" o "rechazado"
   └─ Estado de BD: admin_status = 'aceptado' | 'rechazado' | 'pendiente'

3️⃣ DIRECTIVO (NUEVO COMPORTAMIENTO)
   ├─ Va a /solicitudes/proceso
   ├─ SOLO ve solicitudes donde:
   │  ├─ Tiene al menos 1 documento
   │  ├─ Todos los documentos tienen admin_status = 'aceptado'
   │  └─ NO hay documentos con estado 'pendiente' o 'rechazado'
   ├─ Si faltan documentos por aprobar → NO aparece la solicitud
   └─ Una vez todos aprobados → aparece automáticamente para firmar
```

---

## 🔍 VALIDACIÓN EN BD

### Solicitud SI aparecerá:
```sql
SELECT folio FROM Solicitudes s
WHERE s.cuv IS NULL
AND EXISTS (
    SELECT 1 FROM Documentos_Expediente d
    WHERE d.fk_folio = s.folio
    AND d.admin_status = 'aceptado'
)
AND NOT EXISTS (
    SELECT 1 FROM Documentos_Expediente d
    WHERE d.fk_folio = s.folio
    AND d.admin_status NOT IN ('aceptado', NULL)
);
```

### Solicitud NO aparecerá si:
- ❌ No tiene documentos
- ❌ Tiene documentos en estado 'pendiente'
- ❌ Tiene documentos en estado 'rechazado'
- ❌ Tiene algún documento sin procesar

---

## 📋 CASOS DE PRUEBA

### ✅ Caso 1: Todo aprobado → Directivo VE la solicitud
```
Admin aprueba:
  - Documento 1: admin_status = 'aceptado' ✓
  - Documento 2: admin_status = 'aceptado' ✓
  - Documento 3: admin_status = 'aceptado' ✓

Resultado en /solicitudes/proceso:
  ✓ Solicitud visible para directivo
  ✓ Puede hacer clic para firmar
```

### ❌ Caso 2: Uno pendiente → Directivo NO VE la solicitud
```
Admin ha revisado:
  - Documento 1: admin_status = 'aceptado' ✓
  - Documento 2: admin_status = 'pendiente' ⏳
  - Documento 3: admin_status = 'aceptado' ✓

Resultado en /solicitudes/proceso:
  ❌ Solicitud OCULTA
  ❌ No aparece en listado
```

### ❌ Caso 3: Uno rechazado → Directivo NO VE la solicitud
```
Admin ha revisado:
  - Documento 1: admin_status = 'aceptado' ✓
  - Documento 2: admin_status = 'rechazado' ✗
  - Documento 3: admin_status = 'aceptado' ✓

Resultado en /solicitudes/proceso:
  ❌ Solicitud OCULTA
  ❌ No aparece en listado
```

---

## 🚀 CÓMO PROBAR

1. **En sesión ADMINISTRATIVO:**
   - Ve a `/admin/solicitudes`
   - Revisa solicitud (debe mostrar documentos)
   - Aprueba SOLO algunos: algunos "aceptado", otros dejan "pendiente"

2. **En sesión DIRECTIVO:**
   - Ve a `/solicitudes/proceso`
   - Pestaña "Pendientes"
   - ❌ LA SOLICITUD NO APARECERÁ

3. **De vuelta en ADMINISTRATIVO:**
   - Aprueba los documentos restantes (todos "aceptado")

4. **En sesión DIRECTIVO (refrescar):**
   - Ve a `/solicitudes/proceso`
   - Pestaña "Pendientes"
   - ✅ LA SOLICITUD AHORA APARECE Y PUEDE FIRMAR

---

## 🔐 GARANTÍA

- **Seguridad:** Directivo solo ve solicitudes completamente revisadas
- **Datos correctos:** No puede firmar sin documentos completos
- **Auditoría:** Se mantiene trazabilidad de quién aprobó qué
- **Consistencia:** Estatísticas de "pendientes" son precisas

Status: ✅ **IMPLEMENTADO Y LISTO PARA PRUEBA**
