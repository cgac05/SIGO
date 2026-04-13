# 🧪 GUÍA DE PRUEBA: Completar Fases Manualmente en Folio 1007

## ✅ Paso 0: Resetear la Solicitud

### Ejecuta este SQL en tu BD:

```sql
-- Resetear Folio 1007
UPDATE Solicitudes 
SET presupuesto_confirmado = 0, cuv = NULL, monto_entregado = NULL
WHERE folio = 1007;

UPDATE Documentos_Expediente 
SET estado_validacion = 'Pendiente', revisado_por = NULL, fecha_revision = NULL
WHERE fk_folio = 1007;

-- Verificar
SELECT folio, presupuesto_confirmado AS F1, cuv AS F2, monto_entregado AS F3 
FROM Solicitudes WHERE folio = 1007;
```

**Resultado esperado:**
```
folio | F1 | F2   | F3
1007  | 0  | NULL | NULL
```

---

## 🔴 FASE 1: Revisar Documentos

### 1️⃣ Obtén los IDs de documentos

Ejecuta este SQL para ver qué documentos revisar:

```sql
SELECT id_doc, nombre_archivo, estado_validacion
FROM Documentos_Expediente
WHERE fk_folio = 1007
ORDER BY id_doc ASC;
```

**Resultado esperado:**
```
id_doc | nombre_archivo | estado_validacion
500    | cedula.pdf     | Pendiente
501    | comprobante.pdf| Pendiente
502    | otro.pdf       | Pendiente
```

✏️ **Anota los IDs** (500, 501, 502 en este ejemplo)

---

### 2️⃣ Ve a la Pestaña "Procesos"

1. Abre: `http://127.0.0.1:8000/solicitudes/proceso`
2. Busca la **tarjeta de Folio 1007**
3. Localiza la sección **"Fase 1: Revisión administrativa"** (estará con **borde AZUL** y dice "◉")
4. Haz clic en "Expandir" para abrir el formulario

---

### 3️⃣ Completa el Formulario de Fase 1

Llena los campos en este orden:

```
┌────────────────────────────────────────────┐
│ ID documento:            [500]             │  ← PRIMER id_doc que obtuviste
│ Acción:                  [Aprobar ▼]      │  ← Selecciona "Aprobar"
│ Observaciones:           [Aceptado]       │  ← Texto opcional
│ ☑ Permite correcciones                     │  ← Dejar checkeado
│ Google Drive webViewLink: [DEIXA VACÍO]   │  ← Opcional
│ source_file_id:          [DEIXA VACÍO]    │  ← Opcional
│ official_file_id:        [DEIXA VACÍO]    │  ← Opcional
│ [GUARDAR REVISIÓN]                        │  ← CLICK AQUÍ
└────────────────────────────────────────────┘
```

**➤ CLICK en "GUARDAR REVISIÓN"**

---

### 4️⃣ Repite para TODOS los documentos

Si hay 3 documentos (500, 501, 502):

```
ITERACIÓN 1:
  ID documento: 500 → Acción: Aprobar → [GUARDAR REVISIÓN]
  ✅ Resultado: Página recarga, documento guardado

ITERACIÓN 2:
  ID documento: 501 → Acción: Aprobar → [GUARDAR REVISIÓN]
  ✅ Resultado: Página recarga, documento guardado

ITERACIÓN 3:
  ID documento: 502 → Acción: Aprobar → [GUARDAR REVISIÓN]
  ✅ Resultado: Página recarga, TODOS los documentos aprobados
```

---

### 5️⃣ Verifica que Fase 1 se Completó

Después de aprobar todos:

**En la Vista:**
- La Fase 1 debe cambiar a **VERDE** ✅
- El texto debe decir: `✓ Fase 1: Revisión administrativa`
- La Fase 2 debe cambiar a **AZUL** (activa)

**En la BD (para confirmar):**

```sql
SELECT folio, presupuesto_confirmado AS F1 FROM Solicitudes WHERE folio = 1007;
```

**Resultado esperado:**
```
folio | F1
1007  | 1  ← Este debe cambiar de 0 a 1 automáticamente
```

✅ **FASE 1 COMPLETADA**

---

## 🟡 FASE 2: Firmar Digitalmente

### 1️⃣ Abre Fase 2 (debe estar AZUL)

1. Busca la sección **"Fase 2: Firma directiva"** (borde AZUL, dice "◉")
2. Haz clic en "Expandir" para abrir el formulario

```
┌──────────────────────────────────────────┐
│ Folio:                   1007            │  ← Auto-llenado
│ Contraseña:              [••••••]        │  ← TU contraseña
│ [FIRMAR Y GENERAR CUV]                   │  ← CLICK AQUÍ
└──────────────────────────────────────────┘
```

---

### 2️⃣ Ingresa tu Contraseña

⚠️ **IMPORTANTE:** Debe ser la contraseña del **usuario directivo** que está logeado.

- ✅ Si es correcta: Se genera CUV automáticamente
- ❌ Si es incorrecta: Error "Contraseña incorrecta"

---

### 3️⃣ Click en "FIRMAR Y GENERAR CUV"

**Lo que pasa detrás:**
1. Verifica contraseña
2. Valida presupuesto disponible
3. Valida inventario disponible
4. Genera CUV único
5. Guarda firma en BD

**Resultado esperado:**
```
✓ FIRMA EXITOSA
Página recarga
Fase 2 se pone VERDE
Fase 3 se pone AZUL (activa)
```

---

### 4️⃣ Verifica que Fase 2 se Completó

**En la Vista:**
- La Fase 2 debe cambiar a **VERDE** ✅
- El texto debe decir: `✓ Fase 2: Firma directiva (CUV: CUV-2026-...)`
- La Fase 3 debe cambiar a **AZUL** (activa)

**En la BD (para confirmar):**

```sql
SELECT folio, cuv FROM Solicitudes WHERE folio = 1007;
```

**Resultado esperado:**
```
folio | cuv
1007  | CUV-2026-1007-XXXXXX  ← Se generó automáticamente
```

✅ **FASE 2 COMPLETADA**

---

## 🟢 FASE 3: Cierre Financiero

### 1️⃣ Abre Fase 3 (debe estar AZUL)

1. Busca la sección **"Fase 3: Cierre financiero"** (borde AZUL, dice "◉")
2. Haz clic en "Expandir" para abrir el formulario

```
┌────────────────────────────────────────────┐
│ Folio:                   1007              │  ← Auto-llenado
│ Monto entregado:         [50000.00]       │  ← Cantidad entregada
│ Fecha de entrega:        [12/04/2026]     │  ← Cuándo se entregó
│ Ruta PDF final:          [/archivos/...]  │  ← Documento (opcional)
│ [CERRAR SOLICITUD]                        │  ← CLICK AQUÍ
└────────────────────────────────────────────┘
```

---

### 2️⃣ Llena los Campos

#### Campo 1: Monto entregado
- Número decimal: `50000.00`
- Debe ser > 0
- Puede ser igual al monto de la solicitud o menos

#### Campo 2: Fecha de entrega
- Usar formato: DD/MM/YYYY
- O selector de calendario
- Puede ser hoy: `12/04/2026`

#### Campo 3: Ruta PDF (opcional)
- Si tienes un PDF: `/archivos/1007_entrega.pdf`
- Si no: dejar vacío

---

### 3️⃣ Click en "CERRAR SOLICITUD"

**Lo que pasa detrás:**
1. Valida campos requeridos
2. Guarda monto_entregado
3. Guarda fecha_entrega_recurso
4. Guarda ruta_pdf_final (si aplica)
5. Marca solicitud como FINALIZADA

**Resultado esperado:**
```
✓ SOLICITUD CERRADA
Página recarga
Fase 3 se pone VERDE
Todas las fases están VERDES ✓✓✓
```

---

### 4️⃣ Verifica que Fase 3 se Completó

**En la Vista:**
- Las 3 fases están **VERDES** ✅✅✅
- Todos los textos dicen: `✓ Fase X: ...`
- No hay nada en AZUL (todas completadas)

**En la BD (para confirmar):**

```sql
SELECT 
    folio,
    presupuesto_confirmado AS [F1],
    cuv AS [F2],
    monto_entregado AS [F3],
    fecha_entrega_recurso AS [Fecha]
FROM Solicitudes 
WHERE folio = 1007;
```

**Resultado esperado:**
```
folio | F1 | F2               | F3       | Fecha
1007  | 1  | CUV-2026-1007... | 50000.00 | 2026-04-12
```

✅ **FASE 3 COMPLETADA**

---

## 🎯 RESUMEN: ANTES vs DESPUÉS

### ANTES (Inicial)
```sql
SELECT folio, presupuesto_confirmado, cuv, monto_entregado FROM Solicitudes WHERE folio = 1007;

folio | presupuesto_confirmado | cuv  | monto_entregado
1007  | 0                      | NULL | NULL
```
⚠️ 🔴 **Estado:** FASE 1 Pendiente

---

### DESPUÉS (Completado)
```sql
SELECT folio, presupuesto_confirmado, cuv, monto_entregado FROM Solicitudes WHERE folio = 1007;

folio | presupuesto_confirmado | cuv                | monto_entregado
1007  | 1                      | CUV-2026-1007-XXX  | 50000.00
```
✅ 🟢 **Estado:** TODAS LAS FASES COMPLETAS

---

## 🐛 SOLUCIONAR PROBLEMAS

| Problema | Causa | Solución |
|----------|-------|----------|
| "Documento no encontrado" en Fase 1 | ID documento incorrecto | Revisa con: `SELECT id_doc FROM Documentos_Expediente WHERE fk_folio = 1007` |
| "Presupuesto insuficiente" en Fase 2 | Sin presupuesto asignado | Contacta a Contabilidad o revisa `Presupuestacion` |
| "Contraseña incorrecta" en Fase 2 | Contraseña directivo mal | Usa la contraseña correcta del usuario logeado |
| Fase 1 NO se completa | Hay documentos sin aprobar | Asegúrate de aprobar TODOS: repetir paso 4 para cada documento |
| Fase 2 NO se completa | La firma falló silenciosamente | Revisa logs en `storage/logs/laravel.log` |
| Fase 3 NO se completa | Campos en blanco | Llena TODOS los campos requeridos |

---

## 📋 CHECKLIST FINAL

Marca mientras avanzas:

- [ ] SQL ejecutado: Folio 1007 reseteado
- [ ] FASE 1: Aprobé documento 500
- [ ] FASE 1: Aprobé documento 501
- [ ] FASE 1: Aprobé documento 502
- [ ] FASE 1: Verificado en BD → `presupuesto_confirmado = 1` ✅
- [ ] FASE 2: Ingresé contraseña
- [ ] FASE 2: Clickeé "Firmar y generar CUV"
- [ ] FASE 2: Verificado en BD → `cuv ≠ NULL` ✅
- [ ] FASE 3: Ingresé monto: 50000.00
- [ ] FASE 3: Seleccioné fecha: 12/04/2026
- [ ] FASE 3: Clickeé "Cerrar solicitud"
- [ ] FASE 3: Verificado en BD → `monto_entregado = 50000.00` ✅
- [ ] ✅ TODAS LAS FASES VERDES EN LA VISTA

---

## 🚀 ¿TODO FUNCIONÓ?

Si llegaste aquí y los 3 checkboxes ✅ se cumplieron:

✅ **SISTEMA DE FASES FUNCIONA CORRECTAMENTE**

Las fases avanzan en orden secuencial según los campos de BD.

Si hay algún problema, avísame con:
1. El error exacto que ves
2. El paso en el que falla
3. Tu archivo `storage/logs/laravel.log` (últimas líneas)

