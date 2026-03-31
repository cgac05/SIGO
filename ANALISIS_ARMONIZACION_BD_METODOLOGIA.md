# 🔍 ANÁLISIS DE ARMONIZACIÓN: BASE DE DATOS ↔ METODOLOGÍA

**Fecha:** 28 de Marzo de 2026  
**Propósito:** Validar consistencia entre la estructura de BD implementada y la documentación de metodología  
**Estado:** Reporte Exhaustivo con Identificación de Inconsistencias  

---

## 📊 RESUMEN EJECUTIVO

| Aspecto | Estado | Crítico |
|--------|--------|---------|
| **Tablas Documentadas** | 16 planeadas | ⚠️ |
| **Tablas Implementadas** | 11 creadas | ⚠️ |
| **Migraciones Ejecutadas** | 15 archivos | ✅ |
| **Campos Faltantes** | 8-10 campos | ⚠️ |
| **Inconsistencias Detectadas** | 6 críticas | 🔴 |
| **Sugerencias de Mejora** | 12 recomendaciones | 📝 |

---

## ✅ FASE 1: ESTADO DE TABLAS ACTUALES

### A. TABLAS DOCUMENTADAS EN METODOLOGÍA (Esperadas)

```
MÓDULO BENEFICIARIOS:
  ✅ usuarios                         (Tabla padre - Todo roles)
  ✅ Beneficiarios                    (Rol 0)
  ✅ Personal                         (Roles Admin/Directivo)
  ✅ Solicitudes                      (Trámites)
  ✅ Documentos_Expediente            (Archivos por solicitud)
  
MÓDULO APOYOS:
  ✅ Apoyos                           (Catálogo de apoyos)
  ✅ Requisitos_Apoyo                 (Qué docs son obligatorios)
  ✅ Hitos_Apoyo                      (Workflow: PUBLICACION → CIERRE)
  
GOOGLE DRIVE:
  ✅ google_drive_files               (Metadata de archivos)
  ✅ google_drive_audit_logs          (Trazabilidad - FALTANTE)
  
CATEGORÍAS CATÁLOGOS:
  ✅ Cat_EstadosSolicitud             (Estados)
  ✅ Cat_Prioridades                  (Baja/Normal/Alta)
  ✅ Cat_TiposDocumento               (Tipos de doc)
  
FINANCIERO (Básico):
  ✅ BD_Finanzas                      (Presupuesto, monto_ejercido)
  ✅ BD_Inventario                    (Stock para apoyos en especie)
```

### B. TABLAS DESTINADAS PARA CARGA FRÍA (NO CREADAS AÚN)

```
❌ auditorias_carga_fria             (Auditoría de cargas por admin)
❌ consentimientos_carga_fria        (Aprobación posterior beneficiario)
```

### C. TABLAS DESTINADAS PARA INVENTARIO (NO CREADAS AÚN)

```
❌ inventario_material               (Stock detallado por componente)
❌ componentes_apoyo                 (Partes del kit)
❌ ordenes_compra_interno            (OC-)  
❌ recepciones_material              (REC-)
❌ facturas_compra                   (FAC-)
❌ movimientos_inventario            (Auditoría de salidas)
❌ salidas_beneficiarios             (SAL-)
❌ detalle_salida_beneficiarios      (Quién recibió qué)
❌ auditorias_salida_material        (Reversiones/cambios)
```

### D. TABLAS PARCIALMENTE DISEÑADAS (EN MIGRACIONES)

```
⚠️ Documentos_Expediente             (Campos de Google Drive OK, pero faltan campos de Carga Fría)
⚠️ Apoyos                            (Tipo_apoyo OK, pero faltan campos para especie)
```

---

## 🔴 INCONSISTENCIAS DETECTADAS (CRÍTICAS)

### **INCONSISTENCIA #1: Tabla de Documentos - Campos de Origen Faltantes**

**Documentado en Metodología:**
```
--- Tabla documentos_expediente (PASO 3.5 - Carga Fría)
    ├─ origen_carga VARCHAR(50)       ['beneficiario' | 'admin_carga_fria']
    ├─ cargado_por INT (FK → usuarios)
    └─ justificacion_carga_fria TEXT
```

**Implementado en BD Actual:**
```php
// Modelo Documento.php
protected $fillable = [
    'fk_folio',
    'fk_id_tipo_doc',
    'ruta_archivo',
    'estado_validacion',
    'version',
    'fecha_carga',
    'origen_archivo',            ← EXISTE pero diferente nombre
    'google_file_id',
    'google_file_name',
    'admin_status',              ← Confuso: ¿es para verificación o carga?
    'admin_observations',
    'verification_token',
    'id_admin',
    'fecha_verificacion',
];
```

**Problema:**
- Campo `origen_archivo` existe, pero propósito es **DISTINTO** a `origen_carga`
- NO hay `cargado_por` (FK a admin que cargó)
- NO hay `justificacion_carga_fria`
- El nombre `admin_status` es **ambiguo** (¿verificación? ¿carga?)

**Impacto:** 
🔴 **CRÍTICO** - Las cargas frías NO se pueden registrar sin estos campos

---

### **INCONSISTENCIA #2: Tabla Apoyos - Campos para Apoyos en Especie Faltantes**

**Documentado en Metodología (Paso 3.6):**
```
ALTER TABLE apoyos ADD (
    tipo_apoyo_detallado VARCHAR(50),        -- ECONÓMICO | ESPECIE_KIT | ESPECIE_ÚNICO
    requiere_inventario BIT,                 -- 1 si necesita gestión almacén
    costo_promedio_unitario MONEY,           -- Para análisis de especie
    bodega_asignada INT                      -- FK a bodega (si existe)
);
```

**Implementado en BD Actual:**
```php
// Modelo Apoyo.php
protected $fillable = [
    'nombre_apoyo',
    'anio_fiscal',
    'tipo_apoyo',                ← SOLO hay 'tipo_apoyo' (sin "_detallado")
    'monto_maximo',
    'cupo_limite',
    'activo',
    'fecha_inicio',
    'fecha_fin',
    'foto_ruta',
    'descripcion',
];
```

**Problema:**
- NO hay `tipo_apoyo_detallado`
- NO hay `requiere_inventario`
- NO hay `costo_promedio_unitario`
- NO hay `bodega_asignada`

**Impacto:** 
🔴 **CRÍTICO** - La gestión de apoyos en especie (inventario, compras, salidas) NO funcionará

---

### **INCONSISTENCIA #3: Tablas de Google Drive - Auditoría Faltante**

**Documentado en Metodología (Paso 2.3):**
```
✅ Tabla: google_drive_files       → CREADA ✓
❌ Tabla: google_drive_audit_logs  → DOCUMENTADA pero NO CREADA
```

**Migración Actual:**
```php
// 2026_03_25_create_google_drive_files_table.php

Schema::create('google_drive_files', function (Blueprint $table) {
    $table->id();
    $table->integer('user_id');
    $table->string('google_file_id')->unique();
    $table->string('file_name');
    $table->bigInteger('file_size');
    $table->string('mime_type');
    $table->text('storage_path');
    $table->timestamps();
    // Foreign key OK
});
```

**Problema:**
- NO existe tabla `google_drive_audit_logs` para trazabilidad (IP, navegador, timestamp)
- Documentado en GOOGLE_DRIVE_IMPLEMENTATION.md línea 127
- La auditoría se necesita para LGPDP (requerimiento normativo)

**Impacto:** 
🔴 **CRÍTICO** - No hay trazabilidad de quién accedió a Google Drive, cuándo, desde dónde

---

### **INCONSISTENCIA #4: Relaciones entre Solicitud y Usuario (Ambigüedad PK)**

**Documentado en Metodología (Paso 1.2):**
```
Modelo de relaciones:
Usuarios / Personal / Beneficiarios
    ↓
Solicitudes  (folio, estado, apoyo_solicitado)
```

**Implementado en BD Actual:**

```php
// Modelo Solicitud.php
protected $table = 'Solicitudes';
protected $primaryKey = 'folio';
public $incrementing = false;
protected $keyType = 'int';

protected $fillable = [
    'fk_curp',                    ← Usa CURP, no id_usuario
    'fk_id_apoyo',
    'fk_id_estado',
    'fk_id_prioridad',
    // Falta: fk_id_beneficiario (nombre confuso)
];
```

**Problema:**
- FK a Beneficiario se llama `fk_curp` (debería ser `fk_id_beneficiario`)
- Pero tabla Beneficiarios probablemente tiene `id_usuario` como PK, NO `curp`
- **MNameJoin ambiguo:** ¿Es `fk_curp` una FK a tabla `Beneficiarios` o directa a `curp` de `Usuarios`?

**Impacto:** 
🟠 **MAYOR** - Posibles errores en consultas JOIN, audits e integridad referencial

---

### **INCONSISTENCIA #5: Nombre de Tabla Inconsistente - `Documentos_Expediente` vs `Documentos`**

**Documentado en Metodología:**
```
Tabla: Documentos_Expediente (múltiples por solicitud)
```

**Implementado en BD Actual:**
```php
// Modelo: app/Models/Documento.php
protected $table = 'Documentos_Expediente';  ← OK

// Pero en migraciones:
// 2026_03_21_000003_add_google_drive_fields_to_documentos_expediente.php
Schema::table('Documentos_Expediente', function (Blueprint $table) {
    // Modifica OK
});

// Sin embargo, hay conflicto con modelo Usuario
// En Google Drive: $table->foreign('user_id')->references('id_usuario')->on('Usuarios');
// Pero en Documento: referencias a 'Users' (tabla Laravel default)
```

**Problema:**
- Inconsistencia en nombres de tablas Laravel default (`users`) vs custom (`Usuarios`)
- Podría causar problemas si hay migraciones que crean tabla `Users` automáticamente

**Impacto:** 
🟠 **MAYOR** - Riesgo de duplicación de tablas, foreign key errors

---

### **INCONSISTENCIA #6: Estado de Solicitud - Documentado vs Implementado**

**Documentado en Metodología (Paso 3.5 - Carga Fría):**
```
Estados para solicitud con Carga Fría:
    ├─ DOCUMENTOS_CARGADOS_ADMIN   (después de carga por admin)
    ├─ CONSENTIDO                   (beneficiario aprobó)
    └─ RECHAZADO_POR_BENEFICIARIO   (beneficiario dijo que no)
```

**Implementado en BD Actual:**
```php
// database/migrations/2026_03_12_070000_create_solicitudes_tables.php

DB::table('Cat_EstadosSolicitud')->insert([
    ['id_estado' => 1, 'nombre_estado' => 'Pendiente'],
    ['id_estado' => 2, 'nombre_estado' => 'En revisión'],
    ['id_estado' => 3, 'nombre_estado' => 'Aprobada'],
    ['id_estado' => 4, 'nombre_estado' => 'Rechazada'],
]);
```

**Problema:**
- Los 4 estados básicos existentes NO incluyen estados específicos de Carga Fría
- Los estados para Carga Fría NO fueron agregados a la tabla de catálogo

**Impacto:** 
🔴 **CRÍTICO** - El workflow de Carga Fría NO se puede seguir (no hay estados para ello)

---

## 🟡 CAMPOS FALTANTES O INCORRECTOS (MAYORES)

### Campo: `ip_origen` / `navegador_agente`

**Documentado:** Presente en tablas de auditoría de Carga Fría, Inventario, Salidas  
**Implementado:** 
- ✅ Existe en `google_drive_files` como FK
- ✅ Existe en algunas migraciones
- ❌ NO existe en tabla central de auditoría general

**Impacto:** 🟠 MAYOR - Trazabilidad LGPDP incompleta

---

### Campo: `version` en Documentos

**Documentado:** No mencionado (pero implícito en "re-carga de documentos")  
**Implementado:** ✅ Existe en `Documento.php` fillable

**Impacto:** ✅ OK - Pero necesita validarse que se incremente con cada re-carga

---

### Campo: `folio_institucional` en Solicitud

**Documentado:** `Folio: SIGO-2026-TEP-0050` (ejemplo en Carga Fría)  
**Implementado:** 
- ✅ `folio` existe como PK en tabla Solicitudes
- ❌ Pero tipo es `int`, NO `string` (no puede ser "SIGO-2026-TEP-0050")

**Impacto:** 🔴 **CRÍTICO** - El sistema de folio NO funciona como documentado

---

## 📋 TABLA COMPARATIVA: Campos Documentados vs Implementados

### Tabla: `Documentos_Expediente`

| Campo Documentado | Tipo Doc | Campo en BD | Estado | Error |
|---|---|---|---|---|
| `id_doc` | PK | `id_doc` | ✅ | - |
| `fk_folio` | FK | `fk_folio` | ✅ | - |
| `fk_id_tipo_doc` | FK | `fk_id_tipo_doc` | ✅ | - |
| `ruta_local` | VARCHAR | `ruta_archivo` | ⚠️ | Nombre diferente |
| `google_file_id` | VARCHAR | `google_file_id` | ✅ | - |
| `origin_carga` | VARCHAR | ❌ | ❌ **FALTANTE** | Crítico para Carga Fría |
| `cargado_por` | FK | ❌ | ❌ **FALTANTE** | Crítico para Auditoría |
| `justificación_carga_fría` | TEXT | ❌ | ❌ **FALTANTE** | Crítico para LGPDP |
| `verificado` | BIT | ❌ | ❌ **FALTANTE** | Necesario para workflow |
| `verification_token` | VARCHAR | `verification_token` | ✅ | - |
| `admin_status` | VARCHAR | `admin_status` | ⚠️ | Ambiguo (verificación ≠ carga) |
| `admin_observations` | TEXT | `admin_observations` | ✅ | - |

### Tabla: `Apoyos`

| Campo Documentado | Tipo Doc | Campo en BD | Estado | Error |
|---|---|---|---|---|
| `id_apoyo` | PK | `id_apoyo` | ✅ | - |
| `nombre_apoyo` | VARCHAR | `nombre_apoyo` | ✅ | - |
| `tipo_apoyo` | VARCHAR | `tipo_apoyo` | ⚠️ | Necesita ser más específico |
| `tipo_apoyo_detallado` | VARCHAR | ❌ | ❌ **FALTANTE** | Para ECONÓMICO/ESPECIE_KIT/ESPECIE_ÚNICO |
| `monto_maximo` | MONEY | `monto_maximo` | ✅ | - |
| `requiere_inventario` | BIT | ❌ | ❌ **FALTANTE** | Crítico para gestión especie |
| `costo_promedio_unitario` | MONEY | ❌ | ❌ **FALTANTE** | Necesario para salidas |
| `bodega_asignada` | INT (FK) | ❌ | ❌ **FALTANTE** | Necesario para inventario |
| `foto_ruta` | VARCHAR | `foto_ruta` | ✅ | - |
| `descripcion` | TEXT | `descripcion` | ✅ | - |

---

## 🟢 LO QUE SI FUNCIONA BIEN (Implementación Correcta)

✅ **Autenticación y Roles:**
- Tabla `usuarios` con campo `rol`
- Middleware de autorización
- OAuth 2.0 Google integrado

✅ **Google Drive API:**
- Tabla `google_drive_files` creada
- Relación User → GoogleDriveFile OK
- Campos storage_path, mime_type, file_size presentes

✅ **Verificación Administrativa Básica:**
- Tabla `Cat_EstadosSolicitud` con 4 estados
- Campo `verification_token` para QR
- Campo `admin_status` para aprobación/rechazo

✅ **Estructura Base Correcta:**
- Modelos Eloquent bien definidos
- Foreign keys con cascadas OK
- Índices apropiados

---

## 🔴 BLOQUEADORES IDENTIFICADOS (Que impiden avanzar)

### Bloqueador #1: **Sistema de Folio Incorrecto**

**Documentado:**
```
Folio: SIGO-2026-TEP-0050  (STRING con formato específico)
```

**Implementado:**
```
PK folio es tipo INT autoincremental
Tabla solicitudes genera: folio = 1, 2, 3... (NO "SIGO-2026-TEP-0050")
```

**¿Por qué es bloqueador?**
- Los beneficiarios recibirán emails con "folio 1" (inaceptable)
- Reportes fiscal requerirán "SIGO-2026-TEP-0050" para trazabilidad
- LGPDP requires folio único, auditable

**Recomendación:** Cambiar PK de folio (INT) a columna separada, crear columna `folio_institucional` (STRING)

---

### Bloqueador #2: **Tablas de Carga Fría No Existen**

**Documentado:** 3 tablas nuevas para Carga Fría
**Implementado:** 0 tablas creadas

**¿Por qué es bloqueador?**
- La Fase 3.5 (Carga Fría) está en PLANEADO pero tablas de BD no existen
- Sin tablas, no se pueden guardar registros de carga fría
- Compliance LGPDP incompleto (no hay auditoría de consentimiento)

---

### Bloqueador #3: **Sistema de Inventario Incompleto**

**Documentado:** 10 tablas para inventario (flujos A-D)
**Implementado:** 2 tablas (BD_Finanzas, BD_Inventario) - BÁSICAS sin detalle

**¿Por qué es bloqueador?**
- No hay trazabilidad de órdenes de compra
- No hay recepción de material registrada
- No hay validación de facturas vs presupuesto
- Salidas de material a beneficiarios SIN auditoría

---

## 📝 SUGERENCIAS ANTES DE EJECUTAR (Preguntas para Ti)

Te haré 12 preguntas estratégicas. Una vez que respuestas, podemos decidir qué implementar:

### **GRUPO 1: Folio del Sistema**

**P1) ¿Quieres mantener el folio como INT autoincremental Y agregar un folio_institucional adicional?**
```
Opción A: Mantener INT + agregar campo folio_institucional STRING (RECOMENDADO)
    → Consultas más rápidas (INT), pero generamos formato "SIGO-2026-TEP-0050"
    
Opción B: Cambiar PK a STRING directamente
    → Más lento, pero más realista para auditoría
    
Opción C: Generar folio de otra forma (trigger SQL Server)
    → Automático, pero complejo
```

---

### **GRUPO 2: Campos Faltantes en Documentos**

**P2) ¿Deseas agregar los 3 campos faltantes para Carga Fría?**
```
Campo 1: origen_carga (VARCHAR 50) - 'beneficiario' | 'admin_carga_fria'
Campo 2: cargado_por (INT FK → usuarios) - quién cargó
Campo 3: justificacion_carga_fria (TEXT) - por qué admin lo cargó

Impacto: 1 migración nueva, actualizar Modelo Documento.php
```

**P3) ¿Renombramos `origen_archivo` a algo más claro?**
```
Opción A: Mantener como está (origen_archivo)
Opción B: Renombrar a origen_documento (más claridad)
Opción C: Renombrar a ubicacion_original (físico vs digital)
```

---

### **GRUPO 3: Estados de Solicitud**

**P4) ¿Agregamos 3 nuevos estados para Carga Fría a Cat_EstadosSolicitud?**
```
Id 5: DOCUMENTOS_CARGADOS_ADMIN
Id 6: CONSENTIDO_BENEFICIARIO
Id 7: RECHAZADO_POR_BENEFICIARIO

Impacto: 1 migración nueva, actualizar lógica en services
```

---

### **GRUPO 4: Campos de Apoyos**

**P5) ¿Deseas agregar campos para gestión de apoyos en especie?**
```
Campo 1: tipo_apoyo_detallado (VARCHAR) - ECONÓMICO | ESPECIE_KIT | ESPECIE_ÚNICO
Campo 2: requiere_inventario (BIT) - 1 si necesita gestión almacén
Campo 3: costo_promedio_unitario (MONEY) - para salidas
Campo 4: bodega_asignada (INT FK) - dónde se guarda

Impacto: 1 migración, pero BLOQUEA todas las features de apoyos en especie
```

**P6) ¿Existe una tabla de "Bodegas" en el sistema?**
```
Opción A: Sí, existe → Proporciona el nombre/estructura
Opción B: No existe → Necesitamos crearla antes
Opción C: Por ahora, usamos VARCHAR simple (sin FK a bodega)
```

---

### **GRUPO 5: Tablas Nuevas para Carga Fría**

**P7) ¿Procedemos a crear las 2 tablas nuevas de Carga Fría?**
```
✅ auditorias_carga_fria (id_auditoria PK, beneficiario, admin, solicitud, justificación)
✅ consentimientos_carga_fria (id FK a auditoría, consiente BIT, fecha_consentimiento)

Impacto: 2 migraciones nuevas, 2 modelos nuevos
Complejidad: MEDIA
Prioridad: ALTA (LGPDP compliance)
```

---

### **GRUPO 6: Tablas Nuevas para Inventario**

**P8) ¿Procedemos a crear las 10 tablas nuevas de Inventario?**
```
✅ inventario_material
✅ componentes_apoyo
✅ ordenes_compra_interno
✅ recepciones_material
✅ facturas_compra (¿o integrar con BD_Finanzas existente?)
✅ movimientos_inventario
✅ salidas_beneficiarios
✅ detalle_salida_beneficiarios
✅ auditorias_salida_material

Impacto: 9 migraciones, 9 modelos, lógica compleja
Complejidad: ALTA
Prioridad: MEDIA (Fase 3.6, después de Carga Fría)
Estimado: 1-2 semanas de desarrollo + testing
```

---

### **GRUPO 7: Google Drive - Auditoría**

**P9) ¿Creamos tabla google_drive_audit_logs para trazabilidad?**
```
✅ google_drive_audit_logs (id_audit PK, user_id FK, google_file_id, accion, ip, navegador, fecha)

Impacto: 1 migración, 1 modelo
Complejidad: BAJA
Prioridad: ALTA (LGPDP compliance)
```

---

### **GRUPO 8: Normalización de Nombres**

**P10) ¿Estandarizamos los nombres de tablas?**
```
OPCIÓN A: Mantener formato MIXTO actual (OK, ya existe código)
    Tablas: Apoyos, Solicitudes, Documentos_Expediente, Usuarios, Beneficiarios

OPCIÓN B: Convertir todo a snake_case (SQL best practice)
    Tablas: apoyos, solicitudes, documentos_expediente, usuarios, beneficiarios
    
IMPACTO de B: Cambiar todos los modelos Eloquent, muy riesgoso en PROD
RECOMENDACIÓN: Mantener OPCIÓN A (ya está en uso)
```

---

### **GRUPO 9: Relaciones y Foreign Keys**

**P11) ¿Validamos la relación en Solicitud (fk_curp vs fk_id_beneficiario)?**
```
Actual: protected fillable incluye 'fk_curp'
Pregunta: ¿Debería ser fk_id_beneficiario en lugar de fk_curp?

Opciones:
A) SI - Renombrar fk_curp → fk_id_beneficiario (más estándar)
B) NO - Mantener fk_curp (documenta que es PK extraño)

Impacto: 1 migración de ALTER TABLE, actualizar modelos
Riesgo: BAJO (si la FK funciona actual, no ha falledoido migración)
```

---

### **GRUPO 10: Campos Redundantes**

**P12) ¿Revisamos campos que podrían ser redundantes?**

Identificados:
```
1) En Documento: 
   - estado_validacion + admin_status (¿ambos para lo mismo?)
   - ruta_archivo + storage_path (¿diferencia real?)

2) En Solicitud:
   - fk_id_estado + estado (¿ambos existen?)

3) En Personal / Beneficiarios / Usuarios:
   - ¿Hay diferencia funcional o solo roles?
```

**Acción:** ¿Deseas que elabore diagrama de redundancia?

---

## 📌 PLAN PROPUESTO DE EJECUCIÓN

### **FASE 1: CORRECCIONES INMEDIATAS (Críticas)**
Prioridad: 🔴 Hacer antes de cualquier otra cosa

```
1. Agregar campos faltantes en Documentos_Expediente
   └─ origen_carga, cargado_por, justificacion_carga_fria, verificado
   
2. Agregar estados en Cat_EstadosSolicitud
   └─ DOCUMENTOS_CARGADOS_ADMIN, CONSENTIDO_BENEFICIARIO, RECHAZADO_POR_BENEFICIARIO
   
3. Agregar campos en Apoyos
   └─ tipo_apoyo_detallado, requiere_inventario, costo_promedio_unitario

4. VALIDAR relaciones Solicitud (fk_curp vs fk_id_beneficiario)
```

Estimado: **1-2 días** de desarrollo - RECOMENDADO HACERLO JA

---

### **FASE 2: CARGA FRÍA INTEGRAL (Fase 3.5)**
Prioridad: 🟠 MEDIA-ALTA

```
1. Crear tablas de Carga Fría
2. Crear servicios CargaFriaService
3. Crear vistas y controlador
4. Test end-to-end
```

Estimado: **4-5 días** - After FASE 1

---

### **FASE 3: SISTEMA DE INVENTARIO COMPLETO (Fase 3.6)**
Prioridad: 🟠 MEDIA (posteriior a Carga Fría)

```
1. Crear 9 tablas de inventario
2. Crear flujos A-D (compra, recepción, factura, salida)
3. Integración presupuestaria
4. Testing + QA
```

Estimado: **8-10 días** - After FASE 2

---

## ✅ VALIDACIONES DE CONSISTENCY

### Migración de Referencia: 2026_03_12_070000_create_solicitudes_tables.php

```php
// ✅ CORRECTO
Schema::create('Requisitos_Apoyo', function (Blueprint $table) {
    $table->unsignedInteger('fk_id_apoyo');
    $table->foreign('fk_id_apoyo')
          ->references('id_apoyo')->on('Apoyos')
          ->onDelete('cascade');
});

// ⚠️ POTENCIAL PROBLEMA EN SOLICITUDES
// FK es 'fk_curp' pero debería ser FK numerical a id_usuario
```

---

## 📊 MATRIZ DE DECISIÓN

| Pregunta | Respuesta Sugerida | Dependencias |
|--|--|--|
| **P1: Folio** | Opción A: INT + folio_institucional | Base para todo |
| **P2: Campos Carga Fría** | SÍ - Crear | P1, P4 |
| **P3: Renombrar origen_archivo** | Depende de decisión actual | P2 |
| **P4: Nuevos Estados** | SÍ - Crear | P2 |
| **P5: Campos Apoyos** | SÍ - Crear | P1 |
| **P6: ¿Existe tabla Bodegas?** | Validar en BD | P5 |
| **P7: Tablas Carga Fría** | SÍ (después de P1-P4) | P2, P4 |
| **P8: Tablas Inventario** | SÍ (después de P7, FASE 2) | P5, P6 |
| **P9: Google Drive Audit** | SÍ - Crear | LGPDP |
| **P10: Estandarizar nombres** | NO (mantener actual) | Bajo riesgo |
| **P11: Relaciones validar** | SÍ - Revisar | Integridad |
| **P12: Redundancia** | SÍ - Análisis | Documentación |

---

## 🎯 PRÓXIMOS PASOS

Una vez que respondas las 12 preguntas anteriores, procedemos con:

1. **Generar migraciones nuevas** (específicas según tus respuestas)
2. **Actualizar modelos Eloquent**
3. **Validar integridad referencial** (tests de FK)
4. **Ejecutar migraciones en orden** (dependencias)
5. **Documentar cambios** en archivo de CHANGELOG
6. **Testing end-to-end** de workflows actuales

---

## 📎 ARCHIVOS AFECTADOS (Para cambios)

```
📝 Por crear o modificar:

database/migrations/
├── 2026_03_XX_add_carga_fria_fields_to_documentos.php (NUEVA)
├── 2026_03_XX_add_new_states_to_cat_estados.php (NUEVA)
├── 2026_03_XX_add_inventory_fields_to_apoyos.php (NUEVA)
├── 2026_03_XX_create_auditorias_carga_fria_table.php (NUEVA)
├── 2026_03_XX_create_consentimientos_carga_fria_table.php (NUEVA)
├── 2026_03_XX_create_google_drive_audit_logs_table.php (NUEVA)
└── (9+ más para inventario si P8 = SÍ)

app/Models/
├── Documento.php (ACTUALIZAR - agregar campos)
├── Apoyo.php (ACTUALIZAR - agregar campos)
├── Solicitud.php (VALIDAR relaciones)
├── AuditoriaCargaFria.php (NUEVA)
├── ConsentimientoCargaFria.php (NUEVA)
├── GoogleDriveAuditLog.php (NUEVA)
└── (9+ más para inventario si P8 = SÍ)

config/
└── Documentación de cambios
```

---

**Fin del Análisis. Esperando tus respuestas para proceder con implementación.**

