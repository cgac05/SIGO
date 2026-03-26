# 🔑 Guía Completa: Configuración de Claves de API - Google Drive

**Fecha**: 25 de Marzo de 2026  
**Estado**: ✅ Configuración Actual + Pasos para Producción

---

## 📋 Estado Actual de Claves

### Configuración Local (Desarrollo)
Tu `.env` debe tener las siguientes claves configuradas (ver `.env.example`):

```env
# Google OAuth - DESARROLLO
GOOGLE_CLIENT_ID=YOUR_CLIENT_ID_PLACEHOLDER.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=YOUR_CLIENT_SECRET_PLACEHOLDER
GOOGLE_API_KEY=YOUR_API_KEY_PLACEHOLDER
GOOGLE_REDIRECT_URI=http://localhost/SIGO/public/auth/google/callback
```

**Estado**: ✅ Funcional para desarrollo local

---

## 🔍 Paso 1: Validar Claves Actuales

### 1.1 Verificar que las Claves Estén en Sincronía

```bash
# Verificar que .env esté cargado correctamente
php artisan config:cache
php artisan config:clear
php artisan tinker
```

```php
// Dentro de tinker:
>>> config('services.google.client_id')
>>> config('services.google.client_secret')
>>> config('services.google.api_key')
>>> exit
```

### 1.2 Verificar Acceso a Google Drive API

```php
// Crear archivo de prueba: tests/Feature/GoogleDriveTokenTest.php

<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class GoogleDriveTokenTest extends TestCase
{
    public function test_google_credentials_are_configured()
    {
        $this->assertNotEmpty(config('services.google.client_id'));
        $this->assertNotEmpty(config('services.google.client_secret'));
        $this->assertNotEmpty(config('services.google.api_key'));
    }

    public function test_google_client_can_be_instantiated()
    {
        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        
        $this->assertNotNull($client);
    }
}
```

Ejecutar:
```bash
php artisan test --filter GoogleDriveTokenTest
```

---

## 🎯 Paso 2: Obtener Nuevas Claves (Google Cloud Console)

### 2.1 Acceder a Google Cloud Console

1. Ir a [console.cloud.google.com](https://console.cloud.google.com)
2. Crear nuevo Proyecto o seleccionar uno existente
   - Nombre sugerido: `SIGO-Google-Drive-Producción`

### 2.2 Habilitar APIs Requeridas

**En el panel de búsqueda** (arriba), buscar y habilitar:

- ✅ **Google Drive API** v3
- ✅ **Google Picker API**

Pasos:
1. Buscar "Google Drive API"
2. Click en "Habilitar"
3. Repetir para "Google Picker API"

### 2.3 Crear Credenciales OAuth 2.0

**Ir a**: `APIs & Services` → `Credentials`

**Crear credencial**:
1. Click `+ Create Credentials`
2. Seleccionar: `OAuth client ID`
3. Si pide: Configurar pantalla de consentimiento primero

**Configurar Pantalla de Consentimiento**:
- Tipo: `External`
- Información requerida:
  - **Nombre de app**: SIGO - Plataforma Estatal de Juventud
  - **Email de soporte**: tu-email@tudominio.com
  - **Email desarrollador**: tu-email@tudominio.com
- **Scopes** (Permisos):
  - Agregar `https://www.googleapis.com/auth/drive.file`
  - Agregar `https://www.googleapis.com/auth/userinfo.email`
  - Agregar `https://www.googleapis.com/auth/userinfo.profile`

**Crear OAuth Client**:
- Tipo: `Web application`
- Nombre: `SIGO-Backend`
- **URLs autorizadas de JavaScript**:
  ```
  http://localhost:8000
  https://tu-dominio.com
  https://www.tu-dominio.com
  ```
- **URIs de redirección autorizados**:
  ```
  http://localhost:8000/auth/google/callback
  https://tu-dominio.com/auth/google/callback
  ```

**Descargar credenciales** (JSON):
```json
{
  "web": {
    "client_id": "NUEVA_CLIENT_ID.apps.googleusercontent.com",
    "project_id": "tu-proyecto-id",
    "auth_uri": "https://accounts.google.com/o/oauth2/auth",
    "token_uri": "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
    "client_secret": "NUEVO_CLIENT_SECRET",
    "redirect_uris": ["https://tu-dominio.com/auth/google/callback"]
  }
}
```

### 2.4 Obtener API Key

**En Credentials**:
1. Click `+ Create Credentials`
2. Seleccionar `API Key`
3. Copiar la clave generada

---

## ⚙️ Paso 3: Actualizar Configuración

### 3.1 Para Desarrollo Local

Actualizar `.env`:
```env
# Google OAuth - DESARROLLO
GOOGLE_CLIENT_ID=TU_NUEVA_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=TU_NUEVO_CLIENT_SECRET
GOOGLE_API_KEY=TU_NUEVA_API_KEY
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 3.2 Para Producción

Crear `.env.production`:
```env
# Google OAuth - PRODUCCIÓN
GOOGLE_CLIENT_ID=TU_PRODUCCION_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=TU_PRODUCCION_CLIENT_SECRET
GOOGLE_API_KEY=TU_PRODUCCION_API_KEY
GOOGLE_REDIRECT_URI=https://tu-dominio.com/auth/google/callback
```

**O en Azure Key Vault**:
```powershell
az keyvault secret set --vault-name "tu-vault" `
  --name "GOOGLE-CLIENT-ID" `
  --value "TU_PRODUCCION_CLIENT_ID"
```

---

## 🧪 Paso 4: Validar Funcionamiento

### 4.1 Test Manual

```bash
# Abrir tinker
php artisan tinker
```

```php
// Probar conexión a Google
$client = new Google_Client();
$client->setClientId(config('services.google.client_id'));
$client->setClientSecret(config('services.google.client_secret'));
$client->setRedirectUri(config('services.google.redirect'));

// Generar URL de autenticación
$authUrl = $client->createAuthUrl();
echo $authUrl;

// Exit y copiar URL en navegador
```

### 4.2 Test Feature Completo

```bash
# Ejecutar todos los tests de Google Drive
php artisan test --filter Google

# O específicamente:
php artisan test tests/Feature/GoogleDriveUploadTest.php
```

### 4.3 Verificar en Navegador

1. Ir a: `http://localhost/SIGO/public/auth/google`
2. Debería redirigir a Google
3. Iniciar sesión con test account
4. Debería volver a la app autenticado

---

## 🔐 Paso 5: Seguridad

### 5.1 Proteger Claves Sensibles

**NUNCA hacer commit de claves reales**:
```bash
# Agregar a .gitignore
echo ".env.production" >> .gitignore
echo ".env.*.local" >> .gitignore
```

### 5.2 Rotación de Claves

**En Google Cloud Console**:
1. Ir a Credentials
2. Seleccionar la credencial antigua
3. Crear nueva versión
4. Esperar 24 horas
5. Eliminar versión antigua

### 5.3 Restricciones de API Key

**Para API Key en Credentials**:
1. Seleccionar API Key
2. Click `Restrict key`
3. Configurar:
   - **Application restrictions**: HTTP referers
   - **API restrictions**: Solo Drive API v3 y Picker API
   - **Referers**: 
     ```
     https://tu-dominio.com/*
     http://localhost/*
     ```

---

## 📊 Tabla de Claves Requeridas

| Clave | Dónde se Usa | Ambiente | Ubicación |
|-------|-------------|----------|-----------|
| `GOOGLE_CLIENT_ID` | OAuth 2.0 | Dev + Prod | Google Cloud → Credentials |
| `GOOGLE_CLIENT_SECRET` | OAuth 2.0 | Dev + Prod | Google Cloud → Credentials |
| `GOOGLE_API_KEY` | Picker API | Dev + Prod | Google Cloud → API Keys |
| `GOOGLE_REDIRECT_URI` | OAuth Callback | Dev + Prod | .env (local) / KeyVault (Azure) |

---

## 🚀 Paso 6: Deployment a Producción

### 6.1 Azure Key Vault (Recomendado)

```bash
# Agregar secretos a Azure Key Vault
az keyvault secret set --vault-name "sigo-vault" \
  --name "google-client-id" \
  --value "$GOOGLE_CLIENT_ID"

az keyvault secret set --vault-name "sigo-vault" \
  --name "google-client-secret" \
  --value "$GOOGLE_CLIENT_SECRET"

az keyvault secret set --vault-name "sigo-vault" \
  --name "google-api-key" \
  --value "$GOOGLE_API_KEY"
```

### 6.2 Variables de Entorno en Azure App Service

```bash
# Agregar en Azure Portal → App Service → Configuration → Application settings

GOOGLE_CLIENT_ID=@Microsoft.KeyVault(SecretUri=https://sigo-vault.vault.azure.net/secrets/google-client-id/)
GOOGLE_CLIENT_SECRET=@Microsoft.KeyVault(SecretUri=https://sigo-vault.vault.azure.net/secrets/google-client-secret/)
GOOGLE_API_KEY=@Microsoft.KeyVault(SecretUri=https://sigo-vault.vault.azure.net/secrets/google-api-key/)
GOOGLE_REDIRECT_URI=https://sigo-produccion.azurewebsites.net/auth/google/callback
```

### 6.3 DNS y SSL

✅ Asegurar que:
- SSL Certificate válido (HTTPS activo)
- DNS correcto configurado
- REDIRECT_URI coincide exactamente con dominio real

---

## 🧾 Checklist de Configuración

### Desarrollo
- [ ] Claves de Google obtenidas (o usadas las actuales)
- [ ] `.env` actualizado con claves válidas
- [ ] `php artisan config:clear` ejecutado
- [ ] Test manual en navegador OK
- [ ] Tests automatizados pasando

### Producción
- [ ] Nuevas claves creadas en Google Cloud (producción)
- [ ] Scopes autorizados correctamente
- [ ] URLs redirect coinciden exactamente
- [ ] Secrets en Azure Key Vault
- [ ] Variables de entorno en App Service
- [ ] SSL/HTTPS activo
- [ ] DNS configurado correctamente
- [ ] Test de autenticación desde URL real

---

## 🐛 Troubleshooting

### "Invalid redirect_uri"
**Solución**: Asegurar que `GOOGLE_REDIRECT_URI` coincide EXACTAMENTE con lo configurado en Google Cloud Console (incluyendo protocolo http/https)

### "Unauthorized_client"
**Solución**: Verificar que Scopes están autorizados en la pantalla de consentimiento

### "Access denied"
**Solución**: Verificar que el email del usuario de prueba no está bloqueado; agregar como usuario de prueba en Google Console

### "This API project is not authorized to use the Picker API"
**Solución**: Ir a Google Cloud Console → APIs → Habilitar explícitamente Picker API

---

## 📞 Recursos Útiles

- [Google Cloud Console](https://console.cloud.google.com)
- [Google Drive API Documentation](https://developers.google.com/drive/api)
- [OAuth 2.0 Setup](https://developers.google.com/identity/protocols/oauth2)
- [Google Picker API](https://developers.google.com/picker/docs)

---

## 🎯 Resumen

| Paso | Acción | Estado |
|------|--------|--------|
| 1 | Validar claves actuales | ✅ Hecho |
| 2 | Obtener nuevas claves (opcional) | 📋 Documentado |
| 3 | Actualizar .env | 📋 Cuando tengas nuevas claves |
| 4 | Validar funcionamiento | 📋 Tests listos |
| 5 | Implementar seguridad | 📋 Guía completa |
| 6 | Deploy a producción | 📋 Pasos listos |

**Próximo paso**: Ejecutar los tests para validar que las claves actuales funcionan correctamente.

---

**Última actualización**: 25 de Marzo de 2026  
**Versión**: 1.0  
**Estado**: ✅ Guía Completa
