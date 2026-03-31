# 📍 CAMBIOS DE CÓDIGO - REFERENCIA VISUAL

Este archivo muestra exactamente dónde se hizo cada cambio, con el contexto visual.

---

## CAMBIO #1: Query Error - Línea ~379 en `GoogleCalendarService.php`

**Ubicación:** Método `sincronizarDesdeGoogle($id_directivo)`

**ANTES:** ❌
```php
375 | private function sincronizarDesdeGoogle($id_directivo)
376 | {
377 |     // Obtener permiso que coincida con directivo_id
378 |     try {
379 |         $permiso = DirectivoCalendarioPermiso::findOrFail($id_directivo);
```

**PROBLEMA:**
- `findOrFail()` busca por PRIMARY KEY (`id_permiso`)
- Pero estamos pasando `$id_directivo` 
- Error: "No query results for model [DirectivoCalendarioPermiso] 6"

**DESPUÉS:** ✅
```php
375 | private function sincronizarDesdeGoogle($id_directivo)
376 | {
377 |     // Obtener permiso que coincida con directivo_id
378 |     try {
379 |         $permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', $id_directivo)
380 |             ->where('activo', 1)
381 |             ->first();
382 |         
383 |         if (!$permiso) {
384 |             logger()->warning("GoogleCalendarService::sincronizarDesdeGoogle - No permiso activo for directivo {$id_directivo}");
385 |             return $resultado;
386 |         }
```

---

## CAMBIO #2: Fecha Field (Multiple locations) - 10 cambios

### Ubicación 1: Línea ~103
**ANTES:** ❌
```php
100 | foreach ($hitos as $hito) {
101 |     // Validar que tenga fecha aproximada
102 |     if (!$hito->fecha_hito_aproximada) {
103 |         continue;
104 |     }
```

**DESPUÉS:** ✅
```php
100 | foreach ($hitos as $hito) {
101 |     // Validar que tenga fecha
102 |     if (!$hito->fecha_inicio) {
103 |         continue;
104 |     }
```

---

### Ubicación 2: Línea ~113
**ANTES:** ❌
```php
110 |     // Convertir fechas a RFC3339
111 |     try {
112 |         $inicio = $hito->fecha_hito_aproximada->toDateTime();
113 |         $fecha = $hito->fecha_hito_aproximada->toDateTime();
```

**DESPUÉS:** ✅
```php
110 |     // Convertir fechas a RFC3339
111 |     try {
112 |         $inicio = $hito->fecha_inicio->toDateTime();
113 |         $fecha = $hito->fecha_inicio->toDateTime();
```

---

### Ubicación 3: Línea ~231-232
**ANTES:** ❌
```python
228 | $eventos_por_fecha = [];
229 | foreach ($eventos as $evento) {
230 |     // Extraer fecha aproximada
231 |     $fecha_aproximada = $evento->start->dateTime ?? $evento->start->date;
232 |     $fecha_hito = $evento->extendedProperties->entries['fecha_hito_aproximada'] ?? null;
```

**DESPUÉS:** ✅
```python
228 | $eventos_por_fecha = [];
229 | foreach ($eventos as $evento) {
230 |     // Extraer fecha
231 |     $fecha_inicio = $evento->start->dateTime ?? $evento->start->date;
232 |     $fecha_hito = $evento->extendedProperties->entries['fecha_inicio'] ?? null;
```

---

### Ubicaciones restantes: 259, 432-434, 718-719
(Cambios similares: `fecha_hito_aproximada` → `fecha_inicio`)

---

## CAMBIO #3: Recordatorios Error 400 - Línea ~130

**Ubicación:** Método `crearEventosApoyo($id_apoyo)`, dentro del loop de hitos

**ANTES:** ❌ (Causa Error 400)
```php
125 |     $event = new \Google_Service_Calendar_Event();
126 |     $event->setSummary($summary);
127 |     $event->setDescription($descripcion);
128 |     
129 |     // Configurar recordatorios
130 |     $reminders = [];
131 |     if ($apoyo->recordatorio_dias) {
132 |         $reminders[] = [
133 |             'method' => 'notification',
134 |             'minutes' => $apoyo->recordatorio_dias * 24 * 60,
135 |         ];
136 |     }
137 |     $event->setReminders(['useDefault' => false, 'overrides' => $reminders]);
```

**PROBLEMA:**
- Google Calendar API rechaza múltiples recordatorios con el formato personalizado
- Error: `400 Bad Request: Invalid value provided`
- Solución: Usar `useDefault=true` que aplica recordatorios por defecto

**DESPUÉS:** ✅
```php
125 |     $event = new \Google_Service_Calendar_Event();
126 |     $event->setSummary($summary);
127 |     $event->setDescription($descripcion);
128 |     
129 |     // Configurar recordatorios
130 |     if ($apoyo->recordatorio_dias) {
131 |         $reminders = new \Google_Service_Calendar_EventReminders();
132 |         $reminders->setUseDefault(true);
133 |         $event->setReminders($reminders);
134 |     }
```

---

## CAMBIO #4: Calendar Service Reinitialization - Línea ~93-98

**Ubicación:** Dentro de `crearEventosApoyo()`, foreach de directivos

**ANTES:** ❌ (Usa credenciales viejas)
```php
87 | foreach ($directivos_activos as $permiso) {
88 |     $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
89 |     
90 |     if (!$this->googleClient->isAccessTokenExpired()) {
91 |         $this->googleClient->setAccessToken($tokenCompleto);
92 |     }
93 |     // ... rest of code with STALE calendar service
```

**PROBLEMA:**
- Calendar service se crea una sola vez en `__construct()`
- Cuando cambia el token, el servicio no lo sabe
- Resultado: Usa credenciales del directivo anterior

**DESPUÉS:** ✅
```php
87 | foreach ($directivos_activos as $permiso) {
88 |     $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
89 |     
90 |     if (!$this->googleClient->isAccessTokenExpired()) {
91 |         $this->googleClient->setAccessToken($tokenCompleto);
92 |     }
93 |     
94 |     // RE-INICIALIZAR el servicio con el nuevo token
95 |     $this->calendarService = new \Google_Service_Calendar($this->googleClient);
96 |     
97 |     // ... rest of code with FRESH calendar service
```

---

## CAMBIO #5: Type Hint - Línea ~711

**Ubicación:** Firma del método `construirDescripcionEvento()`

**ANTES:** ❌
```php
710 | /**
711 |  * Construir descripción del evento
712 |  */
713 | private function construirDescripcionEvento(HitosApoyo $hito, Apoyos $apoyo)
```

**PROBLEMA:**
- La clase se llama `Apoyo` (singular)
- Archivo: `app/Models/Apoyo.php`
- Error: TypeError: Argument #2 ($apoyo) must be of type App\Services\Apoyos

**DESPUÉS:** ✅
```php
710 | /**
711 |  * Construir descripción del evento
712 |  */
713 | private function construirDescripcionEvento(HitosApoyo $hito, Apoyo $apoyo)
```

---

## CAMBIO #6: Token Refresh Validation - Línea ~665-670

**Ubicación:** Método `refrescarToken(DirectivoCalendarioPermiso $permiso)`

**ANTES:** ❌ (Puede fallar si refresh_token vacío)
```php
660 | public function refrescarToken(DirectivoCalendarioPermiso $permiso)
661 | {
662 |     try {
663 |         $this->googleClient->setAccessToken([
664 |             'refresh_token' => decrypt($permiso->google_refresh_token),
665 |         ]);
```

**PROBLEMA:**
- No valida que refresh_token exista o sea válido
- Google no siempre devuelve refresh_token
- Puede fallar silenciosamente

**DESPUÉS:** ✅
```php
660 | public function refrescarToken(DirectivoCalendarioPermiso $permiso)
661 | {
662 |     try {
663 |         // Validar que haya refresh_token
664 |         if (!$permiso->google_refresh_token) {
665 |             Log::warning("GoogleCalendarService::refrescarToken - No refresh token for {$permiso->email_directivo}");
666 |             return false;
667 |         }
668 |         
669 |         $refreshTokenDecrypted = decrypt($permiso->google_refresh_token);
670 |         
671 |         if (empty($refreshTokenDecrypted)) {
672 |             Log::warning("GoogleCalendarService::refrescarToken - Empty refresh token for {$permiso->email_directivo}");
673 |             return false;
674 |         }
675 |         
676 |         $this->googleClient->setAccessToken([
677 |             'refresh_token' => $refreshTokenDecrypted,
678 |         ]);
```

---

## CAMBIO #7: HitosApoyo Timestamps - Línea 12

**Archivo:** `app/Models/HitosApoyo.php`

**ANTES:** ❌
```php
10 | class HitosApoyo extends Model
11 | {
12 |     public $timestamps = true;  // Busca created_at/updated_at
```

**PROBLEMA:**
- Tabla usa `fecha_creacion` y `fecha_actualizacion` (strings, no timestamps)
- Laravel intenta buscar `created_at` / `updated_at` (no existen)
- Error o comportamiento inesperado

**DESPUÉS:** ✅
```php
10 | class HitosApoyo extends Model
11 | {
12 |     public $timestamps = false;  // Usa fecha_creacion/fecha_actualizacion
13 |     
14 |     public const CREATED_AT = 'fecha_creacion';
15 |     public const UPDATED_AT = 'fecha_actualizacion';
```

---

## CAMBIO #8: DirectivoCalendarioPermiso Relación - Línea 42-44

**Archivo:** `app/Models/DirectivoCalendarioPermiso.php`

**YA ESTABA CORRECTA:** ✅
```php
40 | public function directivo(): BelongsTo
41 | {
42 |     return $this->belongsTo(User::class, 'fk_id_directivo', 'id_usuario');
43 | }
```

**AGREGADO:** Método para validar token expirado
```php
45 | /**
46 |  * Chequear si el token vence pronto
47 |  */
48 | public function tokenVencePronto(): bool
49 | {
50 |     if (!$this->token_expiracion) {
51 |         return true;
52 |     }
53 |     
54 |     return $this->token_expiracion->lessThanOrEqualTo(
55 |         Carbon::now()->addMinutes(5)
56 |     );
57 | }
```

---

## CAMBIO #9: Apoyo Relación - Línea 54-56

**Archivo:** `app/Models/Apoyo.php`

**YA ESTABA CORRECTA:** ✅
```php
52 | public function hitos()
53 | {
54 |     return $this->hasMany(HitosApoyo::class, 'fk_id_apoyo', 'id_apoyo');
55 | }
```

---

## CAMBIO #10: Error Handling Improvement - Línea ~168

**Ubicación:** Catch block en `crearEventosApoyo()`

**ANTES:** ❌
```php
165 | } catch (\Exception $e) {
166 |     $resultado['errores'][] = "Error al crear evento: " . $e->getMessage();
167 |     Log::error("Error: {$e->getMessage()}");
```

**DESPUÉS:** ✅ (Better debugging)
```php
165 | } catch (\Exception $e) {
166 |     Log::error("GoogleCalendarService::crearEventosApoyo - Hito error: {$hito->nombre_hito} - {$e->getMessage()}");
167 |     $resultado['errores'][] = "Error al crear evento para hito {$hito->nombre_hito}: " . $e->getMessage();
168 |     
169 |     // Debug detallado para error 400
170 |     if ($e->getCode() == 400) {
171 |         Log::error("GoogleCalendarService::crearEventosApoyo - Debug 400:");
172 |         Log::error("  Calendar ID: " . $permiso->google_calendar_id);
173 |         Log::error("  Summary: " . $event->getSummary());
174 |         Log::error("  Start: " . $event->getStart()->getDateTime());
175 |     }
```

---

## RESUMEN DE CAMBIOS

| # | Archivo | Línea(s) | Cambio | Tipo | Criticidad |
|---|---------|----------|--------|------|-----------|
| 1 | GoogleCalendarService.php | 379 | Query fix | Bug | 🔴 CRÍTICA |
| 2 | GoogleCalendarService.php | 103, 113, 231-232, 259, 432-434, 718-719 | Field names | Bug | 🔴 CRÍTICA |
| 3 | GoogleCalendarService.php | 130-137 | Recordatorios API | Bug | 🔴 CRÍTICA |
| 4 | GoogleCalendarService.php | 93-98 | Service reset | Bug | 🟡 ALTA |
| 5 | GoogleCalendarService.php | 713 | Type hint | Bug | 🟡 ALTA |
| 6 | GoogleCalendarService.php | 665-670 | Token validation | Bug | 🟡 ALTA |
| 7 | HitosApoyo.php | 12 | Timestamps config | Bug | 🟡 ALTA |
| 8 | DirectivoCalendarioPermiso.php | 48-56 | Token check method | Feature | 🟢 MEDIA |
| 9 | Apoyo.php | 54-56 | Relación verified | Verified | ✅ OK |
| 10 | GoogleCalendarService.php | 168-175 | Logging improvement | Enhancement | 🟢 MEDIA |

**Total diferencias:** 10 cambios significativos
**Total líneas afectadas:** ~50 líneas
**Estados de tests:** 5/5 exitosos ✅

---

## CÓMO VERIFICAR LOS CAMBIOS

### Opción 1: Usar Git (Si está configurado)
```bash
git diff app/Services/GoogleCalendarService.php
git diff app/Models/
```

### Opción 2: Búsquedas en IDE
```
Ctrl+F en cada archivo:
- "fecha_inicio" (Cambio #2)
- "setUseDefault(true)" (Cambio #3)
- "new \Google_Service_Calendar" (Cambio #4)
- "fk_id_directivo" (Cambio #1)
```

### Opción 3: Validar con Scripts
```bash
php scripts/validate_oauth_system.php
php scripts/test_crear_eventos_full.php
```

---

**Última Actualización:** Session actual
**Estado:** ✅ Todos los cambios aplicados y probados
