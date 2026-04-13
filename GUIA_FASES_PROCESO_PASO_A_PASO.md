# 📋 GUÍA PASO A PASO: Avanzar entre Fases en la Pestaña "Procesos"

## 🎯 Resumen Rápido del Flujo

```
FASE 1: Revisar documentos            FASE 2: Generar CUV          FASE 3: Cierre financiero
└─ Aprobar documentos                 └─ Firmar digitalmente       └─ Entregar dinero
   ↓                                     ↓                           ↓
   presupuesto_confirmado = 1           cuv = "CUV-2026-..."         monto_entregado = 50000
   ✓ FASE 1 COMPLETA                    ✓ FASE 2 COMPLETA            ✓ FASE 3 COMPLETA
```

---

## 🔴 FASE 1: REVISIÓN ADMINISTRATIVA

### ¿Cuándo está esta fase ACTIVA?
✅ **Visible** si: `presupuesto_confirmado = 0` (o NULL) y `cuv = NULL`
🔵 **Color AZUL** = Fase activa (haz clic para expandir)

### ¿Qué necesitas hacer?

Necesitas **aprobar TODOS los documentos** enviados por el beneficiario. Estos documentos están en la tabla `Documentos_Expediente`.

#### 📝 Formulario en Fase 1:
```
┌─────────────────────────────────────────┐
│ ID documento:           [________]       │  ← ID del documento a revisar
│ Acción:                 [Aprobar ▼]     │  ← Selecciona: Aprobar/Observar/Rechazar
│ Observaciones:          [large box]     │  ← Notas opcionales
│ ☑ Permite correcciones                  │  ← Si rechazas, ¿permitir correcciones?
│ Google Drive webViewLink: [URL]         │  ← Link de Drive (opcional)
│ source_file_id:         [______]        │  ← ID del archivo origen (opcional)
│ official_file_id:       [______]        │  ← ID expediente oficial (opcional)
│ [GUARDAR REVISIÓN]                      │  ← Botón para procesar
└─────────────────────────────────────────┘
```

### 📌 Acciones Posibles en Fase 1:

| Acción | Qué pasa | Resultado |
|--------|----------|-----------|
| **Aprobar** | Documento va a estado "Aprobado" | ✓ Documento OK, avanza |
| **Observar** | Documento va a "Observado", notifica beneficiario | ⚠️ Necesita revisión |
| **Rechazar** | Documento va a "Rechazado" | ❌ Debe resubir |

### ⚡ ¿CÓMO PUEDO SABER QUÉ DOCUMENTOS REVISAR?

Necesitas saber qué `id_documento` tienes que incluir. Ejecuta este SQL:

```sql
-- Ver TODOS los documentos de una solicitud
SELECT 
    d.id_doc,
    d.fk_folio,
    d.fk_id_tipo_documento,
    t.nombre_tipo_documento,
    d.estado_validacion,
    d.nombre_archivo
FROM Documentos_Expediente d
LEFT JOIN Tipo_Documento t ON d.fk_id_tipo_documento = t.id_tipo_documento
WHERE d.fk_folio = 1007  -- Reemplaza con tu folio
ORDER BY d.id_doc ASC;
```

Esto te mostrará todos los documentos. **Debes aprobar TODOS** antes de pasar a Fase 2.

### 🎯 OBJETIVO DE FASE 1:

✓ **TODOS los documentos** deben estar en estado **"Aprobado"**

Cuando TODOS están aprobados → Se marca automáticamente `presupuesto_confirmado = 1` → **FASE 1 SE COMPLETA** ✅

---

## 🟡 FASE 2: FIRMA DIRECTIVA (Generar CUV)

### ¿Cuándo está esta fase ACTIVA?
✅ **Visible** si: `presupuesto_confirmado = 1` (Fase 1 completada) AND `cuv = NULL`
🔵 **Color AZUL** = Fase activa (haz clic para expandir)

### ¿Qué necesitas hacer?

Como **directivo**, debes **firmar digitalmente** la solicitud usando tu contraseña.

#### 📝 Formulario en Fase 2:
```
┌──────────────────────────────────────────┐
│ Folio:                  1007             │  ← Auto-llenado
│ Contraseña:             [••••••]         │  ← TU contraseña de directivo
│ [FIRMAR Y GENERAR CUV]                   │  ← Botón para firmar
└──────────────────────────────────────────┘
```

### ✍️ ¿QUÉ PASA AL FIRMAR?

1. **Verificación de contraseña** → ¿Es correcta tu contraseña de directivo?
2. **Validación de presupuesto** → ¿Hay presupuesto disponible en el apoyo?
3. **Validación de inventario** → Si es especie, ¿hay stock disponible?
4. **Generación de CUV** → Se crea un código único (Código Único de Validación)
5. **Registro en BD** → Se guarda la firma digitalmente en `Firmas_Electronicas`

```sql
-- Ver la firma que se generó
SELECT 
    id_firma,
    cuv,
    folio,
    firmante_id,
    fecha_firma,
    tipo_firma
FROM Firmas_Electronicas
WHERE folio = 1007
ORDER BY fecha_firma DESC;
```

### 📍 ¿CÓMO SABER SI LA FIRMA FUNCIONÓ?

Después de firmar, verás en la Fase 2:
```
✓ Fase 2: Firma directiva (CUV: CUV-2026-1007-XXXXX)
```

El CUV aparece en el título cuando se firma exitosamente.

### 🎯 OBJETIVO DE FASE 2:

✓ **Firmar exitosamente** como directivo → Se genera `cuv` → **FASE 2 SE COMPLETA** ✅

---

## 🟢 FASE 3: CIERRE FINANCIERO

### ¿Cuándo está esta fase ACTIVA?
✅ **Visible** si: `presupuesto_confirmado = 1` AND `cuv ≠ NULL` (Fases 1 y 2 completadas) AND `monto_entregado = NULL`
🔵 **Color AZUL** = Fase activa (haz clic para expandir)

### ¿Qué necesitas hacer?

Registrar que **se entregó el dinero/bien** al beneficiario.

#### 📝 Formulario en Fase 3:
```
┌────────────────────────────────────────────┐
│ Folio:                  1007               │  ← Auto-llenado
│ Monto entregado:        [50000.00]        │  ← ¿Cuánto se en entregó?
│ Fecha de entrega:       [DD/MM/YYYY]      │  ← Cuándo se entregó
│ Ruta PDF con QR:        [URL o ruta]      │  ← Documento de entrega
│ [CERRAR SOLICITUD]                        │  ← Botón final
└────────────────────────────────────────────┘
```

### 💰 ¿QUÉ PASA AL CERRAR?

1. Se registra `monto_entregado = 50000.00` 
2. Se registra `fecha_entrega_recurso = 2026-04-12`
3. Se guarda la ruta del PDF (si aplica)
4. La solicitud se marca como **FINALIZADA**
5. Se genera reporte para Sistema Presupuestario

```sql
-- Ver que la solicitud se cerró
SELECT 
    folio,
    presupuesto_confirmado,
    cuv,
    monto_entregado,
    fecha_entrega_recurso
FROM Solicitudes
WHERE folio = 1007;
```

### 🎯 OBJETIVO DE FASE 3:

✓ **Registrar entrega de dinero** → Se guarda `monto_entregado` → **FASE 3 SE COMPLETA** ✅

---

## 🔄 FLUJO COMPLETO CON EJEMPLO REAL

### Ejemplo: Solicitud Folio 1007

```
INICIO
│
├─ FASE 1: Revisión Administrativa
│  ├─ BD Actualiza: presupuesto_confirmado = 0, cuv = NULL, monto_entregado = NULL
│  ├─ Estado: 🔴 FASE 1 Pendiente
│  │
│  ├─ Tú haces: Buscar id_documento en BD
│  │  └─ SQL: SELECT id_doc FROM Documentos_Expediente WHERE fk_folio = 1007
│  │  └─ Resultado: id_documento = 456
│  │
│  ├─ Tú haces: Llenar formulario
│  │  ├─ ID documento: 456
│  │  ├─ Acción: Aprobar
│  │  ├─ Observaciones: "OK"
│  │  └─ [Click GUARDAR REVISIÓN]
│  │
│  ├─ BD Actualiza: Documentos_Expediente { estado_validacion = "Aprobado" }
│  ├─ BD Actualiza: Solicitudes { presupuesto_confirmado = 1 } ✓
│  └─ Estado: 🟢 FASE 1 COMPLETADA ✓
│
├─ FASE 2: Firma Directiva
│  ├─ BD Valida: Presupuesto y inventario disponible
│  │
│  ├─ Tú haces: Llenar formulario
│  │  ├─ Folio: 1007 (auto)
│  │  ├─ Contraseña: ••••••
│  │  └─ [Click FIRMAR Y GENERAR CUV]
│  │
│  ├─ BD Verifica: ¿Contraseña correcta?
│  ├─ BD Valida: ¿Presupuesto OK?
│  ├─ BD Genera: CUV = "CUV-2026-1007-XXXXX"
│  ├─ BD Actualiza: Solicitudes { cuv = "CUV-2026..." } ✓
│  ├─ BD Inserta: Firmas_Electronicas { cuv, folio, fecha_firma, ... }
│  └─ Estado: 🟢 FASE 2 COMPLETADA ✓
│
├─ FASE 3: Cierre Financiero
│  ├─ Tú haces: Llenar formulario
│  │  ├─ Monto entregado: 50000.00
│  │  ├─ Fecha: 2026-04-12
│  │  ├─ PDF: /archivos/entregas/1007.pdf
│  │  └─ [Click CERRAR SOLICITUD]
│  │
│  ├─ BD Actualiza: Solicitudes { monto_entregado = 50000.00 } ✓
│  ├─ BD Valida: Se envía a Sistema Presupuestario
│  └─ Estado: 🟢 FASE 3 COMPLETADA ✓
│
└─ FIN: ✅ SOLICITUD COMPLETADA
```

---

## 🐛 DEBUGGING: Ver en Qué Fase Está Cada Solicitud

### 1️⃣ En el Navegador (DevTools)

Abre F12 (Console) y ejecuta:

```javascript
// Ver todas las fases visibles
Array.from(document.querySelectorAll('div[class*="bg-green"], div[class*="bg-blue"], div[class*="bg-gray"]'))
  .forEach(el => console.log(el.textContent));
```

### 2️⃣ En SQL

```sql
SELECT 
    s.folio,
    CASE 
        WHEN s.presupuesto_confirmado = 0 THEN '🔴 FASE 1'
        WHEN s.presupuesto_confirmado = 1 AND s.cuv IS NULL THEN '🟡 FASE 2'
        WHEN s.presupuesto_confirmado = 1 AND s.cuv IS NOT NULL AND s.monto_entregado IS NULL THEN '🟡 FASE 3'
        WHEN s.monto_entregado IS NOT NULL THEN '🟢 COMPLETA'
    END AS [Fase Actual],
    s.presupuesto_confirmado,
    s.cuv,
    s.monto_entregado
FROM Solicitudes s
WHERE s.folio IN (1007, 1005, 1000)
ORDER BY s.folio;
```

---

## ⚠️ ERRORES COMUNES Y SOLUCIONES

| Error | Causa | Solución |
|-------|-------|----------|
| "Documento no encontrado" | El `id_documento` no existe | Verifica con SQL: `SELECT id_doc FROM Documentos_Expediente WHERE fk_folio = XXXX` |
| "Presupuesto insuficiente" | No hay presupuesto asignado al apoyo | Revisa tabla `Presupuestacion` o contacta Contabilidad |
| "Contraseña incorrecta" | La contraseña no coincide | Verifica que sea TU contraseña de directivo |
| Fase 1 no se completó | Hay documentos sin aprobar | Aprueba TODOS: `SELECT * FROM Documentos_Expediente WHERE fk_folio = 1007 AND estado_validacion != 'Aprobado'` |

---

## 🔗 Archivos Técnicos Relacionados

- **Vista:** [resources/views/solicitudes/proceso.blade.php](resources/views/solicitudes/proceso.blade.php)
- **Controlador:** [app/Http/Controllers/SolicitudProcesoController.php](app/Http/Controllers/SolicitudProcesoController.php)
- **Servicios:**
  - Presupuestario: [app/Services/PresupuestaryControlService.php](app/Services/PresupuestaryControlService.php)
  - Firma: [app/Services/FirmaElectronicaService.php](app/Services/FirmaElectronicaService.php)
  - Inventario: [app/Services/InventarioValidationService.php](app/Services/InventarioValidationService.php)

---

## 🧪 TEST: Simular Progresión Completa

```sql
-- 1️⃣ FASE 1: Aprobar primero manualmente cualquier documento
UPDATE Documentos_Expediente 
SET estado_validacion = 'Aprobado', 
    revisado_por = (SELECT TOP 1 id_usuario FROM Usuarios LIMIT 1)
WHERE fk_folio = 1007;

-- Confirmar presupuesto manualmente para Fase 1
UPDATE Solicitudes 
SET presupuesto_confirmado = 1 
WHERE folio = 1007;

-- 2️⃣ FASE 2: Generar CUV dummy
UPDATE Solicitudes 
SET cuv = 'CUV-2026-' + CAST(1007 AS VARCHAR) + '-TEST' 
WHERE folio = 1007;

-- 3️⃣ FASE 3: Registrar entrega
UPDATE Solicitudes 
SET monto_entregado = 50000.00,
    fecha_entrega_recurso = GETDATE()
WHERE folio = 1007;

-- ✅ Verificar
SELECT folio, presupuesto_confirmado, cuv, monto_entregado FROM Solicitudes WHERE folio = 1007;
```

Recarga la página y verás: 🟢 🟢 🟢 **TODAS LAS FASES COMPLETADAS**

