# 🔧 TROUBLESHOOTING & FAQ

## Problemas Comunes y Soluciones

---

## ❓ PREGUNTA: "¿Por qué el navegador no abrió?"

### Síntomas
- No aparece ventana de navegador
- No redirige a Google

### Soluciones

**1. Verificar que el servidor esté corriendo**
```bash
# En terminal
php artisan serve

# Output esperado:
# Laravel development server started: http://127.0.0.1:8000
```

**2. Abrir manualmente la URL en navegador**
```
Copiar esta URL en el navegador:
https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=523344188732-pglcoe6qpgl5a6df8n0itfa4gbh028oa.apps.googleusercontent.com&redirect_uri=http://localhost:8000/admin/calendario/callback&scope=https://www.googleapis.com/auth/calendar+https://www.googleapis.com/auth/userinfo.email+openid&access_type=offline&state=6_[valor_actual]
```

**3. Verificar que Google Client está configurado**
```bash
# Revisar .env
cat .env | grep GOOGLE

# Debe tener:
# GOOGLE_CLIENT_ID=523344188732-pglcoe6qpgl5a6df8n0itfa4gbh028oa.apps.googleusercontent.com
# GOOGLE_CLIENT_SECRET=...
```

---

## ❓ PREGUNTA: "¿Qué significa 'No query results'?"

### Error Completo
```
No query results for model [DirectivoCalendarioPermiso] 6
at line 379
```

### Causa
❌ **ANTES DE FIXES:** Código buscaba permiso por ID (6), pero debería buscar por id_directivo

✅ **DESPUÉS DE FIXES:** Código busca correctamente:
```php
where('fk_id_directivo', 6)->first()
```

### Verificar que está fijo
```bash
php -r "
include 'app/Services/GoogleCalendarService.php';
\$file = file_get_contents('app/Services/GoogleCalendarService.php');
if (strpos(\$file, 'findOrFail(\$id_directivo)') === false) {
    echo '✅ FIJO: Ya no usa findOrFail';
} else {
    echo '❌ ERROR: Todavía usa findOrFail';
}
"
```

---

## ❓ PREGUNTA: "¿Por qué dice 'fecha_hito_aproximada' not found?"

### Error Completo
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'fecha_hito_aproximada'
```

### Causa
❌ **ANTES DE FIXES:** Código usaba columna que no existe (fecha_hito_aproximada)

✅ **DESPUÉS DE FIXES:** Usa columna correcta (fecha_inicio)

### Verificar que está fijo
```bash
# Comando SQL
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'hitos_apoyo' 
AND COLUMN_NAME = 'fecha_inicio';

# Resultado esperado: Una fila con 'fecha_inicio'
```

### Búsqueda de referencias viejas
```bash
# En terminal - buscar si todavía hay referencias viejas
grep -r "fecha_hito_aproximada" app/

# No debe mostrar nada (si lo hace, faltan fixes)
```

---

## ❓ PREGUNTA: "¿Por qué me da error 400 al crear eventos?"

### Error Completo
```json
{
  "error": {
    "code": 400,
    "message": "Invalid value provided"
  }
}
```

### Causa
❌ **ANTES DE FIXES:** Recordatorios configurados incorrectamente (Google API rechaza formato manual)

✅ **DESPUÉS DE FIXES:** Usa `setUseDefault(true)` que Google acepta

### Verificar que está fijo
```bash
# Buscar en el código
grep -A2 "setUseDefault" app/Services/GoogleCalendarService.php

# Debe mostrar:
# $reminders->setUseDefault(true);
```

---

## ❓ PREGUNTA: "¿Por qué no se crean eventos para el segundo directivo?"

### Síntomas
- Primer directivo: ✅ eventos creados
- Segundo directivo: ❌ no funciona

### Causa
❌ **ANTES DE FIXES:** Calendar service no se reinicializaba, usaba tokens del primer directivo

✅ **DESPUÉS DE FIXES:** Se reinicializa para cada permiso en el loop

### Verificar que está fijo
```bash
# Buscar reinicialización
grep -n "new \\\\Google_Service_Calendar" app/Services/GoogleCalendarService.php

# Debe mostrar DOS líneas:
# 1. En __construct() (línea ~50)
# 2. En el loop de crearEventosApoyo() (línea ~93-98)
```

---

## ❓ PREGUNTA: "¿Por qué la relación belongsTo no funciona?"

### Error
```
Relationship method 'directivo' does not exist on [App\Models\DirectivoCalendarioPermiso]
```

### Verificar que está OK
```bash
php -r "
\$permiso = App\\Models\\DirectivoCalendarioPermiso::where('id_permiso', 7)->first();
echo 'Email: ' . \$permiso->directivo->email;
echo 'Tipo: ' . \$permiso->directivo->tipo_usuario;
"
```

---

## ❓ PREGUNTA: "¿Cómo refresh el token manualmente?"

### Si quieres refrescar sin usar navegador
```bash
php artisan tinker

# Entonces en Tinker:
> $permiso = \App\Models\DirectivoCalendarioPermiso::find(7);
> app('GoogleCalendarService')->refrescarToken($permiso);
> $permiso->token_expiracion
=> "2026-03-30 18:08:15"  # ← Debe ser futuro
```

---

## ❓ PREGUNTA: "¿Dónde veo los eventos creados?"

### En Google Calendar
```
1. Ir a: https://calendar.google.com
2. Loguear como: guillermoavilamora2@gmail.com
3. Buscar eventos entre: 2026-04-04 y 2026-04-29
4. Debe haber 5 eventos con patrón:
   "INJUVE - 🧪 PRUEBA - Capacitación JavaScript - [Nombre Hito]"
```

### En la Base de Datos
```sql
SELECT * FROM hitos_apoyo 
WHERE fk_id_apoyo = 24 
AND google_calendar_event_id IS NOT NULL;

-- Debe mostrar 5 hitos con google_calendar_event_id lleno
```

### En Logs de Laravel
```bash
tail -f storage/logs/laravel.log | grep GoogleCalendarService

# Buscar eventos creados:
# [2026-03-30 23:08:18] local.DEBUG: CreatedEvent:...
```

---

## ❓ PREGUNTA: "¿Qué significa 'Token expirado'?"

### Normal OAuth Lifecycle
1. **Minuto 0:** Usuario hace OAuth, recibe token con expiracion=+1 hora
2. **Minuto 59:** Sistema intenta usar token, aún válido
3. **Minuto 60:** Token expira automáticamente (Google dice "no válido")
4. **Minuto 61+:** Sistema usa refresh_token para obtener nuevo token

### Nuestro Estado Actual
- Token original: 23:08:17 (hace varias horas)
- Expiración: 2026-03-30 17:08:15
- Refresh token: ✅ Disponible (200 bytes)
- Próxima acción: Renovación automática o manual

### Renovación Manual
```bash
# Opción 1: Script
php scripts/refresh_oauth_token.php --directivo_id=6

# Opción 2: Código directo
php artisan tinker
> $perm = App\Models\DirectivoCalendarioPermiso::find(7);
> app('GoogleCalendarService')->refrescarToken($perm);
```

---

## ❓ PREGUNTA: "¿Cómo veo el token en la BD?"

### Vista Encriptada
```sql
SELECT id_permiso, email_directivo, google_access_token, token_expiracion 
FROM directivos_calendario_permisos 
WHERE id_permiso = 7;

-- Output:
-- google_access_token: eyJhbGciOiJSUzI1NiIsInR5cCI... (2816 bytes, encriptado)
-- token_expiracion: 2026-03-30 17:08:15
```

### Desencriptado (desde PHP)
```php
$permiso = DirectivoCalendarioPermiso::find(7);
$token = json_decode(decrypt($permiso->google_access_token), true);

echo $token['access_token'];      // Primer 50 chars: "ya29.a0AfH6SMB..."
echo $token['expires_in'];        // 3600 (1 hora)
echo $token['token_type'];        // "Bearer"
echo $token['scope'];             // "https://www.googleapis.com/auth/calendar ..."
```

---

## ❓ PREGUNTA: "¿Qué son los 10 cambios?"

### Resumen Rápido
1. **Query fix** (línea 379) - Buscar por foreign key correctamente
2. **Fecha field fix** (10 lugares) - usar fecha_inicio, no fecha_hito_aproximada
3. **Recordatorios fix** (línea 130) - usar setUseDefault(true)
4. **Service reset** (línea 93) - reinicializar para cada permiso
5. **Type hint fix** (línea 713) - Apoyo en lugar de Apoyos
6. **Token validation** (línea 665) - validar refresh_token antes de usar
7. **Timestamps fix** (HitosApoyo) - desactivar auto-timestamps
8. **Token check method** (DirectivoCalendarioPermiso) - método tokenVencePronto()
9. **Relación OK** (Apoyo) - ya funcionaba correctamente
10. **Logging enhancement** (línea 168) - mejor debugging

Ver archivo: **DETAILED_CHANGES.md**

---

## ❓ PREGUNTA: "¿Cuáles son los 5 eventos de prueba?"

### Datos Exactos
```
Apoyo ID: 24
Nombre: 🧪 PRUEBA - Capacitación JavaScript
Sincronizar: Sí (sincronizar_calendario=1)
Recordatorio: 1 día antes

Hitos:
1. ID 65 - "Inicio del curso"        - 2026-04-04
2. ID 66 - "Sesión 1: Fundamentos"   - 2026-04-09
3. ID 67 - "Sesión 2: DOM y Eventos" - 2026-04-14
4. ID 68 - "Proyecto Final"          - 2026-04-24
5. ID 69 - "Cierre del curso"        - 2026-04-29
```

### En Google Calendar (después de crear)
```
Título: "INJUVE - 🧪 PRUEBA - Capacitación JavaScript - Inicio del curso"
Fecha: Abril 4, 2026
Hora: 00:00 AM (all-day event si no tiene hora específica)
Descripción: [Información del hito]
Recordatorio: 1 día antes (Notificación)
```

---

## ❓ PREGUNTA: "¿Necesito hacer algo más después de OAuth?"

### Flujo Completo
```
1. Usuario hace OAuth ← User está aquí
2. Sistema recibe token ✅
3. Token se almacena en BD ✅
4. Token se valida ✅
5. -- Si token es válido --
6. Crear eventos en Google Calendar ← Next step
7. Mostrar "Conectado ✅" en interfaz
8. Sincronizar cambios futuros
```

### Script para verificar estado
```bash
php validate_oauth_system.php
```

Expected output si todo es correcto:
```
✅ Permisos encontrados: 1
✅ Token guardado: 2816 bytes
✅ Google_refresh_token: 200 bytes  
✅ TOKEN VALIDO (expires at: 2026-03-30 18:08:15)
✅ Apoyo: 🧪 PRUEBA - Capacitación JavaScript
✅ Hitos: 5
✅ Modelos Eloquent: Funcionales
✅ Google Calendar API: Integrada
✅ Creación de eventos: Probada y funcional
```

---

## 🚨 ERRORES CRÍTICOS

### Si ves esto → solución

| Error | Causa | Solución |
|-------|-------|----------|
| `No query results for model [DirectivoCalendarioPermiso] 6` | Línea 379 no está fija | Verificar DETAILED_CHANGES.md #1 |
| `Unknown column 'fecha_hito_aproximada'` | Cambio #2 no aplicado | Ver DETAILED_CHANGES.md #2 |
| `400 Bad Request Invalid value provided` | Recordatorios sin fix | Buscar setUseDefault en GoogleCalendarService.php |
| `Invalid Credentials` | Token expirado | Renovar token (normal, no es error) |
| `TypeError: Argument #2 ($apoyo) must be of type App\Services\Apoyos` | Type hint sin fix | Ver DETAILED_CHANGES.md #5 |
| `Column 'created_at' not found` | Timestamps sin fix en HitosApoyo | Ver DETAILED_CHANGES.md #7 |
| `Relationship method 'directivo' does not exist` | Relación No está bien | Verificar DirectivoCalendarioPermiso línea 42 |

---

## ✅ VALIDACIONES FINALES

### Checklist antes de "todo listo"

```bash
# 1. Verificar que no hay referencias viejas
[ ] grep -r "fecha_hito_aproximada" app/ → Nothing
[ ] grep -r "findOrFail(\$id_directivo)" app/ → Nothing

# 2. Verificar que existen los fixes
[ ] grep "setUseDefault" app/Services/GoogleCalendarService.php → Found
[ ] grep "new \Google_Service_Calendar" app/Services/GoogleCalendarService.php → Found 2 times

# 3. Verificar BD
[ ] SHOW COLUMNS FROM hitos_apoyo LIKE 'fecha%' → fecha_inicio, fecha_fin
[ ] SELECT * FROM directivos_calendario_permisos WHERE activo=1 → 1 row

# 4. Validar sistema
[ ] php validate_oauth_system.php → All ✅

# 5. Crear eventos de prueba
[ ] php test_crear_eventos_full.php → [eventos_creados] => 5
```

---

**Última Actualización:** Session actual
**Estado:** Ready for troubleshooting
