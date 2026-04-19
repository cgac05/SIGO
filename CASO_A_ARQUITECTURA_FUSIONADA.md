# Caso A: Arquitectura Fusionada ✨

## Estado Actual

**Decisión Arquitectónica:** FUSIÓN CON FLUJO ORDINARIO (ÓPTIMA)

**Mejora Clave:** Caso A Solicitudes ahora usan el **MISMO verificador** que beneficiarios ordinarios, eliminando duplicación de código y mejorando escalabilidad.

---

## Arquitectura Anterior ❌ (Separada)

```
Caso A Flow:
  Momento 1 → Estado: EXPEDIENTE_CREADO_PRESENCIAL (especial)
                ↓
  Momento 2 → Verificador Caso A (interfaz propia)
                ↓
  Admin verifica en OTRA interfaz

Problemas:
  ❌ Código duplicado (2 verificadores)
  ❌ Testing duplicado
  ❌ Mantenimiento difícil
  ❌ Menos escalable
  ❌ Estados especiales en BD
```

---

## Arquitectura Nueva ✨ (Fusionada)

```
Caso A Flow:
  Momento 1 → Crea Solicitud ORDINARIA
               Estado: DOCUMENTOS_PENDIENTE_VERIFICACIÓN (estándar)
               origen_solicitud = 'admin_caso_a' (marca origen)
                ↓
  Momento 2 → Admin escanea documentos (función auxiliar)
                ↓
  Admin verifica en MISMO verificador ordinario
  (filtra por origen_solicitud para reportes)

Ventajas:
  ✅ UN verificador (sin duplicación)
  ✅ Código más limpio (DRY principle)
  ✅ Testing simplificado
  ✅ Escalable (agregar más orígenes fácilmente)
  ✅ Mantenimiento centralizado
```

---

## Los 3 Momentos - Implementación Fusionada

### 📋 Momento 1: Beneficiario Presente (5-10 mins)

**Flujo:**
1. Admin abre: `/admin/caso-a/momento-uno`
2. Busca beneficiario por cédula/nombre (AJAX)
3. Selecciona apoyo
4. Admin verifica: "Tengo documentos físicos en mano"
5. Ingresa número documento (ej: C-12345678)
6. Selecciona documentos esperados (checkboxes)
7. Sistema genera:
   - **Folio:** 001-2026-TEP (único)
   - **Clave privada:** KX7M-9P2W-5LQ8 (16-char aleatorio)
   - **Hash:** SHA256(folio + clave + app_key)
8. Crea **Solicitud ORDINARIA** con:
   ```php
   [
       'folio_institucional' => '001-2026-TEP',
       'beneficiario_id' => $beneficiario_id,
       'apoyo_id' => $apoyo_id,
       'estado_solicitud' => 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN', // ← ESTÁNDAR
       'origen_solicitud' => 'admin_caso_a',  // ← MARCA ORIGEN
       'creada_por_admin' => 1,
       'admin_creador' => $admin_id,
   ]
   ```
9. Imprime ticket: Folio + Clave + QR
10. Beneficiario se retira con ticket

**Base de Datos:**
```sql
INSERT INTO claves_seguimiento_privadas (
    folio, clave_alfanumerica, hash_clave, beneficiario_id, fecha_creacion
) VALUES (
    '001-2026-TEP', 'KX7M-9P2W-5LQ8', 
    sha2('001-2026-TEPKX7M-9P2W-5LQ8[app_key]', 256),
    $beneficiario_id, NOW()
);
```

---

### 📸 Momento 2: Admin Escanea (24-48 horas después)

**Flujo:**
1. Admin abre: `/admin/caso-a/momento-dos`
2. Ingresa folio (o escanea QR)
3. Para cada documento:
   - Drag-drop upload (PDF/JPG, <5MB)
   - Sistema VALIDA: MIME, tamaño
4. Sistema procesa automáticamente:
   - ✅ Calcula SHA256(documento)
   - ✅ Aplica watermark: "INJUVE · 001-2026-TEP · [Date]"
   - ✅ Genera QR: folio + tipo_doc + timestamp + admin_id
   - ✅ Crea cadena digital: hash_anterior → hash_actual
   - ✅ Firma HMAC-SHA256 (inmutable)
   - ✅ Registra auditoría: evento, admin_id, IP, navegador
5. Guarda en BD con `origen_carga = 'admin_escaneo_presencial'`

**Base de Datos:**
```sql
INSERT INTO documentos_expediente (
    fk_id_solicitud, tipo_documento, origen_carga, cargado_por, 
    hash_documento, marca_agua_aplicada, qr_seguimiento, firma_admin
) VALUES (
    $solicitud_id, 'Cédula', 'admin_escaneo_presencial', $admin_id,
    'a3f9e2c...', 1, '[QR_DATA]', 'HMAC_SIGNATURE'
);

INSERT INTO cadena_digital_documentos (
    fk_id_documento, folio, hash_actual, hash_anterior, firma_hmac
) VALUES (
    $doc_id, '001-2026-TEP', 'a3f9e2c...', NULL, 'HMAC_SIGNATURE'
);

INSERT INTO auditorias_carga_material (
    folio, evento, admin_id, cantidad_docs, fecha_evento, ip_admin, navegador_agente
) VALUES (
    '001-2026-TEP', 'caso_a_momento_2_carga_confirmada', $admin_id, 3, 
    NOW(), $request->ip(), $user_agent
);
```

6. Admin hace clic: **[Confirmar Carga]**
7. Sistema:
   - Valida que hay ≥2 documentos
   - **NO cambia estado** (ya está en DOCUMENTOS_PENDIENTE_VERIFICACIÓN)
   - Genera resumen de auditoría
   - Notifica beneficiario: "✓ Documentos cargados"

---

### 🔍 Momento 3: Beneficiario Consulta (Con clave privada)

**Flujo Público (sin autenticación):**
1. Beneficiario abre: `https://sigo.injuve.mx/consulta-privada`
2. Ingresa:
   - **Folio:** 001-2026-TEP
   - **Clave:** KX7M-9P2W-5LQ8 (del ticket)
3. Sistema verifica:
   ```php
   $ingresada_hash = hash('sha256', 
       $folio . $clave_ingresada . config('app.key')
   );
   if (hash_equals($ingresada_hash, $stored_hash)) {
       // ✓ Acceso concedido
   }
   ```
4. Dashboard privado (solo con clave válida):
   - Status: "Documentos en verificación"
   - Timeline: "Admin escaneó · Verificador aprobó · Directivo va a firmar"
   - Lista de documentos con:
     - ✓ Estado (Verificado/Rechazado/Pendiente)
     - ✓ QR verificable
     - ✓ [Descargar] botón
   - ✓ [Verificar Cadena Digital] - prueba de integridad

---

## Flujo Ordinario (igual para TODOS)

Después de Momento 2, la solicitud **entra al flujo ordinario**:

```
Caso A Solicitud (origen_solicitud='admin_caso_a'):
├─ Estado: DOCUMENTOS_PENDIENTE_VERIFICACIÓN
│
├─ [2] Admin Verifica (MISMO verificador que beneficiarios)
│   └─ Puede filtrar: origen_solicitud = 'admin_caso_a' (para reportes)
│   └─ Mismo proceso de validación
│   └─ Aprueba/rechaza documentos
│
├─ [3] Directivo Firma (MISMO proceso que todos)
│   └─ Firma digital usando folio + clave privada
│   └─ Presupuesto se asigna
│   └─ Solicitud → APROBADA
│
└─ [4] Beneficiario Recibe Notificación
    └─ Puede consultarla permanentemente con folio+clave
```

---

## Diferencias con Carga Fría

| Aspecto | Carga Fría | Caso A |
|--------|-----------|---------|
| **Beneficiario presente** | ❌ No | ✓ Sí |
| **Documentos físicos** | ❌ Entrega después | ✓ En mano |
| **Generación folio** | Admin crea + notifica | Admin crea + imprime ticket |
| **Clave privada** | ❌ No | ✓ Sí (folio+clave) |
| **Acceso público Momento 3** | ❌ No | ✓ Sí (sin autenticación) |
| **Cadena digital** | ❌ No | ✓ Sí (integridad) |

---

## Cambios en BD

### Solicitudes (alteración):

```sql
ALTER TABLE solicitudes ADD (
    origen_solicitud NVARCHAR(50) DEFAULT 'beneficiario',
    -- Valores: 'beneficiario' | 'admin_caso_a' | 'admin_carga_fria'
    creada_por_admin BIT DEFAULT 0,
    admin_creador INT FK -- Admin que presencialmente registró
);
```

### Documentos_Expediente (alteraciones):

```sql
ALTER TABLE documentos_expediente ADD (
    origen_carga NVARCHAR(50),              -- 'beneficiario'|'admin_escaneo_presencial'
    cargado_por INT FK,                     -- Usuario que cargó
    marca_agua_aplicada BIT,                -- ¿Watermark aplicado?
    qr_seguimiento NVARCHAR(510),           -- QR data/path
    hash_documento VARCHAR(64),             -- SHA256(document)
    hash_anterior VARCHAR(64),              -- Para cadena digital
    firma_admin NVARCHAR(255),              -- HMAC-SHA256 signature
    fecha_carga DATETIME
);
```

### Nuevas tablas:

```sql
-- 1. Claves de seguimiento privadas (Momento 1)
CREATE TABLE claves_seguimiento_privadas (
    id_clave INT PRIMARY KEY IDENTITY,
    folio NVARCHAR(50) UNIQUE,
    clave_alfanumerica NVARCHAR(20) UNIQUE,
    hash_clave VARCHAR(64),
    beneficiario_id INT FK,
    fecha_creacion DATETIME DEFAULT GETDATE(),
    intentos_fallidos INT DEFAULT 0,
    bloqueada BIT DEFAULT 0,
    INDEX idx_folio (folio),
    INDEX idx_beneficiario (beneficiario_id)
);

-- 2. Cadena digital (integridad)
CREATE TABLE cadena_digital_documentos (
    id_cadena INT PRIMARY KEY IDENTITY,
    fk_id_documento INT FK,
    folio NVARCHAR(50),
    hash_actual VARCHAR(64),
    hash_anterior VARCHAR(64),
    firma_hmac NVARCHAR(255),
    admin_creador INT FK,
    timestamp_creacion DATETIME DEFAULT GETDATE()
);

-- 3. Auditoría de carga
CREATE TABLE auditorias_carga_material (
    id_auditoria INT PRIMARY KEY IDENTITY,
    folio NVARCHAR(50),
    evento NVARCHAR(50),
    admin_id INT FK,
    cantidad_docs INT,
    fecha_evento DATETIME DEFAULT GETDATE(),
    ip_admin NVARCHAR(45),
    navegador_agente NVARCHAR(255),
    detalles_evento NVARCHAR(MAX)  -- JSON
);
```

---

## Seguridad

### Folio + Clave:
- ✅ SHA256 hash (no reversible)
- ✅ Intenta con hash_equals() (previene timing attacks)
- ✅ Integridad: hash = SHA256(folio + clave + app_key)
- ✅ Bloqueo: 5 intentos fallidos = cuenta bloqueada

### Documentos:
- ✅ Hash SHA256 (verifiable integrity)
- ✅ Cadena digital (hash anterior → hash actual)
- ✅ Firma HMAC-SHA256 (immutable, solo con encryption_key)
- ✅ Watermark automático (auditoría visual)

### Auditoría:
- ✅ IP + navegador + timestamp + admin_id
- ✅ Evento registrado en auditorias_carga_material
- ✅ Cumplimiento LGPDP

---

## Rutas Implementadas

```php
// MOMENTO 3: Pública (sin autenticación)
GET   /consulta-privada                           → mostrar_form_folio_clave
POST  /verificar-acceso-privado                   → verificarAcceso()
GET   /documentos-privados/{folio}                → mostrarDocumentosPrivados()

// MOMENTOS 1+2: Admin solamente
GET   /admin/caso-a/momento-uno                   → momentoUno() [form]
POST  /admin/caso-a/momento-uno/guardar           → guardarMomentoUno() [crear]
GET   /admin/caso-a/momento-dos                   → momentoDos() [form]
POST  /admin/caso-a/momento-dos/cargar            → cargarDocumentoMomentoDos() [upload]
POST  /admin/caso-a/momento-dos/confirmar         → confirmarCargaMomentoDos() [resumen]

// FLUJO ORDINARIO (Momento 3 real - verificación)
GET   /admin/verificar-documentos                 → DocumentVerificationController
  └─ Filtra origen_solicitud si es Caso A
  └─ MISMA interfaz que beneficiarios
```

---

## Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `app/Http/Controllers/CasoAController.php` | ✅ Refactorizado para fusión |
| `app/Services/CasoADocumentService.php` | ✅ Crea Solicitud ordinaria |
| `database/migrations/2026_04_18_...php` | ✅ Nuevas tablas + alteraciones |
| `resources/views/admin/caso-a/*.blade.php` | ✅ 5 vistas (admin + público) |
| `routes/web.php` | ✅ 11 rutas registradas |
| `METODOLOGIA_AVANCES_Y_PENDIENTES.md` | ✅ Documentación actualizada |

---

## Próximos Pasos

1. **✓ Arquitectura diseñada** (completado)
2. **✓ Código refactorizado** (completado)
3. **⏳ Ejecutar migración:** `php artisan migrate --path=2026_04_18_...`
4. **⏳ Testing end-to-end:**
   - Momento 1: Admin crea presencial
   - Momento 2: Admin escanea
   - Verificador ordinario: Valida (filtra por origen_solicitud)
   - Directivo: Firma (MISMO proceso)
   - Momento 3: Beneficiario consulta (folio+clave)
5. **⏳ Training admin** (2 horas)

---

## Beneficios de la Fusión

| Beneficio | Impacto |
|-----------|--------|
| **DRY (No repetir código)** | Mantenimiento más fácil |
| **UN verificador** | Testing centralizado |
| **Escalable** | Agregar nuevos orígenes (ej: carga_fria) es fácil |
| **Performante** | Menos queries (mismo verificador) |
| **Auditable** | origen_solicitud marca trazabilidad |
| **Cumplimiento LGPDP** | Auditoría completa por origen |

---

**Decisión Confirmada:** ✨ FUSIÓN CON FLUJO ORDINARIO = ÓPTIMA

User sugirió: "¿Es más óptimo la fusión?" → Respuesta: **SÍ** ✅
