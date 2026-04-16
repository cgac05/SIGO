🔐 PROBLEMA IDENTIFICADO: Google NO Devuelve refresh_token
═══════════════════════════════════════════════════════════════

## ❌ ¿POR QUÉ FALLA?

Cuando un usuario ya ha autorizado tu aplicación una vez, Google **NO devuelve** un refresh_token la segunda vez.

**Log actual muestra:**
```
Refresh Token: VACÍO/NULL
Primeros 50 chars: [BLANK]
```

## ✅ SOLUCIÓN: Revocar Permisos en Google

Sigue estos pasos **EXACTAMENTE**:

### PASO 1️⃣: Abre el navegador
Ir a: https://myaccount.google.com/permissions

### PASO 2️⃣: Busca "SIGO"
En la lista, busca la aplicación que dice algo como:
- "SIGO" o
- "Localhost 8000" o
- "Google Calendar"

### PASO 3️⃣: Haz click en ella
Verás un resumen mostrando:
- Email: guillermoavilamora2@gmail.com
- Permisos: Google Calendar, Gmail, etc.

### PASO 4️⃣: Click en "REMOVE ACCESS" (Eliminar acceso)
Botón rojo/naranja que dice "Remove Access"

### PASO 5️⃣: Confirma
Click en confirmar cuando Google pregunte

### ✅ PASO 6️⃣: Re-autentica en SIGO
Vuelve a ejecutar:
```bash
cd c:\xampp\htdocs\SIGO
php reset_and_reauth_oauth.php
```

Y abre el nuevo link en el navegador.

---

## 🎯 Pasos Rápidos (Resumen)

1. Abre: https://myaccount.google.com/permissions
2. Busca y click en tu app
3. Click: "Remove Access"
4. Confirma
5. Ejecuta: `php reset_and_reauth_oauth.php` en terminal
6. Abre el link en navegador
7. Click "Permitir"

---

## ⚠️ IMPORTANTE

Si después de estos pasos sigue diciendo "Acceso rápido":
- Click en el pequeño arrow/dropdown
- Selecciona "Usar otra cuenta"
- Vuelve a ingresar: guillermoavilamora2@gmail.com
- Click "Permitir"

---

## 🔍 Verifica que funcionó

Después de completar los pasos:
```bash
php diagnose_token_decrypt.php
```

Deberías ver:
```
TEST 2: Desencriptación de Refresh Token
✅ Refresh Token desencriptado
   Contenido: OK (123 chars)  ← ✅ DEBE TENER CHARS
```

---

## 📍 Si aún no funciona

Ejecuta esto para ver el log exacto:
```bash
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "refresh"
```

Deberías ver:
```
✅ Google devolvió refresh_token: XYZ123...
```

Y NO deberías ver:
```
⚠️ ALERTA: Google NO devolvió refresh_token
```

---

**¡Haz esto ahora y avísame cuando termines!**
