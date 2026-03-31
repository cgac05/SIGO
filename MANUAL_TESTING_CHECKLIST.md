# SIGO Google Calendar - Pruebas Manuales Interactivas

**Fecha:** 28 de Marzo de 2026  
**Etapa:** Fase 2 - Validación Local  
**Estado:**🔄 INICIANDO PRUEBAS

---

## Preparación Pre-Pruebas (5 minutos)

### Paso 1: Ejecutar Setup de Datos de Prueba

```bash
cd c:\xampp\htdocs\SIGO
php setup_manual_tests.php
```

**Esperado:**
```
✅ Google Client cargado correctamente
✅ Hitos_Apoyo.google_calendar_event_id
✅ Hitos_Apoyo.google_calendar_sync
✅ Apoyos.sincronizar_calendario
✅ Directivo: ID=X
✅ Apoyo: ID=X
✅ Hito: ID=X
✅ Permiso: ID=X
```

**Si aparece error de migración:** Ejecutar primero:
```bash
php artisan migrate --path="database/migrations/2026_03_28_000000_add_google_calendar_fields.php"
```

### Paso 2: Iniciar Servidor Laravel

```bash
php artisan serve
```

**Información importante:**
- Servidor en: http://localhost:8000
- Mantener terminal abierta durante todas las pruebas
- Presionar Ctrl+C para detener (cuando terminen todas las pruebas)

### Paso 3: Acceder a la Aplicación

Abrir en navegador: **http://localhost:8000**

Verificar que:
- [ ] Página de inicio carga correctamente
- [ ] No hay errores de conexión
- [ ] SIGO se muestra con interfaz normal

---

## Test 1: OAuth Flow Completo (10 minutos)

**Objetivo:** Verificar que el flujo de autenticación con Google Function correctamente

### Pre-requisitos:
1. Tener una cuenta Google (personal o de prueba)
2. Haber configurado Google Cloud Console:
   - Proyecto creado
   - Google Calendar API habilitada
   - OAuth 2.0 Credentials (Aplicación Web) creadas
   - `.env` configurado con GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI

### Pasos:

**1. Acceder a Configuración del Calendario**
```
URL: http://localhost:8000/admin/calendario
```

**PRE-REQUISITO:** Estar autenticado como Directivo (Rol 3)
- Si ve página de login: http://localhost:8000/login
- Usar credenciales de directivo creadas en setup

**Resultado esperado:**
- [ ] Se carga la página de configuración
- [ ] Se muestra botón "Conectar con Google"
- [ ] Se muestra estado: "No conectado"
- [ ] No hay errores en la consola

**2. Hacer Clic en "Conectar con Google"**
- [ ] Se redirige a Google Login
- [ ] Se muestra pantalla de consentimiento de Google
- [ ] Se solicita permiso para acceder a Google Calendar

**3. Autorizar en Google**
- [ ] Seleccionar cuenta Google
- [ ] Hacer clic en "Permitir"
- [ ] Se redirige de vuelta a SIGO

**Resultado esperado después del OAuth:**
- [ ] Se carga: http://localhost:8000/calendario/configuracion
- [ ] Se muestra mensaje: **"✅ Conexión exitosa"**
- [ ] Se muestra el estado: **"Conectado"**
- [ ] Aparece información del directivo conectado

### Validación en Base de Datos:

```bash
php artisan tinker
```

```php
>>> $permiso = App\Models\DirectivoCalendarioPermiso::where('fk_id_directivo', 1)->first()
>>> $permiso->activo // Debe ser 1 (verdadero)
>>> $permiso->access_token !== null // Debe ser true (token encriptado)
>>> $permiso->refresh_token !== null // Debe ser true
```

**✅ Test 1 PASÓ** si todo está conectado correctamente

---

## Test 2: Creación Automática de Evento (Evento Disparado) (10 minutos)

**Objetivo:** Verificar que al crear un nuevo hito, se genera automáticamente un evento en Google Calendar

### Pasos:

**1. En la aplicación, crear un nuevo Hito**

Navegar a: **Apoyos → [Seleccionar Apoyo Test] → Agregar Hito**

Llenar formulario:
```
Nombre:       "Test Evento Automático"
Descripción:  "Evento creado para validar sincronización"
Fecha Inicio: Hoy
Fecha Fin:    Mañana
Sincronizar:  ☑ (Habilitado)
```

Hacer clic: **"Crear Hito"**

**Resultado esperado:**
- [ ] Hito se crea exitosamente
- [ ] Se muestra el nuevo hito en la lista
- [ ] No hay errores en la aplicación
- [ ] No hay errores en `storage/logs/laravel.log`

**2. Verificar que el evento se creó en Google Calendar**

Abrir en otra pestaña: **https://calendar.google.com**

Buscar el evento:
- [ ] Evento con nombre: "Test Evento Automático"
- [ ] Fecha correcta (Hoy a Mañana)
- [ ] Contiene descripción del apoyo
- [ ] Tiene recordatorio (3 días antes por defecto)

**3. Verificar en Base de Datos**

```bash
php artisan tinker
```

```php
>>> $hito = App\Models\HitosApoyo::where('nombre_hito', 'Test Evento Automático')->first()
>>> $hito->google_calendar_event_id // Debe tener un valor (no null)
>>> $hito->ultima_sincronizacion // Debe tener timestamp reciente
>>> $hito->google_calendar_sync // Debe ser true (1)
```

**✅ Test 2 PASÓ** si el evento existe en Google Calendar y en BD

---

## Test 3: Actualización de Evento (10 minutos)

**Objetivo:** Verificar que cambios en SIGO se replican a Google Calendar automáticamente

### Pasos:

**1. Editar el Hito creado en Test 2**

En SIGO, acceder a: **Apoyos → [Apoyo Test] → Editar Hito "Test Evento Automático"**

Cambiar:
```
Descripción:  "ACTUALIZADA: Esta es la nueva descripción"
Fecha Fin:    14 días después (en lugar de mañana)
```

Guardar: **"Actualizar Hito"**

**Resultado esperado:**
- [ ] Hito se actualiza exitosamente
- [ ] Se muestra confirmación en SIGO
- [ ] No hay errores

**2. Verificar cambios en Google Calendar**

Abrir: **https://calendar.google.com**

Buscar el evento "Test Evento Automático":
- [ ] Descripción está actualizada
- [ ] Fecha de fin es correcta (14 días después)
- [ ] Otros detalles persisten correctamente

**Nota:** Puede haber un ligero delay (1-30 segundos) antes de que Google Calendar refleje los cambios.

**3. Verificar en Base de Datos**

```php
>>> $hito->refresh()
>>> $hito->ultima_sincronizacion // Debe ser timestamp MÁS RECIENTE que en Test 2
>>> $hito->cambios_locales_pendientes // Debe ser false (se sincronizó)
```

**✅ Test 3 PASÓ** si los cambios aparecen en Google Calendar

---

## Test 4: Scheduler Job - Sincronización de Google a SIGO (10 minutos)

**Objetivo:** Verificar que cambios en Google Calendar se sincronizan de vuelta a SIGO

### Pasos:

**1. Crear evento DIRECTAMENTE en Google Calendar**

Acceder a: **https://calendar.google.com**

Crear evento nuevo:
```
Título:       "Evento Google Directo"
Descripción:  "Creado directamente en Google para probar reverse sync"
Fecha:        Mañana
Hora:         10:00 AM
```

Guardar evento.

**2. Ejecutar comando de sincronización manualmente**

En terminal (nueva ventana, NO cerrar la del servidor):

```bash
cd c:\xampp\htdocs\SIGO
php artisan sync:google-calendar
```

**Resultado esperado:**
```
✓ Iniciando sincronización de Google Calendar...
✓ Directivos procesados: 1
✓ Cambios sincronizados: 1
✓ Errores: 0
```

**3. Verificar en SIGO que el evento fue sincronizado**

Acceder a: **http://localhost:8000/calendario/logs**

Ver logs recientes:
- [ ] Mostrar operación: "sincronizacion_inversa" o similar
- [ ] Estado: "success"
- [ ] Elementos procesados: >= 1

**Alternativa - Verificar en Tinker:**

```php
>>> $log = App\Models\CalendarioSincronizacionLog::latest()->first()
>>> $log->operacion // Debe mostrar la operación
>>> $log->estado // Debe ser "success"
>>> $log->elementos_procesados // >= 1
```

**✅ Test 4 PASÓ** si el scheduler ejecutó correctamente

---

## Test 5: Verificar Logs de Sincronización (5 minutos)

**Objetivo:** Validar que todos los cambios se registran en la tabla de auditoría

### Pasos:

**1. Acceder a Logs en SIGO**

URL: **http://localhost:8000/calendario/logs**

**Verificar que muestra:**
- [ ] Lista de todas las sincronizaciones
- [ ] Filtros funcionan (por estado, rango de fechas)
- [ ] Paginación funciona
- [ ] Estadísticas totales mostradas

**2. Revisar Estructura de Logs**

Para cada entrada visible, debe mostrar:
- [ ] Tipo de operación (creación, actualización, eliminación)
- [ ] Estado (success/error)
- [ ] Número de elementos procesados
- [ ] Fecha y hora

**3. Validar en Base de Datos**

```php
>>> $logs = App\Models\CalendarioSincronizacionLog::latest()->limit(10)->get()
>>> $logs->each(function($log) {
        echo "{$log->operacion}: {$log->estado} ({$log->elementos_procesados} items)\n";
    });
```

**✅ Test 5 PASÓ** si los logs se muestran correctamente

---

## Test 6: Validar Relaciones de Modelos (10 minutos)

**Objetivo:** Verificar que todas las relaciones entre modelos funcionan correctamente

### Pasos:

Abrir tinker y ejecutar:

```php
>>> $directivo = App\Models\User::find(1)

// Relación: Directivo → Permiso Calendario
>>> $permiso = $directivo->calendarioPermiso
>>> $permiso ? "✅ Relación User→CalendarioPermiso OK" : "❌ FALLIDA"

// Relación: Apoyo → Hitos
>>> $apoyo = App\Models\Apoyo::first()
>>> $hitos = $apoyo->hitos
>>> $hitos->count() >= 1 ? "✅ Relación Apoyo→Hitos OK" : "❌ FALLIDA"

// Relación: Apoyo → Logs
>>> $logs = $apoyo->sincronizacionLogs
>>> "✅ Relación Apoyo→Logs OK"

// Relación: Hito → Apoyo
>>> $hito = $appoyo->hitos->first()
>>> $apoyo_ref = $hito->apoyo
>>> $apoyo_ref->id_apoyo === $apoyo->id_apoyo ? "✅ Relación Hito→Apoyo OK" : "❌ FALLIDA"

// Relación: Hito → Logs
>>> $hito_logs = $hito->sincronizacionLogs
>>> "✅ Relación Hito→Logs OK"
```

**✅ Test 6 PASÓ** si todas las relaciones funcionan

---

## Test 7: Validar Scopes (5 minutos)

**Objetivo:** Verificar que los scopes personalizados filtran correctamente

### Pasos:

Ejecutar en tinker:

```php
// Scope: pendienteSincronizacion
>>> $hito->marcarCambiosPendientes()
>>> $pendientes = App\Models\HitosApoyo::pendienteSincronizacion()->get()
>>> $pendientes->count() >= 1 ? "✅ Scope pendienteSincronizacion" : "❌ FALLA"

// Scope: sincronizacionActiva
>>> $activos = App\Models\HitosApoyo::sincronizacionActiva()->get()
>>> $activos->count() >= 0 ? "✅ Scope sincronizacionActiva" : "❌ FALLA"

// Marcar como sincronizado
>>> $hito->marcarComSincronizado()
>>> $hito->refresh()
>>> $hito->cambios_locales_pendientes === false ? "✅ Método marcarComSincronizado" : "❌ FALLA"

// Scope de Apoyos
>>> $apoyos_sync = App\Models\Apoyo::sincronizacionHabilitada()->get()
>>> "✅ Scope sincronizacionHabilitada"
```

**✅ Test 7 PASÓ** si todos los scopes funcionan

---

## Test 8: Validar Seguridad (10 minutos)

### 8.1 Validar Encriptación de Tokens

```php
>>> $permiso = App\Models\DirectivoCalendarioPermiso::first()

// Ver token encriptado en BD (RAW)
>>> $raw = $permiso->getRawAttributes()
>>> strlen($raw['access_token']) > 50 ? "✅ Token encriptado" : "❌ NO encriptado"

// Laravel descrypta automáticamente
>>> strlen($permiso->access_token) > 10 ? "✅ Descrypción automática" : "❌ FALLA"
```

### 8.2 Validar CSRF en OAuth

**Durante el Test 1 (OAuth Flow):**
1. Abrir DevTools (F12) → Application → Cookies
2. Buscar cookie: `oauth_state`
3. Verificar que existe valor
4. Comparar con valor en URL de callback

**✅ Si cookie y URL coinciden:** CSRF OK

### 8.3 Validar Permisos de Rol

```php
// Solo Directivos (Rol 3) deben poder conectar
>>> $directivo = App\Models\User::where('id_rol', 3)->first()
>>> auth()->attempt(['email' => $directivo->correo_electronico, 'password' => 'test'])
>>> Gate::allows('conectar-calendario') ? "✅ Directivo puede conectar" : "❌ PROHIBIDO"

// Admin (Rol 0) NO debe poder conectar (si existe validación)
>>> $admin = App\Models\User::where('id_rol', 0)->first()
>>> auth()->login($admin)
>>> !Gate::allows('conectar-calendario') ? "✅ Admin prohibido" : "⚠️ Sin validación"
```

**✅ Test 8 PASÓ** si tokens están encriptados y permisos funcionan

---

## Resumen de Pruebas

| # | Test | Esperado | Estado |
|---|------|----------|--------|
| 1 | OAuth Flow Completo | Conectar con Google | [ ] ✅ |
| 2 | Creación Automática | Evento en Google | [ ] ✅ |
| 3 | Actualización Automática | Cambios en Google | [ ] ✅ |
| 4 | Scheduler Job | Sincroniza de Google | [ ] ✅ |
| 5 | Logs de Auditoría | Registra operaciones | [ ] ✅ |
| 6 | Relaciones de Modelos | Todas funcionan | [ ] ✅ |
| 7 | Scopes Personalizados | Filtran correctamente | [ ] ✅ |
| 8 | Validaciones de Seguridad | Tokens encriptados | [ ] ✅ |

---

## Checklist Final

### 🎯 Antes de Considerar Exitosas las Pruebas

- [ ] **Test 1:** OAuth flujo completo sin errores
- [ ] **Test 2:** Evento aparece en Google Calendar
- [ ] **Test 3:** Cambios se replican automáticamente
- [ ] **Test 4:** Scheduler sincroniza desde Google
- [ ] **Test 5:** Logs se almacenan correctamente
- [ ] **Test 6:** Todas las relaciones de modelos funcionan
- [ ] **Test 7:** Scopes filtran datos correctamente
- [ ] **Test 8:** Tokens encriptados y permisos validados

### ⚠️ Errores Encontrados

```
[ Documentar aquí cualquier error encontrado ]
```

### 📝 Notas Adicionales

```
[ Cualquier observación sobre el comportamiento ]
```

---

## Próximos Pasos

**Si TODOS los tests pasan:** ✅ Listo para **Azure Deployment (Fase 3)**

**Si hay errores:** 
1. Documentarlos arriba
2. Revisar: LOCAL_QA_TESTING_GUIDE.md → Part 7 (Troubleshooting)
3. Contactar al equipo de desarrollo

---

**Completado por:** _________________  
**Fecha/Hora:** _________________  
**Estado Final:** ☐ PASÓ | ☐ FALLÓ | ☐ CON PROBLEMAS

