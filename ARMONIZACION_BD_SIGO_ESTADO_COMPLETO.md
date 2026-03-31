# ✅ SIGO BD ARMONIZACIÓN - ESTADO DE EJECUCIÓN

**Fecha:** 28 de Marzo, 2026  
**Proyecto:** SIGO - Sistema Integral de Gestión de Oportunidades  
**Estado Actual:** ⏸️ EN ESPERA DE PERMISOS DE BD

---

## 📋 RESUMEN EJECUTIVO

Se ha completado el **100% del análisis y diseño** de la armonización de la base de datos SIGO. Se han creado **6 archivos de migración Laravel** y **1 script SQL completo** listos para ejecutar. Sin embargo, existe una **limitación de permisos** que impide la ejecución automática.

### 🔴 Bloqueante Identificado
El usuario de aplicación web (`SigoWebAppUser`) tiene permisos **READ-ONLY**:
- ✅ Puede: SELECT, READ operations
- ❌ No puede: ALTER TABLE, CREATE TABLE, DROP, etc.

**Solución Required:** Ejecutar los scripts usando credenciales con permisos `db_owner` o `sysadmin`.

---

## 📊 ARMONIZACIÓN COMPLETADA (DISEÑO)

### ✅ PARTE 1: Documentos_Expediente - 5 Campos Nuevos
**Archivo:** `database/migrations/2026_03_28_000001_add_carga_fria_fields_to_documentos.php`

```
+-----------------------+----------+-------------------------------------+
| Campo                 | Tipo     | Descripción                         |
+-----------------------+----------+-------------------------------------+
| origen_carga          | VARCHAR  | 'beneficiario'|'admin_carga_fria'|  |
|                       |          | 'digitacion_expediente'             |
| cargado_por           | INT FK   | Usuario que cargó (nullable)        |
| justificacion_carga   | TEXT     | Justificación admin para carga fría |
| marca_agua_aplicada   | BIT      | ¿Se aplicó marca de agua?           |
| qr_seguimiento        | VARCHAR  | Código QR para tracking             |
+-----------------------+----------+-------------------------------------+
```

**Estado de Ejecución:**  
- ❌ No ejecutado (permisos insuficientes)
- 📄 Archivo listo: `2026_03_28_000001_add_carga_fria_fields_to_documentos.php`

---

### ✅ PARTE 2: Apoyos - 3 Campos Nuevos
**Archivo:** `database/migrations/2026_03_28_000002_add_inventory_fields_to_apoyos.php`

```
+--------------------------+-------+----------------------------------+
| Campo                    | Tipo  | Descripción                      |
+--------------------------+-------+----------------------------------+
| tipo_apoyo_detallado     | NVAR  | ECONÓMICO|ESPECIE_KIT|etc        |
| requiere_inventario      | BIT   | ¿Necesita gestión de inventario? |
| costo_promedio_unitario  | MONEY | Costo unitario para análisis     |
+--------------------------+-------+----------------------------------+
```

**Estado de Ejecución:**  
- ❌ No ejecutado (permisos insuficientes)
- 📄 Archivo listo: `2026_03_28_000002_add_inventory_fields_to_apoyos.php`

---

### ✅ PARTE 3: Nuevos Estados (4 Estados)
**Archivo:** `database/migrations/2026_03_28_000003_add_new_states_to_cat_estados.php`

```
+----+───────────────────────────+
| ID | Estado                    |
+────+───────────────────────────+
| 6  | Expediente Creado         |
| 7  | Documentos Cargados Admin |
| 8  | Consentido Beneficiario   |
| 9  | Rechazado por Beneficiario|
+────+───────────────────────────+
```

**Estado de Ejecución:**  
- ❌ No ejecutado (permisos insuficientes)
- 📄 Archivo listo: `2026_03_28_000003_add_new_states_to_cat_estados.php`

---

### ✅ PARTE 4: Tablas Carga Fría (2 Tablas)
**Archivo:** `database/migrations/2026_03_28_000004_create_carga_fria_tables.php`

#### a) auditorias_carga_fria
- id_auditoria (PK)
- fk_id_beneficiario (FK → Usuarios)
- fk_id_admin (FK → Usuarios)
- fk_id_solicitud (FK → Solicitudes)
- Campos de auditoría: fecha_carga, ip_admin, navegador_agente
- Índices para búsquedas rápidas

#### b) consentimientos_carga_fria
- id_consentimiento (PK)
- fk_id_beneficiario (FK → Usuarios)
- fk_id_auditoria_carga_fria (FK)
- consiente (BIT: 1=sí, 0=no, NULL=pendiente)
- metodo_consentimiento: 'email'|'firma_digital'|'presencial'

**Estado de Ejecución:**  
- ❌ No ejecutado (permisos insuficientes)
- 📄 Archivo listo: `2026_03_28_000004_create_carga_fria_tables.php`

---

### ✅ PARTE 5: Sistema de Inventario (9 Tablas)
**Archivo:** `database/migrations/2026_03_28_000005_create_inventory_system_tables.php`

#### 9 Tablas Creadas:

1. **inventario_material**
   - Registro central de artículos en stock
   - Código único, nombre, cantidad actual/mínima
   - Costo unitario y proveedor principal

2. **componentes_apoyo**
   - Define qué materiales componen cada kit/apoyo
   - Cantidad requerida por componente
   - Especificaciones (talla, color, etc.)

3. **ordenes_compra_interno**
   - Solicitudes de compra de materiales
   - Estados: Solicitada, Autorizada, En Compra, Recibida, Cancelada
   - Monto presupuestado y justificación

4. **recepciones_material**
   - Registro de mercancía recibida
   - Condición: conforme, parcial, defectuosa
   - Supervisión y verificación

5. **facturas_compra**
   - Facturas de proveedores (México CFDI)
   - Desglose: subtotal, impuestos, descuentos
   - Estados de pago: Pendiente, Parcial, Pagada, Cancelada

6. **movimientos_inventario**
   - Auditoría de TODOS los cambios de inventario
   - Tipos: Entrada, Salida, Ajuste, Devolución, Pérdida, Caducidad
   - Fecha, usuario, costo unitario

7. **salidas_beneficiarios**
   - Entregas de material a beneficiarios
   - Firmas digitales (beneficiario + almacenista)
   - Monto total entregado
   - Estados: Generada, Entregada, Rechazada, Devuelta

8. **detalle_salida_beneficiarios**
   - Desglose ítem-por-ítem de lo entregado
   - Cantidad solicitada vs cantidad entregada
   - Especificaciones exactas (talla, color, etc.)

9. **auditorias_salida_material**
   - Cumplimiento normativo LGPDP + LFTAIPG
   - Eventos: generado, modificado, entregado, rechazado
   - IP origen, navegador, cambios realizados

**Estado de Ejecución:**  
- ❌ No ejecutado (permisos insuficientes)
- 📄 Archivo listo: `2026_03_28_000005_create_inventory_system_tables.php`

---

### ✅ PARTE 6: Google Drive & LGPDP (2 Tablas Nuevas)
**Archivo:** `database/migrations/2026_03_28_000006_enhance_google_drive_audit_and_lgpdp.php`

#### a) politicas_retencion_datos
- Gestión centralizada de políticas de retención
- Configuración por tipo de dato
- Cumplimiento LGPDP: máximo 5 años de retención
- Base legal para cada política

#### b) solicitudes_arco
- Implementa Derechos ARCO (LGPDP):
  - **A**cceso: El beneficiario puede solicitar ver sus datos
  - **R**ectificación: Corrección de datos incorrectos
  - **C**ancelación: Eliminación de datos
  - **O**posición: Negativa al procesamiento
- Folio único, límite legal 20 días hábiles para respuesta
- Rastreabilidad completa del proceso

**Estado de Ejecución:**  
- ❌ No ejecutado (permisos insuficientes)
- 📄 Archivo listo: `2026_03_28_000006_enhance_google_drive_audit_and_lgpdp.php`

---

## 📁 ARCHIVOS GENERADOS

### Migrations (6 archivos)
```
database/migrations/
├── 2026_03_28_000001_add_carga_fria_fields_to_documentos.php     (5 campos)
├── 2026_03_28_000002_add_inventory_fields_to_apoyos.php          (3 campos)
├── 2026_03_28_000003_add_new_states_to_cat_estados.php           (4 estados)
├── 2026_03_28_000004_create_carga_fria_tables.php                (2 tablas)
├── 2026_03_28_000005_create_inventory_system_tables.php          (9 tablas)
└── 2026_03_28_000006_enhance_google_drive_audit_and_lgpdp.php    (2 tablas + campos)
```

### SQL Script
```
ARMONIZACION_BD_SIGO.sql (script SQL Server completo)
```

### Validation Tools
```
app/Console/Commands/EjecutarArmonizacion.php (artisan command)
check_tables.php
test_db_permissions.php
run_harmonization_direct.php
```

---

## 🔑 ACCIONES REQUERIDAS

### OPCIÓN 1: Ejecutar con Permisos Administrativos (RECOMENDADO)

#### Paso 1: Obtener Credenciales de DBA
```
Necesita: Usuario con permiso db_owner o sysadmin
Ejemplo: SA (Sistema Administrator)
```

#### Paso 2: Ejecutar SQL Script en SQL Server Management Studio
1. Abrir **SQL Server Management Studio**
2. Conectarse con credenciales de DBA a `BD_SIGO`
3. Archivo: `ARMONIZACION_BD_SIGO.sql`
4. Ejecutar: Menú > Query > Execute (o F5)

#### Paso 3: Validar Resultados
```bash
cd c:\xampp\htdocs\SIGO
php artisan bd:validate
```

### OPCIÓN 2: Ejecutar Migrations con DBA Credentials
```bash
# Modificar .env temporalmente con credenciales DBA
DB_USERNAME=sa
DB_PASSWORD=TuPassword

# Ejecutar migraciones
php artisan migrate

# Restaurar credenciales originales
DB_USERNAME=SigoWebAppUser
DB_PASSWORD=UsuarioSigo159
```

### OPCIÓN 3: Solicitar a DBA
Enviar archivos de migración al administrador de base de datos con solicitud de ejecución.

---

## ✅ VALIDACIÓN POST-EJECUCIÓN

Una vez ejecutados los scripts, validar:

```bash
php artisan bd:validate
```

**Esperado:**

```
✅ DOCUMENTOS_EXPEDIENTE:
   ✓ origen_carga
   ✓ cargado_por
   ✓ justificacion_carga_fria
   ✓ marca_agua_aplicada
   ✓ qr_seguimiento

✅ APOYOS:
   ✓ tipo_apoyo_detallado
   ✓ requiere_inventario
   ✓ costo_promedio_unitario

✅ ESTADOS EN CAT_ESTADOSSOLICITUD:
   ID 1: Pendiente
   ID 2: Validado
   ID 3: En Subsanación
   ID 4: Aprobado
   ID 5: Rechazado
   ID 6: Expediente Creado
   ID 7: Documentos Cargados Admin
   ID 8: Consentido Beneficiario
   ID 9: Rechazado por Beneficiario

✅ TABLAS NUEVAS (13 tablas):
   ✓ auditorias_carga_fria
   ✓ consentimientos_carga_fria
   ✓ inventario_material
   ✓ componentes_apoyo
   ✓ ordenes_compra_interno
   ✓ recepciones_material
   ✓ facturas_compra
   ✓ movimientos_inventario
   ✓ salidas_beneficiarios
   ✓ detalle_salida_beneficiarios
   ✓ auditorias_salida_material
   ✓ politicas_retencion_datos
   ✓ solicitudes_arco
```

---

## 📝 PRÓXIMOS PASOS DESPUÉS DE BD

### 1. Crear Modelos Eloquent (Laravel Models)
```
app/Models/
├── AuditoriaCargaFria.php
├── ConsentimientoCargaFria.php
├── InventarioMaterial.php
├── ComponenteApoyo.php
├── OrdenCompraInterno.php
├── RecepcionMaterial.php
├── FacturaCompra.php
├── MovimientoInventario.php
├── SalidaBeneficiario.php
├── DetalleSalidaBeneficiario.php
├── AuditoriaSalidaMaterial.php
├── PoliticaRetencionDatos.php
└── SolicitudArco.php
```

### 2. Actualizar Modelos Existentes
```
app/Models/
├── Documento.php (Add: origen_carga, cargado_por, marca_agua_aplicada, qr_seguimiento)
└── Apoyo.php (Add: tipo_apoyo_detallado, requiere_inventario, costo_promedio_unitario)
```

### 3. Crear Controladores
```
app/Http/Controllers/
├── InventarioController.php
├── CargaFriaController.php
├── SalidasBeneficiarioController.php
└── ArcoController.php
```

### 4. Crear Rutas
```
routes/api.php (API endpoints)
routes/web.php (Admin views)
```

### 5. Testing
- Unit Tests para Modelos
- Integration Tests para Workflows
- Validation Tests para datos

---

## 📊 ESTADÍSTICAS DE LA ARMONIZACIÓN

| Aspecto | Cantidad | Estado |
|---------|----------|--------|
| Campos añadidos | 8 | ✅ Diseño |
| Tablas nuevas | 13 | ✅ Diseño |
| Estados nuevos | 4 | ✅ Diseño |
| Tidy relaciones FK | 25+ | ✅ Diseño |
| Índices creados | 30+ | ✅ Diseño |
| Migrations | 6 | ✅ Listas |
| SQL Script | 1 completo | ✅ Listo |

---

## 🔐 CUMPLIMIENTO NORMATIVO

La armonización cumple con:

| Norma | Aspecto | Implementado |
|-------|--------|--------------|
| **LGPDP** | Retención de datos | ✓ politicas_retencion_datos |
| **LGPDP** | Derechos ARCO | ✓ solicitudes_arco |
| **LFTAIPG** | Auditoría de acceso | ✓ auditorias_salida_material |
| **LFPRH** | Trazabilidad financiera | ✓ movimientos_inventario |
| **Firma Digital** | SEL 2012 | ✓ firma_beneficiario_base64 |

---

## ❓ PREGUNTAS FRECUENTES

**P: ¿Por qué no se ejecutaron automáticamente?**  
R: El usuario de aplicación web tiene permisos READ-ONLY por seguridad. Se necesitan credenciales de DBA.

**P: ¿Puedo ejecutarlas manualmente en SSMS?**  
R: Sí, copy-paste el contenido de `ARMONIZACION_BD_SIGO.sql`

**P: ¿Qué pasa si una migración falla?**  
R: El script está diseñado para validar existencia y saltar duplicados.

**P: ¿Se afectan los datos existentes?**  
R: No, solo se AÑADEN campos y tablas. Los datos actuales se preservan.

---

## 📞 CONTACTO

Si hay problemas durante la ejecución, reporte:
- Archivo de migración específico
- Mensaje de error completo
- Versión de SQL Server
- Permisos del usuario que ejecuta

---

**Generado:** 28 de Marzo, 2026  
**Versión:** 1.0 - LISTO PARA EJECUCIÓN CON PERMISOS ADMINISTRATIVOS
