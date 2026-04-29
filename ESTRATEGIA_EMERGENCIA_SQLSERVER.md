# 🆘 SQL Server Driver - Estrategia de Emergencia

## ⚠️ Situación

Los drivers `sqlsrv` y `pdo_sqlsrv` no se están instalando correctamente en AWS Elastic Beanstalk. El error persiste:

```
could not find driver (Connection: sqlsrv)
```

Los logs se eliminan cada 15 minutos, lo que hace difícil diagnosticar el problema.

---

## 🔧 Solución Implementada

He creado **3 nuevos archivos `.ebextensions`** con una estrategia multi-nivel:

### 1. **`04_session_workaround.config`** ⚡ (ACTIVO AHORA)
- **Cambia `SESSION_DRIVER` a `file`** en lugar de `database`
- Esto permite que **la aplicación funcione inmediatamente** sin depender de SQL Server
- Es **temporal** - se revertirá cuando los drivers estén funcionando

**Impacto**: La app será accesible, pero las sesiones se guardarán en archivos locales en lugar de en la BD.

### 2. **`05_sqlserver_binary_install.config`** 🔨 (PRINCIPAL)
- Instala drivers desde **Microsoft Repository en lugar de compilar con PECL**
- Mucho más confiable en entornos limitados como Elastic Beanstalk
- **Guarda logs en `/var/app/current/storage/logs/eb_install/`** (persistentes)
- Ejecuta verificaciones y tests de conexión
- Reinicia servicios correctamente

**Estrategia**:
```
1. Instala ODBC Driver 18 para SQL Server (Microsoft)
2. Intenta instalar php-sqlsrv + php-pdo_sqlsrv desde dnf (binarios precompilados)
3. Crea archivos .ini para cargar las extensiones
4. Reinicia PHP-FPM y Nginx
5. Ejecuta tests de conexión
6. Guarda logs para debugging
```

### 3. **Ruta de Diagnóstico** 🔍
- Agregué: `/diagnostico-sqlserver` (sin autenticación requerida)
- Muestra estado de drivers en tiempo real (HTML visual)
- Accesible incluso si hay errores

---

## 🚀 Próximos Pasos

### OPCIÓN A: Deploy Inmediato
```bash
# Ya hice los cambios, solo falta:
git push origin Produccion
# El pipeline se ejecutará automáticamente
```

### OPCIÓN B: Ver el Estado AHORA (después de push)
1. Espera a que termine el deployment (~5-10 min)
2. Visita: `https://sigo-app-env.eba-pjwh8vad.us-east-1.elasticbeanstalk.com/diagnostico-sqlserver`
3. Verás estado de drivers y conexión a la BD

### OPCIÓN C: Acceder a Logs de Instalación
```bash
# Por SSH en la instancia EC2
ssh -i "key.pem" ec2-user@<IP>

# Ver logs de instalación
ls -lah /var/app/current/storage/logs/eb_install/
tail -100 /var/app/current/storage/logs/eb_install/

# Ver estado actual de PHP
php -m | grep sqlsrv
php -m | grep pdo_sqlsrv
```

---

## 📋 Cambios Realizados

| Archivo | Tipo | Propósito |
|---------|------|----------|
| `.ebextensions/04_session_workaround.config` | Nuevo | SESSION_DRIVER=file (temporal) |
| `.ebextensions/05_sqlserver_binary_install.config` | Nuevo | Instalación robusta de drivers (sin PECL) |
| `routes/web.php` | Modificado | +Ruta `/diagnostico-sqlserver` |
| `storage/diagnostico.php` | Existente | Página HTML de diagnóstico |

---

## 🎯 Timeline Esperado

| Evento | Tiempo |
|--------|--------|
| Push a GitHub | Ahora |
| Pipeline inicia | <1 min |
| Deployment en EB | ~5-10 min |
| Drivers se instalan | ~2-3 min (durante deployment) |
| App accesible | ~10 min |
| Diagnóstico disponible | ~12 min |

---

## ✅ Validación Posterior a Deploy

Una vez desplegado, verifica:

1. **Acceso a la aplicación**:
   ```
   https://sigo-app-env.eba-pjwh8vad.us-east-1.elasticbeanstalk.com/
   → Debe cargar sin error 500
   ```

2. **Diagnóstico SQL Server**:
   ```
   https://sigo-app-env.eba-pjwh8vad.us-east-1.elasticbeanstalk.com/diagnostico-sqlserver
   → Debe mostrar estado de drivers en HTML
   ```

3. **Logs de instalación**:
   ```bash
   eb ssh
   cat /var/app/current/storage/logs/eb_install/*.log
   ```

4. **Estado de servicios** (por SSH):
   ```bash
   php -m | grep sqlsrv        # Debe mostrar: sqlsrv
   systemctl status php-fpm     # Debe estar: active (running)
   systemctl status nginx       # Debe estar: active (running)
   ```

---

## 🔄 Una Vez que Funcione

Cuando los drivers estén instalados y funcionando correctamente:

1. **Cambiar SESSION_DRIVER a `database`** (opcional):
   - Editar `.ebextensions/04_session_workaround.config`
   - Cambiar: `SESSION_DRIVER: file` → `SESSION_DRIVER: database`
   - Deploy nuevamente

2. **Limpiar archivos temporales**:
   - Borrar `/storage/logs/eb_install/`
   - Borrar ruta `/diagnostico-sqlserver` de `routes/web.php`

---

## ❌ Si Aún Falla

Si después de este deployment el problema persiste:

1. **Verificar logs de instalación**:
   ```bash
   eb logs --all
   cat /var/app/current/storage/logs/eb_install/*.log
   ```

2. **Verificar estado de DNF/Yum**:
   ```bash
   dnf list installed | grep -i sqlsrv
   dnf list available | grep -i sqlsrv
   ```

3. **Verificar versión de PHP**:
   ```bash
   php -v
   # Los drivers deben coincidir con la versión PHP
   # PHP 8.5.4 requiere drivers compatibles con PHP 8.5
   ```

4. **Como último recurso**: Contactar a AWS Support para:
   - Verificar que dnf/yum tiene acceso a Microsoft repositories
   - Verificar conexión de red de la instancia EC2
   - Verificar que hay suficiente espacio en disco

---

## 📞 Resumen

**Ahora**: La aplicación debería funcionar (sesiones en archivos)
**Después**: Los drivers se instalarán y se conectarán a SQL Server
**Diagnóstico**: Siempre disponible en `/diagnostico-sqlserver`

¿Lista para hacer push?
