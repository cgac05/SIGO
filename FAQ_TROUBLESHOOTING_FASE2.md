# 🆘 FAQ Y TROUBLESHOOTING - FASE 2

## Preguntas Frecuentes

### P: ¿Por qué el SQL tarda tanto?
**R:** No debería tardar más de 30 segundos. Si tarda más:
1. Cierra todas las pestañas de SSMS
2. Desconecta y reconecta a la base de datos
3. Ejecuta primero: `USE BD_SIGO;` antes de ejecutar el script

---

### P: ¿Puedo ejecutar el SQL varias veces?
**R:** Sí, es seguro. El SQL tiene lógica para:
- Eliminar folio 1000 si existe
- Recrearlo limpio
- Asignar nuevos IDs de hito
- Verificar integridad referencial

Esto es útil para "resetear" el test si lo necesitas.

---

### P: ¿El SQL borra datos de otros folios?
**R:** NO. El SQL SOLO toca:
- Folio 1000 (eliminado y recreado)
- Documentos de folio 1000 (eliminados y recreados)
- Hitos de folio 1000 (solo para actualizar estado)

El resto de la base de datos no se afecta.

---

### P: ¿Qué debo hacer si aparece error "Invalid Object Name"?
**R:** Significa que la tabla no existe. Soluciones:
1. Verifica que estés en base de datos `BD_SIGO`:
   ```sql
   SELECT DB_NAME()
   ```
2. Si retorna otra BD, ejecuta primero:
   ```sql
   USE BD_SIGO;
   ```
3. Si aún así falla, contacta al DBA para verificar la estructura de tablas

---

### P: ¿Cómo verifico que el usuario dora1 se creó correctamente?
**R:** Ejecuta:
```sql
SELECT u.id_usuario, u.nombre, p.fk_rol, p.nombre_completo
FROM Usuarios u
LEFT JOIN Personal p ON u.id_usuario = p.fk_id_usuario
WHERE u.nombre = 'dora1'
```

Resultado esperado:
```
id_usuario  nombre  fk_rol  nombre_completo
-----------+-------+-------+---------------
[ID]        dora1   2       Dora Prueba
```

---

### P: ¿Puedo cambiar el usuario de test a otro?
**R:** Sí, pero debes editar el SQL. Busca la línea:
```sql
SELECT id_usuario FROM Usuarios WHERE nombre = 'dora1'
```

Y cambia `'dora1'` por el usuario que quieras.

---

### P: ¿Cuál es el flujo correcto en la aplicación?
**R:**
```
1. Inicia sesión como dora1 (123456789)
2. Menú → Solicitudes en Proceso
3. Busca folio 1000 (debe estar en "ANALISIS_ADMIN")
4. Haz clic en "Ver" o icon de firma
5. Se abre la página de Resumen Crítico
6. Verifica los 5 bloques de información
7. Marca los 4 checkboxes:
   - ✓ Confirmo perfil del beneficiario
   - ✓ Confirmo monto del apoyo
   - ✓ Confirmo documentos requeridos
   - ✓ Asumo responsabilidad
8. Haz clic en "Proceder a Firmar"
9. La página debe redirigir a /solicitudes/proceso
10. Folio debe estar en Fase 3 (RESULTADOS)
```

---

## Errores Comunes y Soluciones

### Error: "Base de datos no existe"
```
Mensaje: "Database 'BD_SIGO' does not exist"
```

**Causa:** Es probable que en tu servidor la base de datos tenga otro nombre o estés conectado al servidor incorrecto.

**Solución:**
```sql
-- Verifica qué bases de datos existen:
SELECT name FROM sys.databases ORDER BY name

-- Si ves algo como "SIGO_PROD" o "BD_SIGO_DEV", usa ese nombre en el SQL
```

---

### Error: "Invalid column name"
```
Mensaje: "Invalid column name 'presupuesto_confirmado'"
```

**Causa:** La estructura de la table `Solicitudes` es diferente.

**Solución:**
```sql
-- Verifica las columnas de Solicitudes:
EXEC sp_help 'Solicitudes'

-- Busca el nombre correcto de las columnas
-- Luego edita el SQL para usar los nombres correctos
```

---

### Error: SQL se ejecutó pero no ves folio 1000
```
Mensaje: No veo folio 1000 en /solicitudes/proceso
```

**Causa:** Posiblemente el folio se creó pero en estado incorrecto o el usuario no tiene permisos.

**Verificación:**
```sql
-- 1. Verifica que folio existe:
SELECT folio, fk_id_estado FROM Solicitudes WHERE folio = 1000

-- 2. Verifica permisos del usuario:
SELECT u.nombre, p.fk_rol 
FROM Usuarios u
LEFT JOIN Personal p ON u.id_usuario = p.fk_id_usuario
WHERE u.nombre = 'dora1'
-- Debe retornar rol = 2 o 3

-- 3. Revisa los logs:
-- Archivo: storage/logs/laravel.log
-- Busca errores de "Authorization" o "Route"
```

---

### Error: "Acceso denegado" cuando intento abrir firma
```
Mensaje: "No tienes permiso para acceder a esta solicitud"
```

**Causa:** El usuario dora1 no tiene permiso de rol correcto.

**Solución:**
```sql
-- Verifica el rol:
SELECT p.fk_rol FROM Personal p
WHERE fk_id_usuario = (SELECT id_usuario FROM Usuarios WHERE nombre = 'dora1')

-- Debe retornar 2 (Admin) o 3 (Directivo)
-- Si retorna algo diferente, actualiza:
UPDATE Personal SET fk_rol = 2 
WHERE fk_id_usuario = (SELECT id_usuario FROM Usuarios WHERE nombre = 'dora1')
```

---

### Error: Los checkboxes aparecen pero no funciona el botón
```
Componentes sin responder al clic
```

**Causa:** El cache de Laravel no se limpió correctamente.

**Solución:**
```bash
# En terminal en la carpeta del proyecto:
cd c:\xampp\htdocs\SIGO

# Limpia los caches:
php artisan view:clear
php artisan cache:clear
php artisan config:clear

# Recarga la página en el navegador (Ctrl + Shift + R para forzar)
```

---

### Error: Botón "Proceder a Firmar" no redirige
```
Se queda en la misma página, no va a /solicitudes/proceso
```

**Causa:** La ruta POST no se registró correctamente o hay error en el controller.

**Verificación:**
```bash
# En terminal:
php artisan route:list | findstr "completar-fase-2"

# Debe mostrar:
# POST solicitudes/{folio}/firma/completar-fase-2
```

Si no aparece, verifica que esté en `routes/web.php` línea ~277.

---

### Error: "Undefined variable in FirmaController"
```
Mensaje: "Undefined variable: $usuario" en FirmaController.php
```

**Causa:** El método `completarFase2()` no encuentra el usuario autenticado.

**Solución:**
1. Verifica que estés autenticado como dora1
2. Revisa que `Auth::user()` existe en el método
3. Si falta, contacta al desarrollo

---

### Error: Folio no cambia a Fase 3
```
Comportamiento: Folio permanece en ANALISIS_ADMIN después de completar
```

**Causa:** El hito siguiente (RESULTADOS) no existe en la base.

**Verificación:**
```sql
-- Obtén el apoyo del folio 1000:
SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1000

-- Luego verifica hitos (reemplaza X con el id del apoyo):
SELECT id_hito, clave_hito, orden_hito 
FROM Hitos_Apoyo 
WHERE fk_id_apoyo = X
ORDER BY orden_hito

-- Debe haber:
-- Orden 1: PUBLICACION
-- Orden 2: RECEPCION
-- Orden 3: ANALISIS_ADMIN
-- Orden 4: RESULTADOS
-- Orden 5: CIERRE
```

Si faltan hitos, ejecuta el SQL nuevamente.

---

## Resetear Completamente

Si algo salió mal y quieres empezar desde cero:

### Opción 1: Ejecutar SQL de nuevo
```sql
-- Simplemente ejecuta el SQL nuevamente
-- Eliminará folio 1000 anterior y creará uno nuevo limpio
```

### Opción 2: Borrar manualmente
```sql
-- Si prefieres borrar a mano:

-- 1. Borra documentos:
DELETE FROM Documentos_Expediente WHERE fk_folio = 1000

-- 2. Borra folio:
DELETE FROM Solicitudes WHERE folio = 1000

-- 3. Luego ejecuta el SQL de setup nuevamente
```

---

## Validar Cambios del Código

### Verifica que el código fue aplicado correctamente:

```bash
# 1. Verifica que FirmaController tiene el nuevo método:
findstr /n "completarFase2" app\Http\Controllers\FirmaController.php

# Debe retornar algo como:
# 275:    public function completarFase2(int $folio)

# 2. Verifica que existe la ruta:
findstr /n "completar-fase-2" routes\web.php

# Debe retornar la línea con la ruta POST

# 3. Verifica que firma.blade.php no tiene duplicados:
findstr /c:"@component('components.firma.resumen-critico'" resources\views\solicitudes\firma.blade.php

# Debe retornar SOLO 1 línea (línea ~30-35)
```

---

## Logs para Debugging

Si algo falla en tiempo de ejecución, revisa:

```bash
# Archivo principal de logs:
C:\xampp\htdocs\SIGO\storage\logs\laravel.log

# Busca líneas con:
# - [ERROR] - Errores críticos
# - [WARNING] - Avisos
# - "Fase 2 completada" - Log de auditoría de éxito
```

---

## Contactar a Soporte

Si ninguna solución funciona:

1. **Captura de pantalla** - Muestra exactamente qué error ves
2. **Logs** - Adjunta contenido de `storage/logs/laravel.log`
3. **SQL Output** - Si el SQL falla, muestra el mensaje de error completo
4. **Información del Sistema:**
   ```sql
   SELECT @@VERSION, DB_NAME()
   ```

---

**Última actualización:** 12/04/2026
**Versión:** 1.0
