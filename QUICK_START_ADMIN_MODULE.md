# ⚡ QUICK START - Administrative Module

**Status**: ✅ Ready to Deploy

## 1. Run the Migration

```bash
php artisan migrate
```

**Output should show**:
```
2026_03_26_add_admin_verification_to_documentos ... 1s
```

## 2. Configure Environment

Edit `.env`:
```env
ENCRYPTION_KEY_QR=use-your-app-key-or-custom-256-char-key
```

## 3. Test the Module

### Option A: Via Browser
```
URL: http://localhost:8000/admin/solicitudes
Username: admin user (must have role 1, 2, or 3)
```

You should see:
- ✅ List of pending solicitations
- ✅ Ability to filter by benefit type
- ✅ Statistics showing pending/accepted/rejected counts
- ✅ Click any solicitation → detail view

### Option B: Via Artisan Commands

```bash
# Test models load
php artisan tinker
> App\Models\Documento::count()
> App\Models\Solicitud::count()

# Check routes
php artisan route:list | grep admin
```

## 4. Verify Implementation

**Check Files Exist**:
```bash
# Models
ls app/Models/{Documento,Solicitud,TipoDocumento}.php

# Service  
ls app/Services/AdministrativeVerificationService.php

# Controller
ls app/Http/Controllers/DocumentVerificationController.php

# Views
ls resources/views/admin/solicitudes/{index,show}.blade.php
```

**All should exist** ✅

## 5. Test Verification Workflow

1. Go to `/admin/solicitudes`
2. Click a solicitation with documents
3. Try to **Aceptar** (Accept) a document
   - Should generate `verification_token`
   - Token should be 64 characters (SHA256 hash)
4. Try to **Rechazar** (Reject) a document
   - Should require `observations` text
   - Should prevent submission if empty
5. Visit `/validacion/{token}` with accepted document's token
   - Should show document info
   - Should NOT show file content (metadata only)

## 6. Common URLs

| URL | Access Level | Purpose |
|-----|--------------|---------|
| `/admin/solicitudes` | Admin | List pending solicitations |
| `/admin/solicitudes/1000` | Admin | Detail view of folio 1000 |
| `/admin/documentos/5/view` | Admin | View document #5 |
| `/validacion/abc123...` | Public | Validate QR code |

## ✅ Troubleshooting

**403 Forbidden Error**?
- Check user has admin role (1, 2, or 3)
- Verify Personal table has correct role

**404 Not Found?**
- Run `php artisan route:clear`
- Verify route group in `routes/web.php`

**No documents showing?**
- Check `Documentos_Expediente` table is not empty
- Verify `admin_status` field exists (after migration)

## 📞 Need Help?

See detailed guides:
- `ADMINISTRATIVE_MODULE_GUIDE.md` - Full operational guide
- `ADMINISTRATIVE_IMPLEMENTATION_SUMMARY.md` - Technical overview
- `.github/agents/administrative.agent.md` - Development persona

---

**Everything is ready**. Just run `php artisan migrate` and start using! 🚀
