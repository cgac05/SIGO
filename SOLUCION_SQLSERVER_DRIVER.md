# 🔧 SOLUCIÓN: SQL Server Driver Error en AWS Elastic Beanstalk

## 🔴 Problema
```
could not find driver (Connection: sqlsrv)
```
Los drivers `sqlsrv` y `pdo_sqlsrv` no están cargados en PHP.

---

## 📋 OPCIÓN 1: Diagnóstico Rápido (Sin Redeploy)

Si tienes acceso SSH a la instancia EC2:

### Paso 1: Conectar por SSH
```bash
# Opción A: Con AWS Systems Manager (recomendado, no necesita Key Pair)
aws ssm start-session --target i-xxxxxxxxxx --region us-east-1

# Opción B: Con Elastic Beanstalk CLI
eb ssh --instance-ids <INSTANCE_ID>

# Opción C: Con SSH directo si tienes el Key Pair
ssh -i "your-key.pem" ec2-user@<PUBLIC_IP>
```

### Paso 2: Ejecutar diagnóstico
```bash
# Copiar el script a la instancia o ejecutar directamente:

bash << 'DIAG_EOF'
echo "=== PHP Modules ===" 
php -m | grep -i sqlsrv

echo -e "\n=== Extension Directory ===" 
php -r "echo ini_get('extension_dir') . PHP_EOL;"

echo -e "\n=== PHP.d files ===" 
ls -la /etc/php.d/ | grep sqlsrv

echo -e "\n=== Check sqlsrv.so ===" 
find /usr -name "*sqlsrv*.so" 2>/dev/null

echo -e "\n=== PHP Info ===" 
php -v

echo -e "\n=== ODBC ===" 
odbcinst -q -l -d

echo -e "\n=== Service Status ===" 
systemctl status php-fpm
DIAG_EOF
```

---

## 📋 OPCIÓN 2: Redeploy con Configuración Mejorada (Recomendado)

He preparado 3 archivos `.ebextensions` optimizados:

### 1. `.ebextensions/01_laravel_setup.config` (ACTUALIZADO)
✅ Intenta instalar drivers binarios primero (más confiable)
✅ Fallback a PECL si es necesario
✅ Reinicia PHP-FPM y Nginx
✅ Valida la instalación

### 2. `.ebextensions/03_odbc_config.config` (NUEVO)
✅ Configura ODBC correctamente
✅ Valida drivers ODBC
✅ Prueba conexión a BD en deployment

### Pasos:
```bash
# 1. Asegúrate de tener los archivos en tu repo
ls -la .ebextensions/

# 2. Commit y push
git add .ebextensions/
git commit -m "Fix: SQL Server drivers - binary + PECL fallback"
git push origin main

# 3. Deploy
eb deploy

# 4. Monitorear
eb logs --all
```

---

## 🚨 OPCIÓN 3: Solución de Emergencia (Si nada más funciona)

### A. Cambiar la configuración para no usar SQLSrv en sesiones

Si los drivers no se instalan, puedes usar **File Session Driver** temporalmente:

**`.env`**:
```bash
DB_CONNECTION=sqlsrv
DB_HOST=bdsigoprod.ca7cms0eernu.us-east-1.rds.amazonaws.com
DB_PORT=1433
DB_DATABASE=BD_SIGO
DB_USERNAME=admin
DB_PASSWORD=xxxxx

# CAMBIAR ESTO TEMPORALMENTE:
SESSION_DRIVER=file    # En lugar de: database
# O usar Redis si tienes disponible
```

Luego:
```bash
php artisan config:cache
php artisan cache:clear
```

### B. Alternativa: Usar imagen AMI con drivers preinstalados

Crear una imagen base con los drivers ya compilados es más confiable que instalarlos en cada deploy.

---

## 🔍 Diagnóstico Completo

He preparado un script bash completo para ejecutar en la instancia:

```bash
# En tu máquina local:
scp -i "key.pem" diagnostic_sqlserver.sh ec2-user@<IP>:/tmp/

# Por SSH:
ssh -i "key.pem" ec2-user@<IP>
bash /tmp/diagnostic_sqlserver.sh
```

---

## 📊 Checklist de Validación

Después de cualquier fix, valida:

- [ ] `php -m | grep sqlsrv` → debe mostrar **sqlsrv**
- [ ] `php -m | grep pdo_sqlsrv` → debe mostrar **pdo_sqlsrv**
- [ ] `systemctl status php-fpm` → debe estar **active (running)**
- [ ] `systemctl status nginx` → debe estar **active (running)**
- [ ] Logs de aplicación sin errores → `eb logs`
- [ ] Página carga sin error 500 → visita la URL

---

## 🎯 Resumen de cambios

| Archivo | Cambio | Impacto |
|---------|--------|--------|
| `01_laravel_setup.config` | ✅ Drivers binarios + PECL fallback | Más robustez |
| `03_odbc_config.config` | ✅ Validación ODBC post-deploy | Visibilidad |
| `diagnostic_sqlserver.sh` | ✅ Script de diagnóstico | Debug rápido |

---

## ❓ ¿Cuál es el próximo paso?

1. **Si tienes SSH acceso ahora**:
   - Ejecuta el diagnóstico (OPCIÓN 1)
   - Comparte los resultados
   
2. **Si prefieres redeploy limpio**:
   - Verifica que los 3 archivos `.ebextensions` estén en tu repo
   - Haz `git push` y `eb deploy`
   - Monitorea con `eb logs`

3. **Si es urgente**:
   - Usa OPCIÓN 3 (SESSION_DRIVER=file temporalmente)
   - Luego soluciona los drivers

---

## 📞 Siguientes pasos

**Dime qué opción prefieres y si necesitas ayuda para ejecutar los comandos.**
