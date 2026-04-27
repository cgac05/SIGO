# 🔍 Diagnóstico sin Logs de AWS - Guía Completa

## Problema
Los logs de Elastic Beanstalk se eliminan cada 15 minutos y las rutas `/debug-db` y `/diagnostico-sqlserver` devuelven 404.

## Solución: 3 Métodos para Ver Qué Está Pasando

---

## ✅ MÉTODO 1: Health Check en el Navegador (MÁS FÁCIL)

### URL:
```
https://sigo-app-env.eba-pjwh8vad.us-east-1.elasticbeanstalk.com/healthcheck.php
```

### Qué muestra:
- ✓ Versión de PHP
- ✓ Extensiones cargadas (sqlsrv, pdo_sqlsrv, etc.)
- ✓ Directorio de extensiones
- ✓ Archivos .ini de configuración
- ✓ Drivers PDO disponibles
- ✓ Prueba de conexión a la BD
- ✓ Últimos logs de instalación (si existen)
- ✓ Estado del sistema de archivos

### Cómo interpretar:
- **Green (✓)**: Todo bien
- **Red (✗)**: Error crítico
- **Yellow (⚠)**: Advertencia

---

## ✅ MÉTODO 2: Ruta JSON `/debug-db`

### URL:
```
https://sigo-app-env.eba-pjwh8vad.us-east-1.elasticbeanstalk.com/debug-db
```

### Respuesta ejemplo:
```json
{
  "timestamp": "2026-04-27T10:30:45Z",
  "hostname": "ip-172-31-x-x",
  "php_version": "8.5.4",
  "extensions": {
    "sqlsrv": true,
    "pdo_sqlsrv": true,
    "pdo": true,
    "odbc": true
  },
  "pdo_drivers": ["mysql", "sqlsrv"],
  "sqlsrv_available": true,
  "connection_test": true,
  "session_driver": "file",
  "installation_logs": ["2026-04-27_10-15-32_install.log"]
}
```

### Qué significa:
| Campo | Valor | Significado |
|-------|-------|-------------|
| `extensions.sqlsrv` | `true` | ✓ Driver instalado |
| `extensions.sqlsrv` | `false` | ✗ Driver NO instalado |
| `sqlsrv_available` | `true` | ✓ PDO puede usarlo |
| `connection_test` | `true` | ✓ Conexión a BD exitosa |
| `connection_test` | `false` | ✗ Error en conexión (lee `error`) |

---

## ✅ MÉTODO 3: Logs por SSH (MÁS COMPLETO)

### Paso 1: Conectar por SSH
```bash
# Opción A: Con Elastic Beanstalk CLI (recomendado)
eb ssh

# Opción B: Con AWS Systems Manager (sin Key Pair)
aws ssm start-session --target i-xxxxxxxxxx --region us-east-1

# Opción C: SSH directo
ssh -i "key.pem" ec2-user@<PUBLIC_IP>
```

### Paso 2: Ejecutar el script de diagnóstico
```bash
cd /var/app/current

# Hacer el script ejecutable
chmod +x get-eb-logs.sh

# Ejecutar
./get-eb-logs.sh
```

Esto creará una carpeta `eb_diagnostics_YYYYMMDD_HHMMSS/` con 36+ archivos de logs.

### Paso 3: Revisar los logs más importantes
```bash
# ¿Está cargado sqlsrv?
cat eb_diagnostics_*/06_php_modules.txt | grep sqlsrv

# Logs de instalación de los drivers
cat eb_diagnostics_*/17_eb_install_logs_content.txt | tail -100

# Errores de PHP-FPM
cat eb_diagnostics_*/22_php_fpm_error.log.txt | tail -50

# Errores de Nginx
cat eb_diagnostics_*/24_nginx_error.log.txt | tail -50

# Logs de Laravel
cat eb_diagnostics_*/30_laravel.log.txt | tail -100
```

### Paso 4: (Opcional) Descargar logs a tu máquina
```bash
# En tu máquina local
scp -i "key.pem" ec2-user@<IP>:/var/app/current/eb_diagnostics_*/*.txt ./logs/
```

---

## ✅ MÉTODO 4: Verificación Rápida sin Script

```bash
# Conectar por SSH primero
eb ssh

# 1. ¿Está cargado sqlsrv?
php -m | grep sqlsrv

# 2. ¿Dónde está el archivo .so?
find /usr -name "sqlsrv.so" 2>/dev/null

# 3. ¿Qué dice php.ini?
php -r "echo ini_get('extension_dir');"
ls -la /etc/php.d/ | grep sqlsrv

# 4. ¿Hay logs de instalación?
ls -lah /var/app/current/storage/logs/eb_install/
tail -100 /var/app/current/storage/logs/eb_install/*.log

# 5. ¿Está corriendo PHP-FPM?
systemctl status php-fpm

# 6. ¿Está corriendo Nginx?
systemctl status nginx

# 7. ¿Cuál es el estado de ODBC?
odbcinst -q -l -d
```

---

## 📊 Interpretación de Resultados

### ✅ Si TODO está bien:
```
✓ php -m | grep sqlsrv  → "sqlsrv"
✓ /debug-db → connection_test: true
✓ /healthcheck.php → Todos los indicadores en verde
✓ Puede conectar a la BD
```
→ **Acción**: Cambiar `SESSION_DRIVER=file` a `SESSION_DRIVER=database` en `.env`

### ⚠️ Si sqlsrv NO está cargado:
```
✗ php -m | grep sqlsrv  → (no output)
✗ /healthcheck.php → sqlsrv: ✗ NOT LOADED
✗ /debug-db → connection_test: null, error: "pdo_sqlsrv extension not loaded"
```
→ **Acción**: Ver logs en `/var/app/current/storage/logs/eb_install/` para ver por qué falló instalación

### ⚠️ Si sqlsrv está cargado pero no se conecta:
```
✓ php -m | grep sqlsrv  → "sqlsrv"
✗ /debug-db → connection_test: false, error: "..."
```
→ **Acción**: Revisar credenciales de BD, security groups, y logs de error específico

---

## 🚨 Qué Revisar Primero

1. **¿Cargó healthcheck.php?**
   - Sí → Revisa los indicadores en verde/rojo
   - No → El deployment no llegó, revisa con `eb logs --all`

2. **¿Dice que sqlsrv está cargado?**
   - Sí → Revisa logs de conexión
   - No → Revisa logs de instalación en `/eb_install/`

3. **¿Se conecta a la BD?**
   - Sí → Cambiar SESSION_DRIVER a database
   - No → Revisar credenciales y security groups

---

## 🛠️ Acciones Según Resultado

### Si sqlsrv NO se instaló:
```bash
# Ver logs de instalación
tail -100 /var/app/current/storage/logs/eb_install/*.log

# Intentar instalar manualmente
ACCEPT_EULA=Y dnf install -y php-sqlsrv php-pdo_sqlsrv

# Si falla, intentar PECL
pecl install sqlsrv pdo_sqlsrv

# Crear archivo .ini manualmente
echo "extension=sqlsrv.so" > /etc/php.d/30-sqlsrv.ini
echo "extension=pdo_sqlsrv.so" > /etc/php.d/30-pdo_sqlsrv.ini

# Reiniciar PHP-FPM
systemctl restart php-fpm
```

### Si sqlsrv está cargado pero no se conecta:
```bash
# Verificar credenciales
cat /var/app/current/.env | grep DB_

# Verificar que puede alcanzar la BD
telnet bdsigoprod.ca7cms0eernu.us-east-1.rds.amazonaws.com 1433

# Ver logs de error específicos
cat /var/log/php-fpm/error.log | tail -50
```

---

## 📝 Resumen de Herramientas

| Herramienta | URL/Comando | Ventaja | Desventaja |
|-----------|-----------|---------|-----------|
| **healthcheck.php** | `/healthcheck.php` | Visual, sin SSH | Requiere deployment |
| **/debug-db** | `/debug-db` | JSON, fácil parsear | Requiere deployment |
| **get-eb-logs.sh** | `./get-eb-logs.sh` | Completo, 36+ archivos | Requiere SSH |
| **Comandos manuales** | `php -m`, etc. | Rápido | Requiere SSH |

---

## 🎯 Plan de Acción Inmediato

1. **Ahora**: Visita `/healthcheck.php`
   - ¿Funciona? → Analiza los indicadores
   - ¿Error 404? → Deployment aún en progreso, espera 10 min

2. **Si funciona**: Toma screenshot/nota de:
   - Estado de sqlsrv (verde/rojo)
   - Estado de pdo_sqlsrv (verde/rojo)
   - Resultado de connection test
   - Mensajes de error (si los hay)

3. **Si no funciona o hay errores**:
   - Conecta por SSH: `eb ssh`
   - Ejecuta: `./get-eb-logs.sh`
   - Comparte los logs de diagnóstico

---

## ❓ Preguntas Frecuentes

**P: ¿Por cuánto tiempo persisten los logs?**
R: Los del sistema se borran cada 15 min. Los de `/eb_install/` persisten en storage.

**P: ¿Puedo acceder a healthcheck sin internet?**
R: No, necesitas acceso a la URL. Pero puedes usar SSH para revisar los mismos archivos localmente.

**P: ¿Es seguro dejar healthcheck.php en producción?**
R: No, deberías borrar `/public/healthcheck.php` después de debuggear.

**P: ¿Cómo automatizo esto?**
R: El script `get-eb-logs.sh` automáticamente recopila todo. Ejecuta con cron si lo necesitas periódicamente.
