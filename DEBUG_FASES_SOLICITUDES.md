# 🔍 Guía de Debugging: Sistema de Fases en Solicitudes

## 📌 Problema Identificado

**Confusión entre DOS sistemas paralelos:**
- `Estado de Solicitud` (Susbanación, Aprobado) → tabla `Cat_EstadosSolicitud`
- `Fase del Proceso` (Fase 1, 2, 3) → lógica de la vista basada en campos BD

---

## 🗂️ Campos de la BD que controlan las FASES

### Tabla: `Solicitudes`

| Campo | Tipo | Permite NULL | Descripción |
|-------|------|--------------|-------------|
| `folio` | int | NO | ID único |
| `fk_id_estado` | int | SÍ | Estado actual (Susbanación, Aprobado, etc) |
| `presupuesto_confirmado` | bit/boolean | SÍ | ¿Presupuesto administrativo confirmado? |
| `cuv` | varchar | SÍ | Código único de validación (generado por firma) |
| `monto_entregado` | decimal | SÍ | ¿Se entregó dinero? |

---

## ⚙️ Lógica ACTUAL de las Fases (en `proceso.blade.php` línea 75-85)

```php
// FASE 1: Revisión administrativa → completa cuando presupuesto se confirma
$fase1Completada = $solicitud->presupuesto_confirmado || !is_null($solicitud->cuv);

// FASE 2: Firma directiva → completa cuando CUV se genera  
$fase2Completada = !is_null($solicitud->cuv) || $solicitud->presupuesto_confirmado;

// FASE 3: Cierre financiero → completa cuando hay monto entregado
$fase3Completada = !is_null($solicitud->monto_entregado);

// Fase activa = primera no completada
$faseActiva = !$fase1Completada ? 1 : (!$fase2Completada ? 2 : 3);
```

### ❌ PROBLEMAS:
1. **Fase 1 y 2 usan EXACTAMENTE las mismas condiciones** → ambas se marcan completadas simultáneamente
2. Si `presupuesto_confirmado = true` pero `cuv = null` → Fase 1 ✓ pero Fase 2 también ✓
3. Confunde "confirmación presupuestaria" con "generación de CUV"

---

## ✅ Flujo CORRECTO que debería ser

```
┌─────────────────────────────────────────┐
│ FASE 1: REVISIÓN ADMINISTRATIVA         │
│ ✓ Cuando: presupuesto_confirmado = 1   │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│ FASE 2: FIRMA DIRECTIVA (genera CUV)    │
│ ✓ Cuando: cuv ≠ NULL                   │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│ FASE 3: CIERRE FINANCIERO               │
│ ✓ Cuando: monto_entregado ≠ NULL       │
└─────────────────────────────────────────┘
```

---

## 🔧 Solución Propuesta

### Paso 1️⃣: Actualizar lógica en `proceso.blade.php` línea 75

```php
@php
    // FASE 1: Solo cuando presupuesto se confirma
    $fase1Completada = (bool) $solicitud->presupuesto_confirmado;
    
    // FASE 2: Solo cuando CUV se genera (requires Fase 1 complete)
    $fase2Completada = !is_null($solicitud->cuv) && $fase1Completada;
    
    // FASE 3: Solo cuando se entrega dinero (requires Fase 2 complete)
    $fase3Completada = !is_null($solicitud->monto_entregado) && $fase2Completada;
    
    // Fase activa = primera no completada
    $faseActiva = !$fase1Completada ? 1 : (!$fase2Completada ? 2 : 3);
    
    // DEBUG: Descomentar para ver estado
    // dump([
    //     'folio' => $solicitud->folio,
    //     'presupuesto_confirmado' => $solicitud->presupuesto_confirmado,
    //     'cuv' => $solicitud->cuv,
    //     'monto_entregado' => $solicitud->monto_entregado,
    //     'fase1Completada' => $fase1Completada,
    //     'fase2Completada' => $fase2Completada,
    //     'fase3Completada' => $fase3Completada,
    //     'faseActiva' => $faseActiva,
    // ]);
@endphp
```

---

## 🧪 Script SQL para DEBUGGEAR Estado Actual

```sql
-- Ver estado de TODAS las solicitudes
SELECT 
    s.folio,
    s.fk_id_apoyo,
    ap.nombre_apoyo,
    COALESCE(ces.nombre_estado, 'Sin estado') AS estado,
    s.presupuesto_confirmado AS [Fase1: Presupuesto?],
    s.cuv AS [Fase2: CUV],
    s.monto_entregado AS [Fase3: Monto Entregado],
    CASE 
        WHEN s.presupuesto_confirmado = 0 THEN '🔴 FASE 1 Pendiente'
        WHEN s.cuv IS NULL THEN '🟢 FASE 1 ✓ | 🟡 FASE 2 Pendiente'
        WHEN s.monto_entregado IS NULL THEN '🟢 FASE 1&2 ✓ | 🟡 FASE 3 Pendiente'
        ELSE '🟢 TODO COMPLETADO'
    END AS [Estado de Fases],
    s.fecha_creacion
FROM Solicitudes s
LEFT JOIN Apoyos ap ON s.fk_id_apoyo = ap.id_apoyo
LEFT JOIN Cat_EstadosSolicitud ces ON s.fk_id_estado = ces.id_estado
ORDER BY s.folio DESC;
```

---

## 📊 Entender QSCOPE: Estados vs Fases

### Estados (de `Cat_EstadosSolicitud`)
- Estos son **históricos/descriptivos** (Susbanación, Aprobado, Rechazado)
- Pueden cambiar VARIAS VECES en el ciclo de vida
- **Independientes** del flujo de fases

### Fases (lógica de `proceso.blade.php`)
- Estos son **lineales/secuenciales** (1 → 2 → 3)
- Cada fase debe completarse ANTES de pasar a la siguiente
- Controladas por campos específicos de BD

\### Ejemplo Real:
```
Solicitud Folio 1007:
- Estado: "Aprobado" (Cat_EstadosSolicitud)
- Fase: En Fase 1 (presupuesto_confirmado = false, cuv = null, monto_entregado = null)

Esto significa:
✓ El documento fue APROBADO administrativamente
✗ Pero aún no se confirmó el presupuesto en el formulario de Fase 1
```

---

## 🚀 Pasos para PROBAR y VERIFICAR

### 1. Ver el estado actual de una solicitud
```bash
# En el navegador, abre la consola (F12) y ejecuta:
document.querySelectorAll('[class*="bg-green"]')  # Ver cuáles están verdes
document.querySelectorAll('[class*="bg-blue"]')   # Ver cuáles están azules (activas)
```

### 2. Editar SQL directamente para SIMULAR fases completadas
```sql
-- Simular: Marcar Fase 1 como completa (Folio 1007)
UPDATE Solicitudes 
SET presupuesto_confirmado = 1 
WHERE folio = 1007;

-- Simular: Marcar Fase 2 como completa (genera un CUV dummy)
UPDATE Solicitudes 
SET cuv = 'CUV-2026-001-' + CAST(folio AS VARCHAR) 
WHERE folio = 1007;

-- Simular: Marcar Fase 3 como completa
UPDATE Solicitudes 
SET monto_entregado = 50000.00 
WHERE folio = 1007;
```

### 3. Refrescar y observar cómo cambian los colores

---

## 📋 Checklist de Verificación

- [ ] ¿Folio 1007 muestra Fase 1 en AZUL (activa) con borde azul?
- [ ] Al setear `presupuesto_confirmado = 1`, ¿se pone VERDE?
- [ ] ¿Fase 2 queda en GRIS (bloqueada) hasta que Fase 1 esté ✓?
- [ ] Al generar CUV en Fase 2, ¿se pone VERDE automáticamente?
- [ ] ¿Fase 3 solo se activa DESPUÉS de completar Fase 2?

---

## 🔗 Archivos Relacionados

- Vista: [recursos/views/solicitudes/proceso.blade.php](resources/views/solicitudes/proceso.blade.php#L75)
- Controlador: [app/Http/Controllers/SolicitudProcesoController.php](app/Http/Controllers/SolicitudProcesoController.php#L32)
- Servicio: [app/Services/SolicitudWorkflowService.php](app/Services/SolicitudWorkflowService.php)
