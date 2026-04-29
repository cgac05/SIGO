# Diagnóstico: Error "could not find driver" en AWS + Error 404 en Laravel

## 🔴 Problema 1: "could not find driver (sqlsrv)"

### Causa raíz:
Los drivers `sqlsrv` y `pdo_sqlsrv` **NO están instalados o habilitados** en PHP en el servidor EC2 de Elastic Beanstalk.

### Por qué ocurre:
- El archivo `.ebextensions/01_laravel_setup.config` intenta instalarlos, pero:
  1. **No reiniciaba PHP-FPM** después de instalar
  2. **No validaba** que la instalación fuera exitosa
  3. **La instalación fallaba en silencio** con `|| true`

### Solución aplicada:
✅ Actualicé `01_laravel_setup.config` para:
- Instalar y habilitar los drivers correctamente
- **Reiniciar PHP-FPM y Nginx** después (comandos 06, 07)
- **Validar que los drivers estén presentes** (comando 08)

---

## 🔴 Problema 2: Error 404 en `/debug-db`

### Causa raíz:
Nginx **no estaba configurado para pasar las solicitudes a PHP-FPM**.

### Por qué ocurre:
El archivo original solo tenía:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Pero le falta** el bloque que le dice a Nginx **cómo procesar archivos `.php`**:
```nginx
location ~ \.php$ {
    fastcgi_pass php_upstream;  # ← ESTO FALTABA
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

Sin esto, cuando solicitas `/debug-db`, Nginx:
1. Intenta encontrar el archivo literal `/public/debug-db` (no existe)
2. Intenta `/public/debug-db/` (no existe)
3. Intenta pasar a `/public/index.php?debug-db` **pero no sabe cómo procesarlo**
4. Retorna 404

### Solución aplicada:
✅ Configuración mejorada de Nginx con upstream PHP-FPM

---

## 📋 Checklist de validación en AWS:

Después de desplegar, **verifica esto en la consola de Elastic Beanstalk**:

```bash
# 1. Conectar por SSH a tu instancia EC2
eb ssh

# 2. Verificar drivers instalados
php -m | grep -i sqlsrv
php -m | grep -i pdo_sqlsrv

# 3. Verificar configuración PHP
grep -r "extension.*sqlsrv" /etc/php.d/

# 4. Verificar estado de PHP-FPM
systemctl status php-fpm

# 5. Verificar estado de Nginx
systemctl status nginx

# 6. Ver logs de PHP
tail -50 /var/log/php-fpm/error.log

# 7. Ver logs de Nginx
tail -50 /var/log/nginx/error.log

# 8. Probar conexión a la BD desde Laravel
cd /var/app/current
php artisan tinker
>>> DB::connection('sqlsrv')->getPdo();
```

---

## 🚀 Pasos para desplegar:

1. **Asegúrate de tener ambos archivos `.ebextensions`**:
   - `01_laravel_setup.config` (actualizado)
   - `02_validate_sqlserver.config` (nuevo)

2. **Haz commit y push**:
   ```bash
   git add .ebextensions/
   git commit -m "Fix: sqlsrv drivers y Nginx config para Laravel routing"
   git push
   ```

3. **Redeploy a Elastic Beanstalk**:
   ```bash
   eb deploy
   ```

4. **Monitorea el deployment**:
   ```bash
   eb logs
   ```

---

## ⚠️ Si aún falla:

Si después de esto sigue sin funcionar:

1. **Verifica la versión de PHP**:
   ```bash
   php -v
   # Los drivers deben coincidir con tu versión PHP
   # Actual: sqlsrv-5.12.0 es para PHP 7.4+ a 8.3
   ```

2. **Verifica variables de entorno**:
   ```bash
   cat /etc/php.ini
   # Busca: display_errors, error_log, etc.
   ```

3. **Si la instalación de PECL falla**, considera usar drivers precompilados:
   ```bash
   # En lugar de compilar con PECL, usa drivers binarios
   yum install -y php-sqlsrv php-pdo_sqlsrv
   ```

---

## 📝 Cambios realizados:

| Archivo | Cambio |
|---------|--------|
| `01_laravel_setup.config` | ✅ Añadido upstream PHP-FPM en Nginx |
| `01_laravel_setup.config` | ✅ Añadido bloque `location ~ \.php$` |
| `01_laravel_setup.config` | ✅ Añadido reinicio de PHP-FPM y Nginx |
| `01_laravel_setup.config` | ✅ Añadido validación post-install |
| `02_validate_sqlserver.config` | ✅ Nuevo archivo para validar drivers |

---

## 🎯 Resumen:

**Problema**: Drivers SQL Server no instalados + Nginx no ruteaba a PHP

**Solución**: Configuración completa de drivers + Nginx correctamente configurado + Reinicio de servicios + Validación

**Resultado**: Las rutas de Laravel funcionarán correctamente y se conectará a SQL Server en RDS
