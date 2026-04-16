## 🔧 SOLUCIÓN: SINCRONIZACIÓN DE GOOGLE CALENDAR NO FUNCIONA

### ✅ PROBLEMA IDENTIFICADO

**Root Cause:** El access_token expiró hace 2 semanas (30/03/2026 22:04:58) y el refresh_token está **VACÍO** después de desencriptar.

**Por qué:**
```
Tokens en BD:
- ID Permiso: 7
- Email: guillermoavilamora2@gmail.com
- Access Token Expirado: SÍ ❌
- Refresh Token: VACÍO (después de desencriptar)
- Última Sincronización: 30/03/2026 21:09
```

Sin refresh_token, Google Calendar API devuelve **Error 401: Invalid Credentials**.

### 🔧 CAMBIOS APLICADOS

✅ **Archivo**: `.env`
```
ANTES:
GOOGLE_REDIRECT_URI=http://localhost/SIGO/public/auth/google/callback

DESPUÉS:
GOOGLE_REDIRECT_URI=http://localhost:8000/admin/calendario/callback
```

**Por qué:** El redirect_uri debe coincidir exactamente con la ruta que maneja el callback en Laravel.

### 📋 INSTRUCCIONES PARA RESOLVER

#### PASO 1: Ejecutar el generador de URL
```bash
cd c:\xampp\htdocs\SIGO
php regenerate_oauth_token.php
```

**Salida esperada:**
```
✅ State generado: [ESTADO_LARGO]
✅ State guardado en BD (válido por 15 minutos)

1. Abre este enlace en el navegador:
https://accounts.google.com/o/oauth2/v2/auth?...approval_prompt=force...

2. Inicia sesión con: guillermoavilamora2@gmail.com
3. Permite que SIGO acceda a Google Calendar
4. Serás redirigido a: http://localhost:8000/admin/calendario/callback
5. Los tokens se refrescarán automáticamente

⚠️ IMPORTANTE: Este enlace expira en 15 minutos
```

#### PASO 2: Verificar que el servidor funciona
Asegúrate de que Laravel está corriendo en puerto 8000:
```bash
# Terminal 1:
cd c:\xampp\htdocs\SIGO
php artisan serve --port=8000
```

#### PASO 3: Abre el enlace en navegador
1. Copia el enlace completo del paso 1
2. Abrir en navegador
3. Selecciona "Usar otra cuenta" si es necesario
4. Inicia sesión con: **guillermoavilamora2@gmail.com**
5. Click en "Permitir" cuando Google pida permisos
6. Automáticamente te redirige a: **http://localhost:8000/admin/calendario**

#### PASO 4: Verificar que se guardó correctamente
```bash
php diagnose_token_decrypt.php
```

**Salida esperada:**
```
TEST 1: Desencriptación de Access Token
✅ Access Token desencriptado correctamente
   - access_token longitud: 341 chars
   - refresh_token: OK (ALGO_VALIDO)

TEST 2: Desencriptación de Refresh Token
✅ Refresh Token desencriptado
   Contenido: OK (123 chars)
```

#### PASO 5: Probar sincronización
Ir a: http://localhost:8000/admin/calendario
Click en "Sincronizar" → Debe funcionas sin errors ahora

### 🎯 RESUMEN DE CAMBIOS

| Archivo | Cambio | Estado |
|---------|--------|--------|
| `.env` | GOOGLE_REDIRECT_URI actualizar puerto | ✅ Hecho |
| `generarUrlAutenticacion()` | Ya incluye `access_type=offline` y `approval_prompt=force` | ✅ OK |
| `manejarCallbackOAuth()` | Guarda refresh_token correctamente | ✅ OK |

### ⚠️ IMPORTANTE - Notas

1. **El link expira en 15 minutos** - si no lo uses, ejecuta `regenerate_oauth_token.php` de nuevo
2. **Debe usarse puerto 8000** - `php artisan serve --port=8000`
3. **Refresh token solo se obtiene si usas `approval_prompt=force`** - esto obliga a Google a pedir permisos nuevamente
4. Después de reautenticar, todos los calendarios volverán a sincronizarse normalmente

### 🐛 DEBUGGING - Si algo sale mal

**Opción A: Ver logs en tiempo real**
```bash
tail -f storage/logs/laravel.log | Select-String "GoogleCalendar"
```

**Opción B: Resetear tokens manualmente**
```bash
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$permiso = \App\Models\DirectivoCalendarioPermiso::find(7);
\$permiso->update(['activo' => 0]);
echo 'Permiso desactivado. Ejecuta regenerate_oauth_token.php de nuevo.';
"
```

---

**Estado Actual:** ✅ Sistema listo para re-autenticación
**Próximo Paso:** Seguir instrucciones del PASO 1
