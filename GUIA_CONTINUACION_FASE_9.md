# GUÍA DE CONTINUACIÓN: FASE 9 PARTE 4 Y DEPLOYMENT

## 📋 Tabla de Contenidos
1. [Resolución de Permisos SQL Server](#resolución-de-permisos)
2. [Ejecución de Migraciones](#ejecución-de-migraciones)
3. [Setup de Directorios](#setup-de-directorios)
4. [Testing Manual](#testing-manual)
5. [Validación de Seguridad](#validación-de-seguridad)
6. [Deployment a Producción](#deployment-a-producción)

---

## 🔧 Resolución de Permisos

### PASO 1: Verificar Usuario SQL Server Actual

En SSMS (SQL Server Management Studio):

```sql
-- Conectar como DBA/sa
USE BD_SIGO;

-- Ver roles del usuario SIGO-APP
EXEC sp_helprolemember;

-- O específicamente:
SELECT DISTINCT role_principal_name 
FROM sys.database_principals rp
INNER JOIN sys.database_role_members drm ON rp.principal_id = drm.role_principal_id
WHERE member_principal_id = (SELECT principal_id FROM sys.database_principals WHERE name = 'SIGO-APP')
```

### PASO 2: Otorgar Permisos (Opción A - Recomendado)

```sql
-- Ejecutar como DBA/sa en SQL Server
USE BD_SIGO;
GO

-- Agregar usuario al rol db_owner
ALTER ROLE db_owner ADD MEMBER [SIGO-APP];
GO

-- Verificar
SELECT DISTINCT role_principal_name 
FROM sys.database_principals rp
INNER JOIN sys.database_role_members drm ON rp.principal_id = drm.role_principal_id
WHERE member_principal_id = (SELECT principal_id FROM sys.database_principals WHERE name = 'SIGO-APP')
```

### PASO 3: Pre-crear Tablas (Opción B - Alternativa)

Si no se puede otorgar permisos:

1. Abrir `DATABASE_SETUP_MIGRATION_MANUAL.sql` en SSMS
2. Conectar como usuario con permisos db_owner
3. Ejecutar todo el script
4. Verificar que se crearon 3 tablas (auditoria_verificacion, archivo_certificado, version_certificado)

---

## 🚀 Ejecución de Migraciones

### Una vez resueltos los permisos:

```bash
# Terminal en c:\xampp\htdocs\SIGO

# PASO 1: Ver migraciones pendientes
php artisan migrate:status

# Deberías ver:
# - 2025_04_04_000100_create_auditoria_verificacion_table .................... Pending
# - 2025_04_04_000200_create_archivo_certificado_table ....................... Pending
# - 2025_04_04_000300_create_version_certificado_table ....................... Pending

# PASO 2: Ejecutar migraciones
php artisan migrate

# Deberías ver:
# Running migrations
# 2025_04_04_000100_create_auditoria_verificacion_table ........ XXXXms SUCCESS
# 2025_04_04_000200_create_archivo_certificado_table ........... XXXXms SUCCESS
# 2025_04_04_000300_create_version_certificado_table ........... XXXXms SUCCESS

# PASO 3: Verificar tablas en BD
php artisan tinker
# En tinker:
DB::select("SELECT name FROM sys.tables WHERE name IN ('auditoria_verificacion', 'archivo_certificado', 'version_certificado')")
exit
```

---

## 📁 Setup de Directorios

```bash
cd c:\xampp\htdocs\SIGO

# Crear directorios necesarios
mkdir -p storage\certificados_archivados
mkdir -p storage\backups

# En Windows, verificar permisos (si es necesario):
# - Click derecho en carpeta
# - Propiedades > Seguridad
# - Asegurar que IUSR y IIS_IUSRS tengan Modificar

# En Linux/Mac (si aplica):
chmod 755 storage/certificados_archivados
chmod 755 storage/backups
chmod 777 storage/certificados_archivados
chmod 777 storage/backups
```

---

## 🧪 Testing Manual

### 1. Test Certificación Digital (Parte 1)

```
1. Navegar a: /admin/certificacion/digital
2. Clic en "Crear Certificado"
3. Seleccionar solicitud completada
4. Verificar:
   - ✅ Hash SHA-256 generado
   - ✅ QR Code visible
   - ✅ Descarga de PDF funciona
   - ✅ Estado en DB actualizado
```

### 2. Test Exportación Reportes (Parte 2)

```
1. Navegar a: /admin/certificacion/reportes
2. Seleccionar filtros (fecha inicio, fecha fin)
3. Clic en "Generar Reporte"
4. Verificar:
   - ✅ Reporte PDF generado
   - ✅ Tabla con datos correctos
   - ✅ Exportación Excel funciona
   - ✅ Dashboards cargan datos
```

### 3. Test Verificación Digital (Parte 3)

```
1. Navegar a: /admin/certificacion/verificacion
2. Clic en "Verificar Certificado" para un certificado
3. Sistema debe validar:
   - ✅ Integridad de hash
   - ✅ Estado del certificado
   - ✅ Beneficiario válido
   - ✅ Montos correctos
4. Verificar:
   - ✅ Score LGPDP calculado
   - ✅ Tabla auditoria_verificacion tiene registros
   - ✅ Descarga de reporte PDF funciona
   - ✅ Reporte de cumplimiento LGPDP visible
```

### 4. Test Archivado y Backup (Parte 4) ⭐

```
[TEST UNITARIO: Archivamiento Individual]
1. Navegar a: /admin/certificacion/archivado
2. Clic en "Visualizar Archivo" para un certificado
3. Clic en "Archivar Este Certificado"
4. Verificar:
   - ✅ Archivo ZIP creado en storage/certificados_archivados/
   - ✅ Tabla archivo_certificado tiene nuevo registro
   - ✅ UUID generado correctamente
   - ✅ Hash integridad SHA-256 guardado
   - ✅ Motivo archivamiento registrado

[TEST: Descarga de Archivo]
5. Clic en "Descargar Archivo"
6. Verificar:
   - ✅ Descarga ZIP funciona
   - ✅ ZIP contiene: certificado.json + hash_integridad.txt
   - ✅ evento DESCARGA_ARCHIVO registrado en version_certificado

[TEST: Historial de Versiones]
7. Clic en "Ver Versiones"
8. Verificar:
   - ✅ Timeline de versiones visible
   - ✅ Tipo ARCHIVADO_INICIAL mostrado
   - ✅ Datos de versión expandibles

[TEST MASIVO: Archivamiento Masivo]
9. Navegar a: /admin/certificacion/archivado/lote/formulario
10. Seleccionar:
    - Fecha inicio: 01/01/2025
    - Fecha fin: 04/04/2025
    - Estado: CERTIFICADO
11. Clic en "Procesar Archivamiento"
12. Verificar:
    - ✅ Tabla con certificados a archivar
    - ✅ Formulario de confirmación
    - ✅ BDD actualiza múltiples registros
    - ✅ Tabla archivo_certificado crece

[TEST: Backup Masivo]
13. En gestor de archivos: Clic en "Descargar Backup"
14. Verificar:
    - ✅ ZIP creado en storage/backups/
    - ✅ Nombre formato: Backup_Certificados_YYYY-MM-DD-HHMMSS.zip
    - ✅ ZIP contiene múltiples certificados_archivados/

[TEST: Restauración]
15. Navegar a archivo visualizar
16. Clic en "Restaurar Certificado"
17. Verificar:
    - ✅ Confirmación mostrada
    - ✅ evento RESTAURACION registrado en version_certificado
    - ✅ Datos de certificado disponibles nuevamente

[TEST: Limpiar Antiguos]
18. En gestor: Clic en "Limpiar Antiguos"
19. Verificar:
    - ✅ Confirmación mostrada
    - ✅ Archivos con fecha_eliminacion >= 365 días marcados inactivo
    - ✅ Estadísticas actualizadas
```

---

## 🔐 Validación de Seguridad

### 1. Validar HTTPS en Producción

```bash
# Verificar que app.php tiene FORCE_HTTPS
# En .env:
APP_URL=https://sigo.ejemplo.com (NO http://)

# Verificar en nginx/apache config:
# redirect http to https
```

### 2. Validar Autenticación

```bash
# Intentar acceder a rutas sin login:
GET /admin/certificacion/archivado (sin login)
# Esperado: Redirect a login

# Intentar acceder con usuario no admin:
# User tipo 0 (beneficiario) intentando /admin/certificacion/archivado
# Esperado: Error 403 Unauthorized
```

### 3. Validar Integridad de Datos

```bash
php artisan tinker

# Verificar hash de certificado
$hist = DB::table('Historico_Cierre')->first();
$calculado = hash('sha256', json_encode($hist));
$guardado = $hist->hash_certificado;
$calculado === $guardado ? "✅ Integridad OK" : "❌ Tamaño corrompido"

# Verificar archivo archivado
$arch = DB::table('archivo_certificado')->first();
$zip_real = filesize($arch->ruta_almacenamiento);
$zip_real > 0 ? "✅ Archivo existe" : "❌ Archivo no encontrado"

exit
```

### 4. Validar Audit Trail

```sql
-- En SQL Server
SELECT COUNT(*) FROM auditoria_verificacion;
-- Debe haber registros de cada verificación

SELECT COUNT(*) FROM version_certificado;
-- Debe haber registros de cada cambio en certificados

SELECT tipo_cambio, COUNT(*) FROM version_certificado 
GROUP BY tipo_cambio;
-- Debe mostrar: ARCHIVADO_INICIAL, RESTAURACION, DESCARGA_ARCHIVO, etc.
```

---

## 🌐 Deployment a Producción

### PRE-DEPLOYMENT CHECKLIST

```
[ ] SQL Server admin ejecutó permisos o pre-creó tablas
[ ] php artisan migrate ejecutado exitosamente
[ ] Directorios storage/certificados_archivados y storage/backups creados
[ ] Permisos de directorios 755 en production
[ ] .env con APP_URL=https://domain.com
[ ] .env con APP_DEBUG=false
[ ] Backup de BD realizado
[ ] Logs analizados: storage/logs/laravel.log
[ ] HTTPS habilitado en servidor
[ ] Certificados SSL válidos
```

### DEPLOYMENT STEPS

```bash
# 1. En servidor PRODUCCIÓN:
cd /var/www/sigo  # (ajustar ruta según tu servidor)

# 2. Pull del código
git pull origin main

# 3. Instalar/actualizar dependencias
composer install --no-dev --optimize-autoloader

# 4. Ejecutar migraciones
php artisan migrate --force

# 5. Clear caches
php artisan cache:clear
php artisan route:cache
php artisan config:cache

# 6. Verificar permisos
chmod -R 755 storage bootstrap/cache public
chmod -R 777 storage/certificados_archivados storage/backups

# 7. Reiniciar servicios
sudo systemctl restart php-fpm
sudo systemctl restart nginx  # o apache2

# 8. Ver logs
tail -f storage/logs/laravel.log
```

### POST-DEPLOYMENT VERIFICATION

```bash
# 1. Verificar aplicación responde
curl -I https://tu-dominio.com/dashboard

# 2. Ver errores en logs
tail storage/logs/laravel.log

# 3. Acceder a admin panel
https://tu-dominio.com/admin/certificacion/archivado

# 4. Test funcionalidad
- Crear certificado
- Archivar certificado
- Descargar archivo
- Restaurar certificado
```

---

## 📞 TROUBLESHOOTING

### Error: "SQLSTATE[42000]: Se ha denegado el permiso CREATE TABLE"

**Solución**:
1. Asegúrate que ejecutaste: `ALTER ROLE db_owner ADD MEMBER [SIGO-APP];`
2. O: Ejecuta el SQL manual desde `DATABASE_SETUP_MIGRATION_MANUAL.sql`
3. Luego: `php artisan migrate`

### Error: "No such file or directory: storage/certificados_archivados"

**Solución**:
```bash
mkdir -p storage/certificados_archivados storage/backups
chmod 755 storage/certificados_archivados
chmod 755 storage/backups
```

### Error: "The stream or file could not be opened"

**Solución**:
```bash
# Verificar permisos de storage
ls -la storage/
chmod 755 storage
chmod 777 storage/certificados_archivados storage/backups
```

### Error: "SQLSTATE[HY000]: General error: 1030"

**Solución**:
```bash
# Podría ser un problema de espacio disco
df -h  # Verificar espacio disponible

# O reiniciar SQL Server
# En SQL Server Management Studio:
# Restart Service (click derecho en instancia)
```

---

## 🔄 Proceso de Rollback (si es necesario)

```bash
# Ver migraciones ejecutadas
php artisan migrate:status

# Rollback última migración
php artisan migrate:rollback

# Rollback todas las migraciones
php artisan migrate:reset

# Rollback y re-ejecutar
php artisan migrate:refresh
```

---

## 📖 Referencias Útiles

| Tema | Ubicación |
|------|-----------|
| Código del Servicio | `app/Services/ArchivadoCertificadoService.php` |
| Código del Controlador | `app/Http/Controllers/Admin/ArchivadoCertificadoController.php` |
| Modelos | `app/Models/ArchivoCertificado.php`, `VersionCertificado.php` |
| Vistas | `resources/views/admin/certificacion/archivado/` |
| Rutas | `routes/web.php` (búscar "ARCHIVADO") |
| Migraciones | `database/migrations/2025_04_04_*.php` |
| SQL Manual | `DATABASE_SETUP_MIGRATION_MANUAL.sql` |

---

## ✅ CHECKLIST FINAL

Marcar cuando completado:

```
RESOLUCIÓN DE PERMISOS
[ ] DBA otorgó db_owner a SIGO-APP
[ ] O: Pre-creadas tablas manualmente

MIGRACIONES
[ ] php artisan migrate ejecutado
[ ] 3 tablas de Fase 9 creadas exitosamente
[ ] Indices creados
[ ] Foreign keys validadas

DIRECTORIOS Y PERMISOS
[ ] storage/certificados_archivados creado y con permisos 755/777
[ ] storage/backups creado y con permisos 755/777
[ ] Web server puede escribir en directorios

TESTING
[ ] Certificación digital funciona
[ ] Reportes exportan aPDF/Excel
[ ] Verificación valida correctamente
[ ] Archivado masivo funciona
[ ] Restauración funciona
[ ] Limpiar antiguos funciona

SEGURIDAD
[ ] Integridad de datos validada
[ ] Audit trail completada
[ ] HTTPS habilitado (producción)
[ ] Autenticación funciona

DEPLOYMENT
[ ] Backup BD realizado
[ ] APP_DEBUG=false en prod
[ ] Logs monitoreados
[ ] Usuarios notificados
[ ] Plan de rollback documentado
```

---

**Documento Actualizado**: 2025-04-04
**Versión**: 1.0 Final
**Status**: Listo para Implementación
