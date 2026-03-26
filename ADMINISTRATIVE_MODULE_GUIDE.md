# Módulo Administrativo - Guía de Implementación

**Fecha**: Marzo 26, 2026  
**Estado**: ✅ Implementado y Listo para Usar

## 📋 Resumen

Se ha implementado completamente el **módulo administrativo de verificación de documentos** según las especificaciones del archivo `administrativo.md`. El módulo permite que los administradores revisen y aprueben/rechacen documentos cargados por beneficiarios, con generación automática de tokens QR para validación.

## 🎯 Características Principales

✅ **Visualización Unificada**: Muestra documentos locales y de Google Drive en una misma interfaz  
✅ **Una Solicitud a la Vez**: Interface limpia mostrando UNA solicitud (no lista de todas)  
✅ **Filtrado por Apoyo**: Filtra solicitudes pendientes por tipo de beneficio  
✅ **Sistema de Estatus**: Pendiente → Aceptado/Rechazado  
✅ **Generación de QR**: Tokens únicos SHA256 para cada documento verificado  
✅ **Validación Pública**: Endpoint sin autenticación para escanear QR  
✅ **Observaciones**: Campo obligatorio al rechazar documentos  
✅ **Auditoría**: Registra usuario admin, fecha y observaciones  

## 🛠️ Instalación y Configuración

### 1. Ejecutar Migración

```bash
cd c:\xampp\htdocs\SIGO
php artisan migrate
```

Esto agregará los campos necesarios a `Documentos_Expediente`.

### 2. Configurar Clave de Encriptación QR

En archivo `.env`:

```env
# Agregar esta línea (o usar la clave de APP_KEY)
ENCRYPTION_KEY_QR=your-secret-key-128-chars-here
```

En `config/app.php`:

```php
'encryption_key_qr' => env('ENCRYPTION_KEY_QR', env('APP_KEY')),
```

### 3. (Opcional) Instalar Librería QR

Para generar imágenes QR, instalar:

```bash
composer require simplesoftware/simple-qrcode
```

## 📁 Archivos Creados/Modificados

### Migraciones
```
database/migrations/2026_03_26_add_admin_verification_to_documentos.php
```

### Modelos
```
app/Models/Documento.php         (NUEVO)
app/Models/Solicitud.php         (NUEVO)
app/Models/TipoDocumento.php     (NUEVO)
```

### Servicios
```
app/Services/AdministrativeVerificationService.php  (NUEVO)
```

### Controladores
```
app/Http/Controllers/DocumentVerificationController.php  (NUEVO)
```

### Vistas
```
resources/views/admin/solicitudes/index.blade.php          (NUEVO)
resources/views/admin/solicitudes/show.blade.php           (NUEVO)
resources/views/admin/validacion-exitosa.blade.php         (NUEVO)
resources/views/admin/validacion-fallida.blade.php         (NUEVO)
```

### Rutas
```
routes/web.php  (MODIFICADO - agregadas rutas /admin/solicitudes/*)
```

## 🚀 Uso del Módulo

### Para Administrador

1. **Acceder al menú**: Ir a `/admin/solicitudes`
   - Requiere rol administrativo (rol 1, 2 o 3 en tabla Personal)

2. **Ver solicitudes pendientes**:
   - Listado con indicadores de documentos pendientes
   - Filtrar por "apoyo" (tipo de beneficio)
   - Estadísticas: Pendientes, Aceptados, Rechazados

3. **Revisar una solicitud**:
   - Click en solicitud para abrir detalle
   - Muestra: Beneficiario, Apoyo, Documentos

4. **Verificar documentos**:
   - Ver documento (click en botón "Ver" - abre en Google Drive o descarga local)
   - Aceptar: ✓ Aceptar → Genera token QR automáticamente
   - Rechazar: ✕ Rechazar → Campo de observaciones (obligatorio)
   - Estado actualiza en tiempo real

### Para Beneficiario (Validación Pública)

1. **Escanear QR**: QR contiene enlace a `/validacion/{token}`
2. **Ver información**: Página pública muestra:
   - Información del beneficiario
   - Tipo de documento
   - Fechas de carga y verificación
   - Administrador que verificó
   - Observaciones (si las hay)

## 🔐 Seguridad

✅ **Autenticación**: Requiere `User->isPersonal()`  
✅ **Autorización**: Verifica rol administrativo  
✅ **Tokens**: SHA256 hash (beneficiario + admin + timestamp + clave secreta)  
✅ **Validación**: Solo de lectura en endpoint público  
✅ **Auditoría**: Registra todas las verificaciones  

## 📊 Estructura de Datos

### Tabla Documentos_Expediente (Actualizada)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id_documento | INT PK | ID único |
| fk_folio | INT FK | Relación con solicitud |
| fk_id_tipo_doc | INT FK | Tipo de documento |
| ruta_archivo | VARCHAR | Ruta local |
| origen_archivo | VARCHAR | 'local' o 'drive' |
| google_file_id | VARCHAR | ID Google Drive |
| google_file_name | VARCHAR | Nombre archivo Drive |
| estado_validacion | VARCHAR | 'Pendiente' (beneficiario) |
| **admin_status** | ENUM | 'pendiente', 'aceptado', 'rechazado' |
| **admin_observations** | TEXT | Notas del admin |
| **verification_token** | VARCHAR | SHA256 token único |
| **id_admin** | INT FK | Usuario admin que verificó |
| **fecha_verificacion** | DATETIME | Cuándo se verificó |

## 🔌 API de Servicio

Clase: `App\Services\AdministrativeVerificationService`

```php
// Obtener solicitud para revisar
$solicitud = $service->getSolicitudForReview($folio, $apoyoFilter);

// Obtener URL para visualizar documento
$url = $service->getDocumentAccessUrl($documento);

// Verificar documento (aceptar/rechazar)
$service->verifyDocument($documento, 'aceptado', 'observaciones...');

// Validar token de QR
$documento = $service->validateVerificationToken($token);

// Obtener solicitudes pendientes
$solicitudes = $service->getSolicitudesPendientes(50);

// Obtener filtros disponibles
$apoyos = $service->getApoyosFiltros();

// Estadísticas
$stats = $service->getVerificationStats();
```

## 🧪 Testing

### Verificar que la migración se ejecutó:

```bash
php artisan migrate:status
```

Debería mostrar `2026_03_26_add_admin_verification_to_documentos ... Ran`

### Verificar tablas y campos:

```bash
php artisan tinker

# En la consola de tinker:
Schema::getColumns('Documentos_Expediente')
```

## 📝 Próximas Fases

### Fase 2: QR Visual
- [ ] Generar imagen QR con `simplesoftwareio/simple-qrcode`
- [ ] Mostrar QR en vista de validación
- [ ] Generar PDF con QR incrustado

### Fase 3: Notificaciones
- [ ] Email al beneficiario cuando documento es aceptado
- [ ] Email al beneficiario cuando documento es rechazado
- [ ] Notificación de envío

### Fase 4: Dashboard
- [ ] Widget de estadísticas en panel admin
- [ ] Gráficos de documentos por apoyo
- [ ] Historial de verificaciones

### Fase 5: Automatización
- [ ] Workflow automático si todos documentos aceptados
- [ ] Rechazo automático si documento válida reglas
- [ ] Cambio de estado de solicitud

## 🐛 Troubleshooting

### Error: "403 No cuentas con permisos"
- Verifique que usuario tenga rol administrativo
- Verificar tabla `Personal`: campo `fk_rol` debe ser 1, 2 o 3

### Error: "404 Solicitud no encontrada"
- Asegúrese que el folio existe en base de datos
- Verifique que solicitud tiene documentos

### Token QR no valida
- Verifique que token es exactamente 64 caracteres (hex)
- Asegúrese que documento tiene `admin_status = 'aceptado'`
- Verifique que `ENCRYPTION_KEY_QR` es la misma en todas instancias

## 📞 Contacto de Soporte

Para preguntas o issues, consulte:
- Documentación en `administrativo.md`
- Código en `.github/agents/administrative.agent.md`
- Especificaciones de base de datos en migraciones

---

**Desarrollado con**: Laravel 11 + Blade + Alpine.js  
**Estándar de Código**: PSR-12  
**Licencia**: Conforme a policy SIGO
