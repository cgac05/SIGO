# 🚨 SETUP PRESUPUESTACIÓN - INSTRUCCIONES IMPORTANTES

## 📋 Estado Actual

- ✅ Código de presupuestación: **COMPLETADO** (100%)
- ✅ Migraciones creadas: **SÍ** 
- ✅ Campos creados en algunas tablas: **PARCIALMENTE**
- ❌ Tablas presupuestación: **PENDIENTE** (Permisos SQL Server insuficientes)

## 🔴 Problema

El usuario `SigoWebAppUser` en SQL Server NO tiene permisos `CREATE TABLE`.

```
Error: "Se ha denegado el permiso CREATE TABLE en la base de datos 'BD_SIGO'"
User: SigoWebAppUser
```

## ✅ Solución: Ejecutar Script SQL Manual

### Paso 1: Abre SQL Server Management Studio (SSMS)

```
1. Inicia SQL Server Management Studio
2. Conecta a: localhost (o tu servidor SQL Server)
3. Autentica como: sa (o usuario admin)
```

### Paso 2: Copia y Ejecuta el Script

**Archivo:** `SQL_PRESUPUESTO_SETUP.sql`

```bash
# Ubicación:
c:\xampp\htdocs\SIGO\SQL_PRESUPUESTO_SETUP.sql
```

Pasos:
1. Abre SSMS
2. New Query
3. Copia el contenido completo de `SQL_PRESUPUESTO_SETUP.sql`
4. Pega en la ventana de query
5. **Ejecuta** (F5 o botón Execute)

### Resultado Esperado

```
✅ Ciclo 2026 creado
✅ Categorías de presupuesto creadas
✅ SETUP COMPLETADO EXITOSAMENTE
```

## 📊 Qué se Crea

1. **ciclos_presupuestarios** - Ciclos fiscales (2026 con $100M)
2. **presupuesto_categorias** - 5 categorías presupuestarias
3. **presupuesto_apoyos** - Sub-asignaciones por apoyo
4. **movimientos_presupuestarios** - Auditoría de transacciones
5. **alertas_presupuesto** - Sistema de alertas
6. **Campos en Solicitudes** - presupuesto_confirmado, fecha_confirmacion, directivo_autorizo

## 🔧 Alternativa: Dar Permisos Permanentes

Si tienes permisos DBA, ejecuta esto para PERMITIR migraciones futuras:

```sql
USE BD_SIGO;
GO

-- Opción A: Permisos básicos (mínimo)
GRANT SELECT, INSERT, UPDATE, DELETE ON SCHEMA::dbo TO [SigoWebAppUser];

-- Opción B: Permitir crear tablas (más permisivo)
GRANT CREATE TABLE TO [SigoWebAppUser];
GRANT ALTER ON SCHEMA::dbo TO [SigoWebAppUser];

-- Opción C: Admin de BD (no recomendado)
ALTER ROLE db_owner ADD MEMBER [SigoWebAppUser];
```

## ✅ Después de Ejecutar el Script

Una vez que tengas las tablas creadas en SQL Server:

1. Verifica en SSMS:
   ```sql
   USE BD_SIGO;
   GO
   SELECT * FROM ciclos_presupuestarios;
   SELECT * FROM presupuesto_categorias;
   ```

2. Luego, en terminal Laravel:
   ```bash
   # Cargar datos iniciales
   php artisan presupuesto:cargar --año=2026
   ```

3. Prueba las rutas:
   ```
   http://localhost:8000/admin/presupuesto/dashboard
   http://localhost:8000/admin/presupuesto/reportes
   ```

## 📎 Archivos Generados

- ✅ `SQL_PRESUPUESTO_SETUP.sql` - Script manual para SSMS
- ✅ `app/Console/Commands/CargarPresupuestoAnual.php` - Command Artisan
- ✅ `database/migrations/2026_03_31_create_presupuesto_tables.php` - Migraciones

## 🎯 Próximos Pasos

Después de ejecutar el script SQL:

1. [ ] Ejecutar SQL_PRESUPUESTO_SETUP.sql en SSMS
2. [ ] Verificar tablas en SQL Server
3. [ ] Ejecutar `php artisan presupuesto:cargar --año=2026`
4. [ ] Probar dashboards en navegador
5. [ ] Continuar con Fase 5: Dashboard & KPIs

---

**Creado:** 3 de Abril de 2026  
**Estado:** En Espera de Ejecución SQL
