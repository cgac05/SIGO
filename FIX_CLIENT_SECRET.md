🔐 ERROR: Google Client Secret Inválido
═════════════════════════════════════════════════════════

ERROR RECIBIDO:
{
  "error":"invalid_client",
  "error_description":"The provided client secret is invalid."
}

═════════════════════════════════════════════════════════

📋 SOLUCIÓN: Obtener el Secret Correcto de Google Cloud

### PASO 1: Abre Google Cloud Console
https://console.cloud.google.com/

### PASO 2: Encuentra tu Proyecto
- Busca "SIGO" o similar en los proyectos
- O busca el proyecto con Client ID: 523344188732

### PASO 3: Ve a Credenciales
- Menú izquierdo → Credenciales
- O busca "OAuth 2.0 Client IDs"

### PASO 4: Busca la Credencial
- Busca el "Web application" con ID: 523344188732
- Click en él para abrir los detalles

### PASO 5: Copia el Client Secret
- Verás un campo llamado "Client Secret"
- Copia TODO exactamente como aparece (incluyendo guiones)

### PASO 6: Actualiza .env
El secret debería verse así (pero con tu valor real):
GOOGLE_CLIENT_SECRET=GOCSPX-[ALGO_MUY_LARGO_DE_CARACTERES]

Si es muy diferente, cópialo exactamente.

═════════════════════════════════════════════════════════

⚠️  COMÚN: El Secret cambió
Si hace poco regeneraste las credenciales en Google Cloud,
el secret NEW debería usarse aquí.

Pasos si regeneraste:
1. Ve a https://console.cloud.google.com/apis/credentials
2. Busca "OAuth 2.0 Client" con ID 523344188732
3. Click en el icono de lápiz (edit)
4. Busca "Client Secret" en la sección derecha
5. Si no ves el botón "Show", haz click en él
6. Copia el valor

═════════════════════════════════════════════════════════

EJEMPLO DE DÓNDE ESTÁ:

Google Cloud Console
└── Credenciales
    └── OAuth 2.0 Client IDs
        └── Web application (ID: 523344188732)
            ├── Client ID: 523344188732-[...].apps.googleusercontent.com
            └── Client Secret: GOCSPX-[TODO_ESTO_AQUI]  ← AQUÍ

═════════════════════════════════════════════════════════

DESPUÉS DE ACTUALIZAR .env:

1. Ejecuta: php reset_and_reauth_oauth.php
2. Abre el link nuevo en navegador
3. Completa la autenticación

¿ Necesitas ayuda cómo actualizar .env? Avísame y te muestro.
