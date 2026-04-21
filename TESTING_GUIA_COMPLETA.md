# 🧪 Guía de Testing - Caso A Completado ✅

**Fecha:** 18 Abril 2026  
**Estado:** ✅ Migración ejecutada exitosamente  
**Base de datos:** BD_SIGO (SQL Server local - Windows Auth)  

---

## ✅ Estado Actual

### Tablas Creadas
- ✅ `claves_seguimiento_privadas` - Claves privadas de acceso Momento 3
- ✅ `cadena_digital_documentos` - Integridad de documentos
- ✅ `auditorias_carga_material` - Auditoría de escaneos
- ✅ `politicas_retencion_documento` - Retención LGPDP

### Campos Agregados a `documentos_expediente`
- ✅ `origen_carga` - Marca origen (beneficiario | admin_escaneo_presencial)
- ✅ `cargado_por` - ID del usuario que cargó
- ✅ `hash_documento` - SHA256 del documento
- ✅ `hash_anterior` - Para cadena digital
- ✅ `firma_admin` - HMAC signature
- ✅ `qr_seguimiento` - QR code data
- ✅ `marca_agua_aplicada` - Flag de watermark
- ✅ `estado_verificacion` - Estado del documento

### Configuración
- ✅ `.env` restaurado a `SigoWebAppUser` (restricciones SQL)
- ✅ App PHP ejecutándose con usuario limitado (seguridad)
- ✅ Rutas registradas (11 nuevas rutas)
- ✅ Vistas producción-ready

---

## 🚀 Iniciando Testing

### Paso 1: Iniciar servidor PHP

```powershell
cd c:\xampp\htdocs\SIGO
php artisan serve
```

**Esperado:**
```
INFO  Server running on [http://127.0.0.1:8000]
```

Deja esto corriendo en otra terminal.

---

### Paso 2: Acceder a los Paneles

**Momento 1 - Crear Presencial:**
```
http://localhost:8000/admin/caso-a/momento-uno
```
✅ Requiere: Login como Admin
✅ Funcionalidad: Crear expediente presencial + generar folio/clave

**Momento 2 - Escanear Documentos:**
```
http://localhost:8000/admin/caso-a/momento-dos
```
✅ Requiere: Login como Admin
✅ Funcionalidad: Escanear documentos + watermark automático

**Verificación Ordinaria:**
```
http://localhost:8000/admin/verificar-documentos
```
✅ Requiere: Login como Admin
✅ Nota: MISMA interfaz para todos (Caso A se diferencia por `origen_solicitud`)

**Consulta Privada (Público):**
```
http://localhost:8000/consulta-privada
```
❌ NO requiere login
✅ Ingresa: Folio + Clave privada
✅ Funcionalidad: Ver documentos sin autenticación

---

## 🧪 Escenario de Testing Completo

### Test 1: Crear Solicitud Presencial (Momento 1) - 5 mins

```
PASOS:
1. Accede: http://localhost:8000/admin/caso-a/momento-uno
2. Auth: Inicia sesión como admin (rol 1-2)
3. Busca: Beneficiario (usa AJAX search)
4. Selecciona: 
   └─ Apoyo: TEP u otro activo
   └─ Documento: C-1234567890
5. Checks: ☑ Cédula ☑ RFC ☑ Comprobante
6. Click: [GUARDAR EXPEDIENTE]

RESULTADOS ESPERADOS:
✓ Folio generado (ej: 001-2026-TEP)
✓ Clave privada generada (16 caracteres)
✓ QR code visible
✓ Botón [IMPRIMIR TICKET]
✓ Email simulado a beneficiario
✓ Solicitud creada con:
  - origen_solicitud = 'admin_caso_a'
  - estado = 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN'
  - creada_por_admin = 1
```

**Guardar para siguiente test:**
- Folio: _______________
- Clave: _______________

---

### Test 2: Escanear Documentos (Momento 2) - 5 mins

```
PREPARACIÓN:
- Tener PDFs/JPGs de prueba en escritorio
- Tamaño: <5MB cada uno
- Formatos: PDF, JPG, PNG

PASOS:
1. Accede: http://localhost:8000/admin/caso-a/momento-dos
2. Auth: Ya logueado (sesión activa)
3. Ingresa: Folio del Test 1 (ej: 001-2026-TEP)
4. Upload: 
   a. Drag-drop archivo 1 sobre área azul
   b. Validación automática
   c. Click: [✓ ACEPTAR]
   d. Repeat para archivo 2 y 3
5. Click: [CONFIRMAR CARGA]

RESULTADOS ESPERADOS:
✓ Documentos guardados en BD
✓ Hash SHA256 calculado
✓ Watermark: "INJUVE · 001-2026-TEP · [DATE]" visible
✓ QR code generado
✓ HMAC signature creado
✓ Auditoría registrada:
  - evento = 'caso_a_momento_2_carga_confirmada'
  - admin_id = tu usuario
  - IP registrada
  - Navegador registrado
✓ Email a beneficiario: "Documentos recibidos"
✓ Resumen mostrado
  - 3 documentos confirmados
  - Status: DOCUMENTOS_PENDIENTE_VERIFICACIÓN
  - Link a verificador ordinario
```

---

### Test 3: Verificación en Panel Ordinario - 5 mins

```
PASOS:
1. Accede: http://localhost:8000/admin/verificar-documentos
2. Filter (opcional): Busca por folio
3. Find: 001-2026-TEP
4. Open: Expediente
5. For cada documento:
   a. Review: Contenido visible
   b. Check: Watermark presente? ✓
   c. Check: QR present? ✓
   d. Integridad: SHA256 válido? ✓
   e. Action: [✓ APROBAR] o [✗ RECHAZAR]
6. Done: Estado cambia a DOCUMENTOS_VERIFICADOS

RESULTADOS ESPERADOS:
✓ MISMA interfaz que para beneficiarios (fusión exitosa)
✓ Validación funciona
✓ Admin puede aprobar/rechazar
✓ Auditoría registra acción
✓ Estado de solicitud cambia
✓ origen_solicitud='admin_caso_a' se mantiene (para reportes)
```

---

### Test 4: Acceso Público Momento 3 - 5 mins

```
PREPARACIÓN:
- Abrir navegador PRIVADO/INCÓGNITO (simular sin login)
- O: Diferente navegador
- O: Limpiar cookies

PASOS:
1. Accede: http://localhost:8000/consulta-privada
   └─ (Sin autenticación - debe cargar)
2. Ingresa: 
   ├─ Folio: 001-2026-TEP (del Test 1)
   └─ Clave: KX7M-9P2W-5LQ8 (del Test 1)
3. Click: [VERIFICAR ACCESO]
4. Dashboard: Si acceso exitoso
   ├─ Status: "Documentos en verificación"
   ├─ Timeline: Admin escaneó → Verificador aprobó → Directivo va a firmar
   ├─ Documentos:
   │  ├─ ✅ Cédula (Verificado) [Ver QR] [Descargar]
   │  ├─ ✅ RFC (Verificado) [Ver QR] [Descargar]
   │  └─ ✅ Comprobante (Verificado) [Ver QR] [Descargar]
   └─ [Verificar Cadena Digital]

RESULTADOS ESPERADOS:
✓ Acceso EXITOSO sin autenticación
✓ Documentos visibles
✓ QR verificables
✓ Descargas funcionan
✓ Hash integridad válido
✓ Cadena digital se verifica
✓ Status correcto

CASOS DE ERROR A PROBAR:
✗ Folio incorrecto → "Folio no encontrado"
✗ Clave incorrecta → "Clave inválida"
✗ Clave correcta 5 veces fallida → "Cuenta bloqueada"
```

---

## 📋 Verificación de Base de Datos

### Después de cada test, verificar en SQL Server:

```sql
-- 1. Verificar Solicitud Caso A creada
SELECT TOP 1 
    folio_institucional,
    origen_solicitud,
    creada_por_admin,
    admin_creador,
    estado_solicitud
FROM solicitudes 
WHERE origen_solicitud = 'admin_caso_a'
ORDER BY fecha_cambio_estado DESC;

-- Expected: 
-- folio_institucional: 001-2026-TEP (or similar)
-- origen_solicitud: admin_caso_a
-- creada_por_admin: 1
-- admin_creador: [your_user_id]
-- estado_solicitud: DOCUMENTOS_PENDIENTE_VERIFICACIÓN

-- 2. Verificar Clave Privada creada
SELECT TOP 1
    folio,
    clave_alfanumerica,
    beneficiario_id,
    intentos_fallidos,
    bloqueada
FROM claves_seguimiento_privadas
ORDER BY fecha_creacion DESC;

-- Expected:
-- folio: 001-2026-TEP
-- clave_alfanumerica: [16-char random]
-- beneficiario_id: [user_id]
-- intentos_fallidos: 0
-- bloqueada: 0

-- 3. Verificar Documentos cargados
SELECT TOP 3
    id_doc,
    fk_folio,
    origen_carga,
    cargado_por,
    hash_documento,
    marca_agua_aplicada,
    estado_verificacion
FROM documentos_expediente
WHERE origen_carga = 'admin_escaneo_presencial'
ORDER BY fecha_carga DESC;

-- Expected:
-- origen_carga: admin_escaneo_presencial
-- cargado_por: [admin_id]
-- hash_documento: [64-char SHA256]
-- marca_agua_aplicada: 1
-- estado_verificacion: PENDIENTE (or APROBADO after verification)

-- 4. Verificar Cadena Digital
SELECT TOP 3
    id_cadena,
    folio,
    hash_actual,
    hash_anterior,
    firma_hmac
FROM cadena_digital_documentos
ORDER BY timestamp_creacion DESC;

-- Expected:
-- folio: 001-2026-TEP
-- hash_actual: [64-char]
-- hash_anterior: [64-char or NULL para first]
-- firma_hmac: [HMAC signature]

-- 5. Verificar Auditoría
SELECT TOP 5
    folio,
    evento,
    admin_id,
    cantidad_docs,
    fecha_evento,
    ip_admin
FROM auditorias_carga_material
ORDER BY fecha_evento DESC;

-- Expected:
-- evento: caso_a_momento_1_presencial or caso_a_momento_2_carga_confirmada
-- admin_id: [your_id]
-- IP_admin: [your_computer_ip]
```

---

## 🐛 Troubleshooting

| Problema | Solución |
|----------|----------|
| "Beneficiario no encontrado" | Verifica cédula exacta en sistema |
| "Apoyo no disponible" | Selecciona apoyo con estado RECEPCIÓN o ACTIVO |
| "Folio ya existe" | Espera 1 min e intenta de nuevo |
| "Error al cargar archivo" | Verifica tamaño <5MB y formato válido |
| "Hash mismatch" | Verifica que SHA256 esté correctamente implementado |
| "Clave inválida (cuenta bloqueada)" | Normal tras 5 intentos, admin debe desbloquear en BD |
| "Watermark no visible" | Verifica que servidor tenga permisos de lectura/escritura |
| "Email no recibido" | Verifica que MAIL está configurado en .env |

---

## ✅ Checklist de Testing

### Funcionalidad
- [ ] Test 1: Crear presencial exitoso
- [ ] Test 2: Escanear documentos exitoso
- [ ] Test 3: Verificación ordinaria exitosa
- [ ] Test 4: Acceso público exitoso (sin login)

### Datos
- [ ] Folio generado correctamente
- [ ] Clave privada generada (16 chars)
- [ ] Hash SHA256 calculado
- [ ] Watermark aplicado
- [ ] QR code presente
- [ ] HMAC firma creada
- [ ] Auditoría registrada

### BD
- [ ] Tabla `claves_seguimiento_privadas` con datos
- [ ] Tabla `cadena_digital_documentos` con datos
- [ ] Tabla `auditorias_carga_material` con eventos
- [ ] Campo `origen_carga` en documentos_expediente
- [ ] Campo `hash_documento` en documentos_expediente
- [ ] Campo `origen_solicitud` en solicitudes

### Seguridad
- [ ] Folio + clave válida = acceso ✓
- [ ] Folio inválida = error ✓
- [ ] Clave inválida (x5) = bloqueo ✓
- [ ] IP y navegador registrados ✓
- [ ] Sin autenticación en Momento 3 ✓

---

## 🚀 Próximos Pasos (Después del Testing)

1. **Firma Digital** - Directivo firma Caso A solicitudes
2. **Presupuesto** - Asignación automática
3. **Training Admin** - 2 horas sobre nuevos panels
4. **Production Deploy** - Migración ya preparada para Azure

---

## 📞 Soporte

Si encuentras algún problema:

1. Revisa logs: `storage/logs/laravel.log`
2. Verifica BD en SQL Server Management Studio
3. Revisa `.env` tenga credenciales correctas
4. Limpia cache: `php artisan cache:clear`

---

**Status:** ✅ LISTO PARA TESTING COMPLETO
