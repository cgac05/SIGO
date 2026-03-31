# 🚀 Comencemos las Pruebas Manuales - Guía Rápida

**Etapa Actual:** Fase 2 - Validación Local  
**Tiempo Total Estimado:** 60-75 minutos  
**Estado:** 🟢 Ready for Execution

---

## Estructura de la Sesión de Pruebas

```
Total: 75 minutos
├─ Setup Inicial:           5 min  ← Comenzamos AQUÍ
├─ Test 1 (OAuth):         10 min
├─ Test 2 (Crear evento):  10 min
├─ Test 3 (Actualizar):    10 min
├─ Test 4 (Scheduler):     10 min
├─ Test 5 (Logs):           5 min
├─ Test 6 (Relaciones):    10 min
├─ Test 7 (Scopes):         5 min
├─ Test 8 (Seguridad):     10 min
└─ Resumen & Checklist:     5 min
```

---

## ✅ PASO 1: Preparación (Ahora Mismo)

### 1a. Ejecutar Setup de Datos

```bash
cd c:\xampp\htdocs\SIGO
php setup_manual_tests.php
```

**Esto va a:**
- ✅ Crear usuario Directivo de prueba
- ✅ Crear Permiso de Calendario
- ✅ Crear Apoyo de prueba
- ✅ Crear Hito de prueba
- ✅ Verificar columnas de BD

**Resultado esperado:**
```
✅ Google Client cargado
✅ Hitos_Apoyo.google_calendar_event_id
✅ Apoyos.sincronizar_calendario
✅ Directivo: ID=1
✅ Apoyo: ID=1
✅ Hito: ID=1
Setup completado
```

---

### 1b. Iniciar Servidor Laravel

**En una nueva terminal:**

```bash
cd c:\xampp\htdocs\SIGO
php artisan serve
```

**Resultado esperado:**
```
Laravel development server started: http://127.0.0.1:8000
```

**IMPORTANTE:** Mantener esta terminal abierta durante TODAS las pruebas.

---

### 1c. Configurar Google OAuth (Pre-requisito)

**⚠️ CRÍTICO:** Antes de Test 1 (OAuth flow)

Necesitas:
1. Cuenta Google (personal o de prueba)
2. Google Cloud Console project
3. Google Calendar API habilitada
4. OAuth 2.0 Credentials creadas

**Pasos rápidos:**
```
1. Ir a: https://console.cloud.google.com
2. Crear Proyecto → "SIGO Test"
3. Habilitar API → "Google Calendar API"
4. Crear Credenciales → "OAuth 2.0 - Web application"
5. Configurar consent screen
6. Descargar JSON credentials
7. En SIGO .env agregar:
   GOOGLE_CLIENT_ID=xxx
   GOOGLE_CLIENT_SECRET=xxx
   GOOGLE_REDIRECT_URI=http://localhost:8000/admin/calendario/callback
```

**Si ya tienes esto configurado:** ✅ Continúa

---

## 🧪 Tests a Ejecutar

### Test 1: OAuth Flow ⏱️ 10 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-1-oauth-flow-completo-10-minutos)

**Resumen:** 
1. Acceder a: http://localhost:8000/admin/calendario
2. Hacer clic: "Conectar con Google"
3. Autorizar en Google
4. Verificar que regresa a SIGO con estado "Conectado"

**Resultado esperado:** ✅ Conectado correctamente

---

### Test 2: Crear Evento ⏱️ 10 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-2-creación-automática-de-evento-evento-disparado-10-minutos)

**Resumen:**
1. En SIGO: Crear nuevo Hito
2. Verificar que aparece en Google Calendar automáticamente
3. Validar en BD que google_calendar_event_id tiene valor

**Resultado esperado:** ✅ Evento en Google Calendar

---

### Test 3: Actualizar Evento ⏱️ 10 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-3-actualización-de-evento-10-minutos)

**Resumen:**
1. Editar el Hito creado en Test 2
2. Cambiar descripción y fecha
3. Verificar cambios en Google Calendar

**Resultado esperado:** ✅ Cambios replicados automáticamente

---

### Test 4: Scheduler ⏱️ 10 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-4-scheduler-job---sincronización-de-google-a-sigo-10-minutos)

**Resumen:**
1. Crear evento directamente en Google Calendar
2. Ejecutar: `php artisan sync:google-calendar`
3. Verificar sincronización en SIGO

**Resultado esperado:** ✅ Scheduler funciona bidireccional

---

### Test 5: Logs ⏱️ 5 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-5-verificar-logs-de-sincronización-5-minutos)

**Resumen:**
1. Acceder a: http://localhost:8000/admin/calendario/logs
2. Verificar que muestra todas las operaciones

**Resultado esperado:** ✅ Logs se almacenan correctamente

---

### Test 6: Relaciones ⏱️ 10 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-6-validar-relaciones-de-modelos-10-minutos)

**Resumen:**
Ejecutar en tinker:
```php
php artisan tinker
>>> $directivo->calendarioPermiso
>>> $apoyo->hitos
>>> $hito->apoyo
```

**Resultado esperado:** ✅ Todas las relaciones funcionan

---

### Test 7: Scopes ⏱️ 5 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-7-validar-scopes-5-minutos)

**Resumen:**
```php
>>> HitosApoyo::pendienteSincronizacion()->get()
>>> HitosApoyo::sincronizacionActiva()->get()
>>> Apoyo::sincronizacionHabilitada()->get()
```

**Resultado esperado:** ✅ Scopes filtran correctamente

---

### Test 8: Seguridad ⏱️ 10 min
**Archivo:** [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md#test-8-validar-seguridad-10-minutos)

**Resumen:**
1. Verificar tokens encriptados en BD
2. Validar CSRF en OAuth flow
3. Verificar permisos de rol

**Resultado esperado:** ✅ Todos los controles de seguridad funcionan

---

## 📋 Checklist de Ejecución

**Completar mientras ejecutas los tests:**

- [ ] **Setup:** Datos creados correctamente
- [ ] **Servidor:** Corriendo en http://localhost:8000
- [ ] **Google OAuth:** Configurado en .env
- [ ] **Test 1:** OAuth flow ✅
- [ ] **Test 2:** Crear evento ✅
- [ ] **Test 3:** Actualizar evento ✅
- [ ] **Test 4:** Scheduler sync ✅
- [ ] **Test 5:** Logs ✅
- [ ] **Test 6:** Relaciones ✅
- [ ] **Test 7:** Scopes ✅
- [ ] **Test 8:** Seguridad ✅

---

## 🆘 Si Encuentras Errores

Consultar: [LOCAL_QA_TESTING_GUIDE.md →  Part 7: Troubleshooting](LOCAL_QA_TESTING_GUIDE.md#parte-7-resolución-de-problemas-comunes)

Errores comunes:
```
"Google Client not found"         → composer dump-autoload --optimize
"CSRF token mismatch"              → Limpiar cookies del navegador
"Access token expired"             → Esperar o renovar manualmente
"Quota exceeded"                   → Esperar a que se restablezca (1 hora)
"Column does not exist"            → Ejecutar migración
```

---

## ✨ Después de Completar Todos los Tests

### Opción A: Todos los Tests ✅ PASARON
```
Estado: LISTO PARA AZURE DEPLOYMENT
Próximo paso: Contactar DevOps para despliegue en Azure (Fase 3)
```

### Opción B: Algunos Tests ❌ FALLARON
```
1. Documentar errores en MANUAL_TESTING_CHECKLIST.md
2. Revisar troubleshooting en LOCAL_QA_TESTING_GUIDE.md
3. Contactar al equipo técnico con detalles de errores
4. Reintentar después de solucionar
```

---

## 🎯 Recursos Disponibles

| Recurso | Propósito |
|---------|----------|
| [MANUAL_TESTING_CHECKLIST.md](MANUAL_TESTING_CHECKLIST.md) | Pasos detallados de cada test |
| [LOCAL_QA_TESTING_GUIDE.md](LOCAL_QA_TESTING_GUIDE.md) | Guía completa con troubleshooting |
| [setup_manual_tests.php](setup_manual_tests.php) | Script de preparación |
| [FINAL_STATUS_REPORT.md](FINAL_STATUS_REPORT.md) | Estado del proyecto |

---

## 🏁 Comando para Comenzar AHORA

```bash
# Terminal 1: Setup
cd c:\xampp\htdocs\SIGO
php setup_manual_tests.php

# Terminal 2: Servidor
cd c:\xampp\htdocs\SIGO
php artisan serve

# Terminal 3: Tests (cuando esté listo)
# Ir a: http://localhost:8000/calendario/configuracion
```

---

**Estado:** ✅ Listo para comenzar pruebas manuales

**Próximo paso:** Ejecuta `php setup_manual_tests.php` en terminal 1

**Tiempo total:** 60-75 minutos para completar todos los tests
