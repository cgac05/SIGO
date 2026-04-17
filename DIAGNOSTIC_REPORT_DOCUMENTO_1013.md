# Reporte de Investigación: Documento Faltante Folio 1013

## Resumen Ejecutivo

Se investigó por qué el documento de la solicitud folio **1013** (ID documento 11) no se podía visualizar después de la aprobación, aunque se reportaba que era visible antes.

**HALLAZGO**: El archivo físicamente **NO existe** en el almacenamiento del servidor, aunque el registro en BD indicaba:
- `origen_archivo`: 'google_drive' (INCORRECTO)
- `google_file_id`: NULL (CONFLICTO)
- `ruta_archivo`: 'solicitudes/1rKeeN6Iw3jSO59grKpYY8h8vIiMZYKbiFOOn9hg.pdf'

## Investigación Realizada

### 1. Verificación de Rutas
Se verificaron 5 ubicaciones estándar donde podría residir el archivo:
```
❌ C:\xampp\htdocs\SIGO\storage\app/public/solicitudes/1rKeeN6Iw3jSO59grKpYY8h8vIiMZYKbiFOOn9hg.pdf
❌ storage_path('app/public/') + ruta
❌ public_path('storage/') + ruta
❌ Storage::disk('public')->exists($ruta)
❌ Búsqueda glob: solicitudes/1rKeeN6I*
```

### 2. Verificación en Directorio Completo
Se listó el directorio `/storage/app/public/solicitudes/` y se encontraron 15 archivos PDF/PNG de otros documentos, pero el archivo del folio 1013 no está presente.

### 3. Análisis de Metadatos
La BD tiene conflictive flags:
- `origen_archivo = 'google_drive'` → Indica que es archivo de Google Drive
- `google_file_id = NULL` → Pero NO tiene ID de Google Drive
- `ruta_archivo = 'solicitudes/...'` → Pero el archivo no está en storage local

**Conclusión**: Error de metadata - El documento fue marcado como Google Drive pero en realidad es un archivo local que no existe.

## Causa Raíz Probable

Hay varias hipótesis:

1. **Archivo nunca se guardó correctamente**
   - Upload falló silenciosamente
   - Hash se registró en BD pero el archivo nunca llegó al disco

2. **Archivo fue eliminado después de aprobación**
   - Algún proceso en el flujo de aprobación eliminó el archivo
   - La BD fue actualizada con `origen_archivo='google_drive'` incorrectamente

3. **Limpieza de directorio de pruebas**
   - El directorio de solicitudes fue limpiado/reiniciado
   - Los registros en BD permanecieron

## Acciones Tomadas

### 1. Metadata Corregida en BD
```sql
UPDATE Documentos_Expediente 
SET origen_archivo = 'local', 
    google_file_id = NULL 
WHERE fk_folio = 1013 AND id_doc = 11
```

**Status**: ✅ Completado

### 2. Mecanismos de Detección Mejorados
Se actualizó el código de la aplicación para manejar mejor estos conflictos:

#### `Documento.php` - Modelos mejorados
```php
isLocal()    // Detecta: origen='local' OR (ruta + NO google_id + NO google_path)
isFromDrive() // Detecta: origen='drive'/'google_drive' + google_id OR solo google_id
```

#### `DocumentVerificationController.php` - Validación de conflictos
- Detecta cuando `origen='google_drive'` pero `google_file_id=NULL`
- Intenta buscar como local
- Si encuentra, actualiza BD automáticamente
- Registra en logs para auditoría

#### `DocumentController.php` - Múltiples fallbacks
- Verifica 3 ubicaciones de almacenamiento
- Detecta patrones de Google Drive ID
- Intenta diferentes resoluciones de path

## Recomendaciones

### Inmediatas
1. ✅ **Metadata ya fue corregida** - Documento ahora marcado como 'local'
2. **El usuario debe re-cargar el documento** si necesita que esté disponible

### A Corto Plazo
1. Revisar logs de aprobación para ver si hay proceso que elimina archivos
2. Verificar si hay otros documentos con el mismo conflicto:
   ```sql
   SELECT COUNT(*) FROM Documentos_Expediente 
   WHERE origen_archivo = 'google_drive' AND google_file_id IS NULL
   ```
3. Si hay más, aplicar corrección en batch

### A Largo Plazo
1. Cambiar proceso de aprobación para NO eliminar archivos (solo cambiar estado)
2. Agregar validación en upload para confirmar que el archivo se guardó
3. Implementar validación de integridad en BD:
   - Si `origen='google_drive'` → `google_file_id` debe ser NOT NULL
   - Si `origen='local'` → la ruta debe existir en storage

## Nuevas Defensas Implementadas

### En Tiempo de Ejecución
- DocumentVerificationController ahora detecta y corrige conflictos automáticamente
- DocumentController intenta múltiples estrategias antes de fallar
- Logging detallado para auditoría

### En Comandos Artisan
```bash
# Diagnosticar documento específico
php artisan fix:documento-metadata --folio=1013 --id=11

# Corregir metadata en BD
php artisan fix:documento-metadata-sql --folio=1013 --id=11
```

## Conclusión

El documento de folio 1013 está **irrecuperablemente perdido** en la sesión actual (archivo no existe en disk). Sin embargo:

1. ✅ La metadata en BD ha sido corregida
2. ✅ El código ahora es más robusto para detectar estos problemas
3. ✅ Se han reducido las posibilidades de futuros problemas similares

El usuario debe re-cargar el documento si desea que esté disponible nuevamente.

---

**Fecha de Reporte**: 2026-04-16
**Solicitado por**: Usuario (Debugging folio 1013)
**Estado Final**: DIAGNÓSTICO COMPLETADO + CORRECCIONES APLICADAS
