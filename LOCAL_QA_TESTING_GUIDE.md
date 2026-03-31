# SIGO Google Calendar Integration - Local QA Testing Guide

## Overview
Este documento proporciona una estrategia completa para validar la integración de Google Calendar con SIGO antes del despliegue a Azure.

**Estado Actual:** ✅ Código completado + Tests creados + Infraestructura en progreso

**Próximo Paso:** Resolver dependencias de Composer → Ejecutar tests locales

---

## Parte 1: Preparación del Entorno

### 1.1 Verificar Instalación de Dependencias

```bash
# Limpiar cache de composer
composer clear-cache

# Reinstalar dependencias
composer install

# Verificar que google/apiclient está instalado
composer show | grep google/apiclient
# Esperado: google/apiclient v2.19.1

# Verificar clases de Google
php artisan tinker
>>> use Google\Client;
>>> echo "Google Client OK";
>>> exit
```

### 1.2 Verificar Autoload de Laravel

```bash
# Generar autoloader de composer
composer dump-autoload

# Optimizar autoloader para producción
composer dump-autoload --optimize
```

### 1.3 Preparar Base de Datos

```bash
# Ejecutar SOLO la migración de Google Calendar
php artisan migrate --path="database/migrations/2026_03_28_000000_add_google_calendar_fields.php"

# Verificar columnas fueron agregadas
php artisan tinker
>>> Schema::hasColumn('Hitos_Apoyo', 'google_calendar_event_id')
>>> Schema::hasColumn('Apoyos', 'sincronizar_calendario')
```

---

## Parte 2: Tests Automatizados

### 2.1 Ejecutar Tests Unitarios (GoogleCalendarService)

```bash
# Ejecutar tests unitarios
php artisan test tests/Unit/GoogleCalendarServiceTest.php

# Esperado Output:
# ✓ test_google_calendar_service_instantiates
# ✓ test_token_expiracion_validation
# ✓ test_token_expira_pronto_validation
# ✓ test_token_valido_no_expirado
# ✓ test_directivo_calendario_permiso_encryption
# ✓ test_apoyo_sincronizacion_fields
# ✓ test_hitos_apoyo_google_fields
# ✓ test_sync_audit_logging
```

### 2.2 Ejecutar Tests de Integración (Controllers & Events)

```bash
# Ejecutar tests de integración
php artisan test tests/Feature/GoogleCalendarIntegrationTest.php

# Esperado Output:
# ✓ test_hito_cambio_event_is_dispatched
# ✓ test_hitos_apoyo_model_has_sync_methods
# ✓ test_apoyo_has_hitos_relationship
# ✓ test_directivo_calendario_permiso_token_validation
# ✓ test_user_model_has_calendario_permiso_relationship
# ✓ test_hitos_apoyo_scopes
# ✓ test_sync_google_calendar_command_can_run
```

### 2.3 Ejecutar Todos los Tests

```bash
# Ejecutar toda la suite de tests
php artisan test

# Con output detallado
php artisan test --verbose

# Con reporteHTML
php artisan test --html=tests/report.html
```

---

## Parte 3: Validación Manual (Paso a Paso)

### 3.1 Pre-Requisitos Manuales

1. **Crear Cuenta de Test en Google Cloud Console**
   - Crear proyecto nuevo en https://console.cloud.google.com
   - Habilitar Google Calendar API
   - Generar credenciales OAuth 2.0 (Aplicación web)
   - Descargar JSON con client_id y client_secret
   - Configurar `.env` con credenciales

2. **Crear Usuario Directivo de Test**
   
```bash
php artisan tinker
>>> $user = User::create([
...   'nombre_usuario' => 'directivo_test',
...   'correo_electronico' => 'directivo.test@example.com',
...   'tipo_usuario' => 'Directivo',
...   'id_rol' => 3,
... ])
>>> $user->id_usuario
# Anotar el ID del usuario para pasos siguientes
```

3. **Crear Apoyo de Test con Sincronización Habilitada**

```bash
php artisan tinker
>>> $apoyo = Apoyo::create([
...   'nombre_apoyo' => 'Test Apoyo con Calendar',
...   'descripcion' => 'Apoyo para validar sincronización de Google Calendar',
...   'sincronizar_calendario' => true,
...   'recordatorio_dias' => 3,
...   'google_group_email' => 'test-apoyo@example.com',
... ])
>>> $apoyo->id_apoyo
# Anotar el ID del apoyo
```

### 3.2 Test 1: OAuth Flow Completo

**Objetivo:** Verificar que el flujo OAuth 2.0 se completa correctamente

```
1. Acceder a la interfaz de configuración del calendario:
   URL: http://localhost:8000/calendario/configuracion
   
2. Hacer click en "Conectar con Google"
   - Se debe redirigir a Google Login
   - Se debe mostrar pantalla de consentimiento de permisos
   
3. Autorizar en la pantalla de Google
   - Verificar permisos solicitados: Google Calendar (lectura/escritura)
   
4. Completar OAuth Callback
   - Se debe redirigir de vuelta a http://localhost:8000/calendario/configuracion/callback
   - Se debe mostrar mensaje: "✓ Conexión exitosa"
   - Base de datos debe tener entrada en DirectivoCalendarioPermiso
```

**Validación:**

```bash
php artisan tinker
>>> $user->calendarioPermiso
>>> $user->calendarioPermiso->access_token  # Debe estar encriptado
>>> $user->calendarioPermiso->activo  # Debe ser true
```

### 3.3 Test 2: Creación Automática de Evento (Evento Disparado)

**Objetivo:** Verificar que al crear un hito, se genera evento en Google Calendar automáticamente

```bash
php artisan tinker

# 1. Crear hito (el evento debe dispararse automáticamente)
>>> $hito = HitosApoyo::create([
...   'fk_id_apoyo' => 1,  # ID del apoyo de test
...   'nombre_hito' => 'Test Hito con Calendar Event',
...   'descripcion' => 'Hito para validar creación automática de evento',
...   'fecha_inicio' => now(),
...   'fecha_fin' => now()->addDays(7),
...   'google_calendar_sync' => true,
... ])

# 2. Verificar que el evento fue creado en Google
>>> $hito->refresh()
>>> $hito->google_calendar_event_id  # Debe tener valor (no null)
>>> $hito->ultima_sincronizacion  # Debe tener timestamp actual
```

**Validación en Google Calendar:**
- Abrir https://calendar.google.com
- Buscar evento con nombre del hito
- Verificar que tiene los valores correctos (fecha, descripción, recordatorio)

### 3.4 Test 3: Actualización de Evento

**Objetivo:** Verificar que cambios en SIGO se replican a Google Calendar

```bash
# Actualizar el hito creado
>>> $hito->update([
...   'descripcion' => 'Descripción actualizada desde SIGO',
...   'fecha_fin' => now()->addDays(14),  # Extender duración
... ])

# Verificar cambios
>>> $hito->cambios_locales_pendientes  # Debe ser true inicialmente
>>> $hito->ultima_sincronizacion  # Se actualiza después que el listener procesa
```

**Validación en Google Calendar:**
- Abrir el evento en Google Calendar
- Verificar que la descripción y fecha de fin se actualizaron

### 3.5 Test 4: Scheduler Job (Sincronización Google → SIGO)

**Objetivo:** Verificar que el scheduler sincroniza cambios de Google Calendar a SIGO

```bash
# Ejecutar manualmente el scheduler job
php artisan sync:google-calendar

# Output esperado:
# ✓ Iniciando sincronización de Google Calendar...
# ✓ Directivos procesados: 1
# ✓ Cambios sincronizados: X
# ✓ Errores: 0

# Verificar logs
tail -f storage/logs/laravel.log | grep "calendario"
```

### 3.6 Test 5: Logs de Sincronización

**Objetivo:** Verificar que todos los cambios se registran en la tabla de auditoría

```bash
php artisan tinker

# Ver logs de sincronización
>>> CalendarioSincronizacionLog::latest()->first()

# Esperado:
>>> $log->operacion  # 'creacion', 'actualizacion', 'eliminacion'
>>> $log->estado     # 'success', 'error'
>>> $log->elementos_procesados  # Número de eventos procesados

# Ver todos los logs del último sincronización
>>> CalendarioSincronizacionLog::where('operacion', 'actualizacion')
...   ->orderBy('created_at', 'desc')
...   ->get()
```

### 3.7 Test 6: Validar Relaciones de Modelos

**Objetivo:** Verificar que todas las relaciones entre modelos funcionan correctamente

```bash
php artisan tinker

# Relación: Directivo → Calendario Permiso
>>> $directivo = User::find(1)  # ID del directivo test
>>> $permiso = $directivo->calendarioPermiso
>>> $permiso->access_token !== null  # Debe estar encriptado

# Relación: Apoyo → Hitos
>>> $apoyo = Apoyo::find(1)  # ID del apoyo test
>>> $hitos = $apoyo->hitos
>>> $hitos->count()  # Debe tener >= 1

# Relación: Apoyo → Logs de Sincronización
>>> $logs = $apoyo->sincronizacionLogs
>>> $logs->count()  # Debe tener >= 1

# Relación: Hito → Apoyo
>>> $hito = HitosApoyo::find(1)
>>> $apoyo = $hito->apoyo
>>> $apoyo->nombre_apoyo  # Debe mostrar nombre del apoyo

# Relación: Hito → Logs
>>> $logs = $hito->sincronizacionLogs()
```

### 3.8 Test 7: Validar Scopes

**Objetivo:** Verificar que los scopes personalizados funcionan correctamente

```bash
php artisan tinker

# Scope: pendienteSincronizacion (hitos con cambios pendientes)
>>> $pendientes = HitosApoyo::pendienteSincronizacion()->get()
>>> $pendientes->count()

# Scope: sincronizacionActiva (hitos con sync habilitada)
>>> $activos = HitosApoyo::sincronizacionActiva()->get()
>>> $activos->count()

# Scope: sincronizacionHabilitada (apoyos con sync habilitado)
>>> $activos_apoyos = Apoyo::sincronizacionHabilitada()->get()
>>> $activos_apoyos->count()
```

---

## Parte 4: Validación de Seguridad

### 4.1 Validar Encriptación de Tokens

```bash
php artisan tinker

# Verificar que tokens están encriptados en la base de datos
>>> $permiso = DirectivoCalendarioPermiso::first()
>>> $permiso->getRawAttributes()['access_token']  # Debe ver texto encriptado

# Verificar que Laravel descryptan automáticamente
>>> $permiso->access_token  # Debe mostrar token legible
```

### 4.2 Validar CSRF en OAuth Flow

```
Durante el Test 3.2 (OAuth Flow):
1. Abrir DevTools → Application → Cookies
2. Verificar que existe cookie 'oauth_state'
3. Verificar que valor en URL de callback coincide con cookie
```

### 4.3 Validar Permisos de Rol

```bash
php artisan tinker

# Solo Directivos (Rol 3) deben poder conectar calendario
>>> $directivo = User::where('id_rol', 3)->first()
>>> auth()->login($directivo)
>>> Gate::authorize('conectar-calendario')  # Debe ser true

>>> $admin = User::where('id_rol', 0)->first()
>>> auth()->login($admin)
>>> Gate::authorize('conectar-calendario')  # Debe ser false
```

---

## Parte 5: Validación de Performance

### 5.1 Medir Tiempo de Sincronización

```bash
# Comando con timing
time php artisan sync:google-calendar

# Esperado: < 30 segundos para 1-10 directivos
# Si > 60 segundos, revisar:
# 1. Velocidad de conexión
# 2. Número de eventos en Google Calendar
# 3. Config de Google API quotas
```

### 5.2 Verificar Uso de API Quota

```bash
# Monitorear en Google Cloud Console
# https://console.cloud.google.com/apis/dashboard

# Buscar "Google Calendar API"
# Verificar:
# - Quotas utilizadas
# - Rate limits
# - Errores 429 (rate limit exceeded)
```

---

## Parte 6: Checklist Final de Validación

### Pre-Despliegue a Azure
- [ ] ✅ OAuth flow completo (Test 3.2)
- [ ] ✅ Creación automática de evento (Test 3.3)
- [ ] ✅ Actualización de evento (Test 3.4)
- [ ] ✅ Scheduler job funciona (Test 3.5)
- [ ] ✅ Logs almacenados correctamente (Test 3.6)
- [ ] ✅ Relaciones de modelos funcionan (Test 3.7)
- [ ] ✅ Scopes personalizados funcionan (Test 3.8)
- [ ] ✅ Tokens encriptados correctamente (Test 4.1)
- [ ] ✅ CSRF validado en OAuth (Test 4.2)
- [ ] ✅ Permisos de rol aplicados (Test 4.3)
- [ ] ✅ Performance aceptable (Test 5.1)
- [ ] ✅ No hay errores de API quota (Test 5.2)
- [ ] ✅ Todos los tests automatizados pasan
- [ ] ✅ No hay excepciones en logs storage/logs/laravel.log
- [ ] ✅ Documentación GOOGLE_CALENDAR_AUTO_SYNC_GUIDE.md revisada

---

## Parte 7: Resolución de Problemas Comunes

### Error: "Class Google\Client not found"
**Causa:** google/apiclient no está instalado
**Solución:**
```bash
composer clear-cache
composer install
composer dump-autoload --optimize
```

### Error: "CSRF token mismatch" en OAuth callback
**Causa:** Cookie de estado no coincide
**Solución:**
1. Limpiar cookies del navegador
2. Reintentar OAuth flow
3. Verificar que session.php tiene driver correcto

### Error: "Access token expired" en sincronización
**Causa:** Token de Google expirado
**Solución:**
- El código está diseñado para auto-renovar tokens
- Si sigue occuriendo, verificar que campo token_expiracion está actualizado en DB

### Error: "Quota exceeded" en Google Calendar API
**Causa:** Se alcanzó límite de API calls
**Solución:**
1. Esperar a que se restablezca cuota (generalmente 1 hora)
2. Para desarrollo: usar cuentas de prueba ilimitadas
3. Para producción: solicitar aumento de cuota a Google

### Error: "Table does not exist" en migración
**Causa:** SIGO ya tiene tablas existentes
**Solución:**
- Usar: `php artisan migrate --path=`  para migración específica
- Las checks en la migración evitarán agregar columnas duplicadas

---

## Parte 8: Ejecución Recomendada

### Orden Sugerido (Paralelo a Desarrollo):

**Fase 1: Infraestructura (15 min)**
```bash
composer clear-cache && composer install
composer dump-autoload --optimize
php artisan migrate --path="database/migrations/2026_03_28_000000_add_google_calendar_fields.php"
```

**Fase 2: Tests Automatizados (10 min)**
```bash
php artisan test tests/Unit/GoogleCalendarServiceTest.php
php artisan test tests/Feature/GoogleCalendarIntegrationTest.php
```

**Fase 3: Tests Manuales (45 min)**
- Ejecutar Tests 3.2 a 3.8 en orden secuencial
- Tomar screenshots de resultados

**Fase 4: Validación de Seguridad (15 min)**
- Ejecutar Tests 4.1 a 4.3

**Fase 5: Performance (10 min)**
- Ejecutar Tests 5.1 a 5.2

**Total Tiempo Estimado:** 90 minutos (testing local completo)

---

## Documentos de Referencia

- 📖 [GOOGLE_CALENDAR_AUTO_SYNC_GUIDE.md](./GOOGLE_CALENDAR_AUTO_SYNC_GUIDE.md) - Guía técnica completa
- 📖 [ADMINISTRATIVE_MODULE_GUIDE.md](./ADMINISTRATIVE_MODULE_GUIDE.md) - Guía del módulo administrativo
- 🔗 [Google Calendar API Docs](https://developers.google.com/calendar)
- 🔗 [Laravel Testing Docs](https://laravel.com/docs/12.x/testing)

---

**Estado:** ✅ Listo para testing local  
**Próximo Paso:** Resolver dependencias de Composer → Ejecutar Fase 1  
**Después:** Azure Deployment (deferred por usuario)
