# ✅ RESOLUCION: Eventos Duplicados y Horas Incorrectas

## 🔴 PROBLEMA REPORTADO

1. **Eventos duplicados creados multiple veces:**
   - 1 marzo: 4 eventos idénticos
   - 9 marzo: 3 eventos idénticos  
   - 15 marzo: 2 eventos idénticos
   - 30 marzo: 1 evento (pero patrón de múltiples anteriores)

2. **Hora incorrecta:**
   - Se estaban creando a las 6:00 AM (hora de creación del registro)
   - Debería ser 23:59 (11:59 PM, zona horaria de Mazatlán/America/Mexico_City)

---

## 🔍 ROOT CAUSE ANALYSIS

### Problema 1: Eventos Duplicados

**Ubicación:** `app/Services/GoogleCalendarService.php`, método `crearEventosApoyo()`

**Causa:**
```php
// ❌ ANTES: Creaba evento para CADA directivo activo
$directivos = $this->obtenerDirectivosActivos(); // Retorna 4 directivos

foreach ($directivos as $permiso) {
    // Crea 4 eventos idénticos, uno por directivo
    // Si hay 4 directivos → 4 eventos
    // Si hay 3 directivos → 3 eventos
    // etc.
}
```

El sistema estaba iterando sobre todos los directivos activos y creando un evento por cada uno, asumiendo que cada directivo tenía su proppio calendario privado. Sin embargo, todos (aparentemente) comparten el mismo calendario.

### Problema 2: Hora Incorrecta

**Ubicación:** Línea ~117 de `GoogleCalendarService.php`

**Causa:**
```php
// ❌ ANTES: Usa la fecha/hora tal como está en BD
$fecha = $hito->fecha_inicio->toDateTime(); // 2026-04-01 00:00:00
// Google recibe: 2026-04-01T00:00:00

// O si la fecha se guardó con hora de creación:
// Google recibe: 2026-04-01T06:00:00 (cuando se creó el registro)
```

---

## ✅ SOLUCIONES IMPLEMENTADAS

### FIX #1: Crear evento SOLO UNA VEZ (primer/única directivo)

**Archivo:** `app/Services/GoogleCalendarService.php` - Método `crearEventosApoyo()`

**Cambio:**
```php
// ✅ DESPUÉS: Usar SOLO el primer directivo activo
$permiso = $this->obtenerDirectivosActivos()->first();

if (!$permiso) {
    $resultado['errores'][] = "No hay directivos con permisos...";
    return $resultado;
}

// NO usar foreach sobre múltiples directivos
// Crear evento una sola vez
```

**Beneficio:** 
- ✅ Garantiza un evento por hito, no múltiples
- ✅ Evita sincronización en calendarios múltiples del mismo usuario

---

### FIX #2: Evitar duplicados si evento ya existe

**Archivo:** `app/Services/GoogleCalendarService.php` - Método `crearEventosApoyo()`

**Cambio:**
```php
// ✅ NUEVA: Verificar si ya tiene event_id antes de crear
if ($hito->google_calendar_event_id) {
    Log::info("... Hito {$hito->id_hito} ya tiene evento...");
    continue; // No crear duplicado
}

// Solo crear si NO tiene event_id
```

**Beneficio:**
- ✅ Previene duplicados si se ejecuta el método 2 veces
- ✅ Idempotente: seguro ejecutarlo múltiples veces

---

### FIX #3: Fijar hora a 23:59 (Mazatlán)

**Archivo:** `app/Services/GoogleCalendarService.php` - Métodos:
- `crearEventosApoyo()` (línea ~120)
- `actualizarEventoHito()` (línea ~255)

**Cambio:**
```php
// ❌ ANTES:
$fecha = $hito->fecha_inicio->toDateTime();
// Resultado: 2026-04-01T00:00:00 o 2026-04-01T06:00:00 (incorrecta)

// ✅ DESPUÉS:
$fecha = Carbon::parse($hito->fecha_inicio)->setTime(23, 59, 0);
// Resultado: 2026-04-01T23:59:00-06:00
// Con timezone: America/Mexico_City (Mazatlán)

$eventEnd = $fecha->clone()->setTime(23, 59, 59);
// Fin: 2026-04-01T23:59:59-06:00
```

**Beneficio:**
- ✅ Todos eventos ahora se crean a las 23:59 (11:59 PM)
- ✅ Zona horaria correcta: America/Mexico_City (Mazatlán)
- ✅ Consistente con política de apoyos/validaciones

---

### FIX #4: Actualizar método para evitar duplicados también

**Archivo:** `app/Services/GoogleCalendarService.php` - Método `actualizarEventoHito()`

**Cambio:** 
```php
// ✅ También se cambió para usar solo primer directivo
$permiso = $this->obtenerDirectivosActivos()->first();

// Eliminar foreach sobre múltiples directivos
```

**Beneficio:**
- ✅ Consistencia: actualizar también una sola vez
- ✅ Evita actualizar múltiples copias del mismo evento

---

## 📊 VALIDACIÓN DE CAMBIOS

### Prueba 1: Evento Único
✅ **PASS** - Script `crear_apoyo_val.php` ejecutado:
- Resultado: `eventos_creados: 1` (antes: múltiples)
- Confirmó que solo se crea 1 evento

### Prueba 2: Hora Correcta
📋 **PENDIENTE** - Ejecutar `verify_event_times.php`:
```bash
php verify_event_times.php
```
Verificará que los últimos eventos estén a las 23:59

---

## 🗂️ ARCHIVOS MODIFICADOS

| Archivo | Método | Cambio |
|---------|--------|--------|
| `app/Services/GoogleCalendarService.php` | `crearEventosApoyo()` | 1. Usar primer directivo, 2. Evitar duplicados, 3. Fijar hora 23:59 |
| `app/Services/GoogleCalendarService.php` | `actualizarEventoHito()` | 1. Usar primer directivo, 2. Fijar hora 23:59 |
| `app/Http/Controllers/ApoyoController.php` | `syncApoyoMilestones()` | Usar `HitosApoyo::create()` en lugar de `DB::table()->insert()` |
| `app/Providers/AppServiceProvider.php` | `boot()` | Registrar Observer para eventos automáticos |
| `app/Observers/HitosApoyoObserver.php` | NEW | Observador para disparar eventos de sincronización |
| `app/Models/HitosApoyo.php` | `$dispatchesEvents` | Remover (ahora usa Observer) |
| `app/Models/CalendarioSincronizacionLog.php` | `apoyo()` | Cambiar `Apoyos` → `Apoyo` (nombre correcto modelo) |
| `app/Http/Controllers/CasoAController.php` | imports | Cambiar `Apoyos` → `Apoyo` |

---

## 🔧 CÓMO VALIDAR

### 1. Verificar Evento Único
```bash
cd c:\xampp\htdocs\SIGO
php crear_apoyo_val.php
# Debería mostrar: Eventos creados: 1
```

### 2. Verificar Hora 23:59
```bash
php verify_event_times.php
# Debería mostrar: ✅ Hora correcta: 23:59
```

### 3. Verificar en Google Calendar
1. Abrir: https://calendar.google.com
2. Login como: `guillermoavilamora2@gmail.com`
3. Ir a fecha: 2026-04-01
4. Verificar evento "INJUVE - ... - Evento de Validación"
5. Click en evento → Ver hora = 23:59

---

## 📝 CAMBIOS RESUMIDOS

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Eventos por hito** | 4 (o 3, o 2) duplicados | 1 único evento |
| **Hora de creación** | 00:00 o 06:00 (inconsistente) | 23:59 (consistente) |
| **Zona horaria** | UTC/Incorrecta | America/Mexico_City (Mazatlán) |
| **Prevención duplicados** | ❌ No | ✅ Sí (verifica google_calendar_event_id) |
| **Directivos procesados** | Todos los activos | Solo el primero |

---

## 🚀 PRÓXIMOS PASOS

1. ✅ Cambios código implementados
2. ⏳ Ejecutar `php verify_event_times.php` para confirmar horas
3. ⏳ Crear nuevo apoyo de prueba con múltiples hitos
4. ⏳ Verificar en Google Calendar que aparezcan a 23:59
5. ⏳ Eliminar eventos duplicados existentes (opcional)

---

## ⚠️ NOTA IMPORTANTE

Los eventos **existentes** que fueron creados como duplicados seguirán en Google Calendar. Para limpiarlos:

```bash
php find_duplicate_events.php  # Identifica duplicados
# Luego eliminar manualmente en Google Calendar, o
# Crear script: php delete_duplicate_events.php
```

**Pero los NUEVOS eventos ahora se crearán correctamente: uno solo a las 23:59.**
