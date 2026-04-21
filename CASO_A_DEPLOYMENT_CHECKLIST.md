# Caso A: Deployment Checklist ✅

## Pre-Deployment Validation

### ✅ Code Review
- [x] CasoADocumentService refactorizado
- [x] CasoAController actualizado
- [x] Vistas producción-ready
- [x] Migraciones preparadas
- [x] Documentación actualizada

### ✅ Database Changes
```sql
-- Tablas nuevas:
✅ claves_seguimiento_privadas
✅ cadena_digital_documentos
✅ auditorias_carga_material

-- Alteraciones:
✅ solicitudes: +origen_solicitud, +creada_por_admin, +admin_creador
✅ documentos_expediente: +origen_carga, +cargado_por, +marca_agua_aplicada, +qr_seguimiento, +hash_documento, +hash_anterior, +firma_admin, +fecha_carga
```

---

## 🚀 Deployment Steps (in Order)

### Phase 1: Pre-Deployment (Dev/Staging)

#### Step 1: Backup Database
```bash
# SQL Server backup
# Backup COMPLETE database (Nayarit)
BACKUP DATABASE Nayarit 
TO DISK = 'C:\Backup\Nayarit_PreCasoA_2026-04-18.bak';
```

#### Step 2: Review Migration
```bash
# Check migration file
cat database/migrations/2026_04_18_create_caso_a_tables.php

# Expected output:
# - 4 new tables created
# - 3 fields added to solicitudes
# - 8 fields added to documentos_expediente
# - NO estado insertions (fusionado)
```

#### Step 3: Test Migration (Staging Only)
```bash
# Run migration on STAGING database first
php artisan migrate --path=database/migrations/2026_04_18_create_caso_a_tables.php

# Expected: ✓ Migrated 2026_04_18_create_caso_a_tables
```

#### Step 4: Validate Tables
```sql
-- Verify tables created
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME IN (
    'claves_seguimiento_privadas',
    'cadena_digital_documentos', 
    'auditorias_carga_material'
);

-- Expected: 3 rows

-- Verify columns added to solicitudes
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'solicitudes' AND COLUMN_NAME IN (
    'origen_solicitud', 'creada_por_admin', 'admin_creador'
);

-- Expected: 3 rows

-- Verify columns added to documentos_expediente
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'documentos_expediente' AND COLUMN_NAME IN (
    'origen_carga', 'cargado_por', 'marca_agua_aplicada',
    'qr_seguimiento', 'hash_documento', 'hash_anterior', 
    'firma_admin', 'fecha_carga'
);

-- Expected: 8 rows
```

#### Step 5: Test Code (Staging)
```bash
# Run PHPUnit tests (if exist)
php artisan test

# Expected: ✓ All tests pass

# Manual test: Create presential solicitud
# Visit: http://staging.sigo/admin/caso-a/momento-uno
```

---

### Phase 2: Production Deployment

#### Step 6: Deploy Code
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies (if needed)
composer install --no-dev

# 3. Clear cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# 4. Optimize
php artisan optimize
```

#### Step 7: Run Migration (Production)
```bash
# PRODUCTION ONLY: Run migration on PRODUCTION database
# IMPORTANT: This modifies actual data
php artisan migrate --path=database/migrations/2026_04_18_create_caso_a_tables.php

# Expected: ✓ Migrated 2026_04_18_create_caso_a_tables
```

#### Step 8: Verify Production
```sql
-- Verify NEW tables exist in production
SELECT COUNT(*) as table_count FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME IN (
    'claves_seguimiento_privadas',
    'cadena_digital_documentos',
    'auditorias_carga_material'
)

-- Expected: 3

-- Verify NEW columns exist in production
SELECT COUNT(*) as column_count FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME IN ('solicitudes', 'documentos_expediente')
AND COLUMN_NAME IN (
    'origen_solicitud', 'creada_por_admin', 'admin_creador',
    'origen_carga', 'cargado_por', 'marca_agua_aplicada',
    'qr_seguimiento', 'hash_documento', 'hash_anterior',
    'firma_admin', 'fecha_carga'
);

-- Expected: 11 (3 + 8)
```

#### Step 9: Test Production Routes
```bash
# 1. Test Momento 1 (create presencial)
curl -X GET http://sigo.injuve.mx/admin/caso-a/momento-uno

# Expected: ✓ Form page loads (authenticated admin only)

# 2. Test Momento 3 (public access)
curl -X GET http://sigo.injuve.mx/consulta-privada

# Expected: ✓ Public form page loads (no auth required)
```

---

### Phase 3: UAT (User Acceptance Testing)

#### Step 10: Manual E2E Test

**Escenario: Admin presencialmente registra beneficiario**

```
1. MOMENTO 1 - Admin registra presencia
   ├─ Navigate: http://sigo.injuve.mx/admin/caso-a/momento-uno
   ├─ Search: Buscar beneficiario (AJAX search works?)
   ├─ Select: Apoyo activo
   ├─ Enter: Documento identidad (ej: C-12345678)
   ├─ Select: Documentos esperados (Cédula, RFC, etc)
   ├─ Click: [Guardar Expediente]
   └─ Expected: ✓ Folio + Clave generated
              ✓ Ticket impreso
              ✓ Solicitud creada con origen_solicitud='admin_caso_a'
              ✓ Estado = DOCUMENTOS_PENDIENTE_VERIFICACIÓN

2. MOMENTO 2 - Admin escanea documentos
   ├─ Navigate: http://sigo.injuve.mx/admin/caso-a/momento-dos
   ├─ Enter: Folio (ej: 001-2026-TEP)
   ├─ Upload: Documento 1 (drag-drop, <5MB)
   ├─ Upload: Documento 2
   ├─ Click: [Confirmar Carga]
   └─ Expected: ✓ Documentos guardados
              ✓ Hash SHA256 calculado
              ✓ Watermark aplicado
              ✓ Auditoría registrada
              ✓ NO estado cambió (todavía DOCUMENTOS_PENDIENTE_VERIFICACIÓN)

3. VERIFICACIÓN - Admin verifica (ordinario)
   ├─ Navigate: http://sigo.injuve.mx/admin/verificar-documentos
   ├─ Filter: origen_solicitud = 'admin_caso_a' (si existe filtro)
   ├─ Find: Folio 001-2026-TEP
   ├─ Open: Documento 1
   ├─ Validate: Watermark visible?
   ├─ Validate: QR code present?
   ├─ Action: [Aprobar documento] o [Rechazar]
   └─ Expected: ✓ SAME interface como para beneficiarios
              ✓ Validación funciona
              ✓ Estado cambia a DOCUMENTOS_VERIFICADOS

4. FIRMA - Directivo firma
   ├─ Navigate: Panel firma directivo
   ├─ Find: Solicitud Caso A (origen_solicitud='admin_caso_a')
   ├─ Action: [Firmar]
   ├─ Verify: Firma digital aplicada
   ├─ Expected: ✓ SAME process como para beneficiarios
   └─ Result: Estado → APROBADA

5. MOMENTO 3 - Beneficiario consulta (público)
   ├─ Navigate: http://sigo.injuve.mx/consulta-privada
   ├─ Enter: Folio = 001-2026-TEP
   ├─ Enter: Clave = KX7M-9P2W-5LQ8 (del ticket)
   ├─ Click: [Verificar]
   ├─ Dashboard: Ver status, documentos, cadena digital
   └─ Expected: ✓ Acceso sin autenticación
              ✓ Documents visible
              ✓ QR verificable
              ✓ Download disponible
              ✓ Digital chain integrity valida
```

#### Step 11: Admin Training

**Temas a cubrir (2 horas):**
```
1. Momento 1: Cómo crear presencial (15 mins)
   - Panel: /admin/caso-a/momento-uno
   - Campos: beneficiario, apoyo, documento, docs esperados
   - Resultado: Folio + Clave ticket
   - Imprime: Dar al beneficiario

2. Momento 2: Cómo escanear (15 mins)
   - Panel: /admin/caso-a/momento-dos
   - Ingresa folio (o escanea QR)
   - Upload: Documentos
   - Sistema automático: watermark, hash, firma
   - [Confirmar carga]

3. Verificación: MISMO panel ordinario (15 mins)
   - Route: /admin/verificar-documentos
   - Filter: Puede filtrar por origen_solicitud (opcional)
   - MISMA lógica que beneficiarios
   - Aprueba/rechaza documentos

4. Security + LGPDP (15 mins)
   - Folio + clave: Privado del beneficiario
   - NO compartir folio/clave con terceros
   - Auditoría: Cada acción registrada (IP, admin_id)
   - Retención: 90 días post-cierre apoyo

5. Troubleshooting (15 mins)
   - Beneficiario olvidó clave → NO se puede recuperar
   - Hash mismatch → Validar instalación HMAC
   - Watermark no visible → Verificar aplicación en servidor
```

---

### Phase 4: Post-Deployment

#### Step 12: Monitor Logs
```bash
# Watch Laravel logs for errors
tail -f storage/logs/laravel.log | grep -i "caso_a\|error"

# Expected: No errors related to Caso A

# Check auditoría table
SELECT TOP 10 * FROM auditorias_carga_material ORDER BY fecha_evento DESC;

# Expected: ✓ Eventos registrados correctamente
```

#### Step 13: Performance Check
```bash
# Check query performance
DBCC DROPCLEANBUFFERS;  -- Clear cache
SET STATISTICS IO ON;

-- Query claves_seguimiento_privadas index usage
SELECT * FROM claves_seguimiento_privadas WHERE folio = '001-2026-TEP';

-- Expected: ✓ Index seek (not scan)
-- Expected response time: <50ms

SET STATISTICS IO OFF;
```

#### Step 14: Backup Updated DB
```bash
# After successful deployment, backup with new schema
BACKUP DATABASE Nayarit 
TO DISK = 'C:\Backup\Nayarit_PostCasoA_2026-04-18.bak';
```

---

## 🔄 Rollback Plan (If Issues Arise)

### Quick Rollback
```bash
# 1. Revert code
git revert HEAD

# 2. Clear cache
php artisan cache:clear

# 3. Rollback migration
php artisan migrate:rollback --step=1

# 4. Restore database from backup
RESTORE DATABASE Nayarit FROM DISK = 'C:\Backup\Nayarit_PreCasoA_2026-04-18.bak';

# 5. Verify
php artisan tinker
> App\Models\Solicitudes::count()  # Should work normally
```

---

## 📋 Deployment Verification Checklist

After deployment, verify ALL of these:

### Tables
- [ ] claves_seguimiento_privadas exists
- [ ] cadena_digital_documentos exists
- [ ] auditorias_carga_material exists

### Columns
- [ ] solicitudes.origen_solicitud exists
- [ ] solicitudes.creada_por_admin exists
- [ ] solicitudes.admin_creador exists
- [ ] documentos_expediente.origen_carga exists
- [ ] documentos_expediente.hash_documento exists
- [ ] documentos_expediente.firma_admin exists
- [ ] documentos_expediente.qr_seguimiento exists

### Routes
- [ ] GET /admin/caso-a/momento-uno responds 200
- [ ] POST /admin/caso-a/momento-uno/guardar responds 200
- [ ] POST /admin/caso-a/momento-dos/cargar responds 200
- [ ] GET /consulta-privada responds 200 (public)
- [ ] POST /verificar-acceso-privado responds 200 (public)

### Functionality
- [ ] Admin can create presencial solicitud
- [ ] Folio + Clave generated correctly
- [ ] Admin can upload documents Momento 2
- [ ] Documents saved with origin_carga='admin_escaneo_presencial'
- [ ] Admin can verify in ordinary panel
- [ ] Beneficiary can access with folio+clave (no auth)
- [ ] Digital chain integrity validates

---

## 🎯 Success Criteria

✅ All tables exist  
✅ All columns exist  
✅ All routes respond  
✅ E2E test passes  
✅ Admin can complete Momento 1-2  
✅ Verification works in ordinary panel  
✅ Beneficiary can access Momento 3  
✅ Auditoría registra eventos  
✅ No errors in logs  
✅ Performance acceptable (<100ms queries)  

---

## 📞 Support Contacts

If issues arise during deployment:

1. **Database Issues**
   - Check migration logs: `php artisan migrate:status`
   - Review SQL Server error logs

2. **Code Issues**
   - Check Laravel logs: `tail -f storage/logs/laravel.log`
   - Run tests: `php artisan test`

3. **Security Issues**
   - Verify HMAC key in .env
   - Check hash_equals() implementation

---

**Deployment Ready:** ✅ All systems ready for production deployment
