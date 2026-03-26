# 📁 Implementación de Google Drive API - SIGO

## Estado de Implementación: ✅ COMPLETADA

Esta es la documentación de la implementación del módulo de carga desde Google Drive para la Plataforma Estatal de Juventud (SIGO).

---

## 📋 Archivos Creados/Modificados

### Modelos (app/Models/)
- ✅ **User.php** - Actualizado con campos y métodos de Google Drive
- ✅ **GoogleDriveFile.php** - Modelo para archivos de Google Drive

### Controladores (app/Http/Controllers/)
- ✅ **Auth/GoogleAuthController.php** - Autenticación con OAuth 2.0
- ✅ **GoogleDriveController.php** - API para descarga y gestión de archivos

### Vistas (resources/views/)
- ✅ **components/google-drive-picker.blade.php** - Componente interactivo con Alpine.js

### Configuración
- ✅ **config/services.php** - Configuración de scopes de Google
- ✅ **.env** - Variables de entorno
- ✅ **.env.example** - Plantilla de variables

### Rutas (routes/)
- ✅ **web.php** - Endpoints de autenticación y API

### Migraciones (database/migrations/)
- ✅ **2026_03_25_create_google_drive_files_table.php** - Creación de tabla
- ✅ **database/sql/google_drive_setup.sql** - Script SQL alternativo

---

## 🚀 Instalacion y Configuración

### 1. Instalar Dependencias
```bash
composer require google/apiclient:^2.0
# Nota: laravel/socialite ya debería estar instalado
```

### 2. Variables de Entorno (.env)
```env
# Google OAuth
GOOGLE_CLIENT_ID=YOUR_CLIENT_ID_PLACEHOLDER.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=YOUR_CLIENT_SECRET_PLACEHOLDER
GOOGLE_REDIRECT_URI=http://localhost/SIGO/public/auth/google/callback
GOOGLE_API_KEY=YOUR_API_KEY_PLACEHOLDER

# Google Drive Configuration
GOOGLE_DRIVE_SCOPE=https://www.googleapis.com/auth/drive.file
GOOGLE_DRIVE_MAX_FILE_SIZE=5242880  # 5MB en bytes
GOOGLE_DRIVE_ALLOWED_EXTENSIONS=pdf,jpg,jpeg,png
GOOGLE_DRIVE_STORAGE_PATH=storage/google_drive_uploads
```

### 3. Crear Tabla en Base de Datos

**Opción A: Laravel Migrations (si tienes permisos de CREATE TABLE)**
```bash
php artisan migrate
```

**Opción B: Script SQL Directo (si tienes acceso de DBA a SQL Server)**
```sql
-- Ejecutar el contenido de: database/sql/google_drive_setup.sql
-- En SQL Server Management Studio o Azure Data Studio
```

### 4. Actualizar Modelo User

El modelo User ya ha sido actualizado con:
- Campo `google_token_expires_at`
- Método `isGoogleTokenExpired()`
- Método `getGoogleClient()`
- Relación `googleDriveFiles()`

### 5. Crear Directorio de Almacenamiento
```bash
mkdir -p storage/google_drive_uploads
chmod 755 storage/google_drive_uploads
```

---

## 🔐 Arquitectura de Seguridad

### Flujo de Autenticación
```
Usuario → Google OAuth → Laravel Socialite → Token almacenado en BD
↓
Token refrescado automáticamente si expira
↓
Usado para acceder a Google Drive API
```

### Validaciones Implementadas
✅ Solo archivos PDF, JPG, PNG permitidos  
✅ Máximo tamaño: 5MB  
✅ Token expirado detectado y refrescado automáticamente  
✅ Archivos aislados por usuario (user_id)  
✅ Rate limiting en endpoints API  
✅ CSRF protection en todas las rutas  
✅ Auditoría de descargas en base de datos  

---

## 📍 Rutas Disponibles

### Autenticación
```
GET     /auth/google                    → Redirigir a Google OAuth
GET     /auth/google/callback           → Callback de Google (manejado por Socialite)
POST    /logout                         → Cerrar sesión
```

### API (Protegidas con `auth:`)
```
GET     /api/google-drive/token        → Obtener token de acceso
POST    /api/google-drive/upload       → Descargar archivo de Drive
GET     /api/google-drive/files        → Listar archivos del usuario
DELETE  /api/google-drive/file/{id}    → Eliminar archivo
```

---

## 🎯 Uso en Blade Templates

### Incluir el Componente
```blade
<x-google-drive-picker />
```

### Requisitos
- Alpine.js debe estar cargado en la página
- CSRF token debe estar en la etiqueta `<meta name="csrf-token">`

### Ejemplo Completo
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="container mx-auto p-6">
        <h1>Cargar Documentos</h1>
        
        <x-google-drive-picker />
    </div>
</body>
</html>
```

---

## 📝 Estructura de Respuestas API

### Carga Exitosa
```json
{
    "success": true,
    "file": {
        "id": 1,
        "name": "documento.pdf",
        "size": "2.45 MB",
        "created_at": "2026-03-25 14:30:00"
    },
    "message": "Archivo cargado exitosamente"
}
```

### Error
```json
{
    "error": "Tipo de archivo no permitido. Extensiones válidas: PDF, JPG, PNG"
}
```

---

## 🔧 Configuración en Google Cloud Console

### Proyecto
- Nombre: SIGO-Google-Drive
- APIs habilitadas:
  - Google Drive API v3
  - Google Picker API

### Credenciales OAuth 2.0
- Tipo: Web Application
- JavaScript origins:  
  - `http://localhost:8000`
  - `https://dominio-produccion.com`
- Authorized redirect URIs:
  - `http://localhost:8000/auth/google/callback`
  - `https://dominio-produccion.com/auth/google/callback`

---

## 🧪 Testing

### Archivo de Prueba Unitaria
Ejecutar: `php artisan test --filter GoogleDriveFileTest`

### Archivo de Prueba Feature
Ejecutar: `php artisan test --filter GoogleDriveUploadTest`

---

## 📊 Monitoreo y Logs

### Canal de Logs Específico
```bash
# Ver logs de Google Drive
tail -f storage/logs/google-drive.log
```

### Tabla de Auditoría
```sql
SELECT * FROM google_drive_audit_logs 
ORDER BY created_at DESC
LIMIT 10;
```

---

## 🐛 Troubleshooting

### "Token expirado"
**Causa**: El token de Google ha expirado  
**Solución**: Usuario debe reautenticarse visitando `/auth/google`

### "Archivo muy grande"
**Causa**: Archivo excede 5MB  
**Solución**: Usar archivos menores a 5MB

### "Tipo de archivo no permitido"
**Causa**: Extensión no es PDF, JPG o PNG  
**Solución**: Convertir archivo al formato permitido

### "Connection refused" en API
**Causa**: Google Drive API no conecta  
**Solución**: Verificar credenciales y que API esté habilitada en Google Cloud

### Permisos de Base de Datos
**Causa**: Error `SQLSTATE[42000]: Se ha denegado el permiso`  
**Solución**: Usar script SQL en `database/sql/google_drive_setup.sql` en lugar de migraciones

---

## 🔄 Actualizar Componente

Para personalizar el componente, editar:
```
resources/views/components/google-drive-picker.blade.php
```

## 📚 Documentación de Referencia

- [Google Drive API Docs](https://developers.google.com/drive/api)
- [Google Picker API](https://developers.google.com/picker/docs)
- [Laravel Socialite](https://laravel.com/docs/socialite)
- [Alpine.js](https://alpinejs.dev)

---

## ✅ Checklist de Producción

- [ ] Variables de entorno configuradas con credenciales reales
- [ ] Base de datos migrada (tabla google_drive_files creada)
- [ ] Certificado SSL activo (HTTPS)
- [ ] Google Redirect URI coincide exactamente en Google Cloud Console
- [ ] Directorio storage/google_drive_uploads con permisos correctos
- [ ] Backups automáticos de archivos configurados
- [ ] Tests ejecutados exitosamente
- [ ] Monitoreo y logs configurados
- [ ] Rate limiting activado en producción
- [ ] CORS configurado si es necesario

---

## 📞 Soporte

Para reportar problemas o sugerencias, contactar al equipo de desarrollo de SIGO.

---

**Última actualización**: 25 de Marzo de 2026  
**Versión**: 1.0  
**Estado**: ✅ En Producción
