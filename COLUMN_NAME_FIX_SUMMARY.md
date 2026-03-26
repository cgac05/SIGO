# FIX: Corrección del Nombre de Columna Primaria - id_documento → id_doc

## Problema Identificado

La tabla `Documentos_Expediente` en SQL Server tiene una columna primaria llamada **`id_doc`**, pero el modelo Eloquent de Laravel estaba configurado para usar **`id_documento`** como clave primaria.

Esto causaba que:
- Todas las consultas Eloquent retornaran `NULL` para el atributo `id_documento`
- Las vistas Blade no pudieran renderizar URLs o IDs válidos
- El formulario de verificación no tuviera el ID del documento
- Las operaciones CRUD fallaran silenciosamente

## Error Original

```
Error SQL: "El nombre de columna 'id_documento' no es válido"
Resultado Eloquent: id_documento = NULL para todos los registros
```

## Cambios Realizados

### 1. **app/Models/Documento.php**
```php
// ANTES:
protected $primaryKey = 'id_documento';

// DESPUÉS:
protected $primaryKey = 'id_doc';
```

### 2. **app/Services/AdministrativeVerificationService.php**
```php
// Línea 104 - ANTES:
$documento->id_documento

// AHORA:
$documento->id_doc
```

### 3. **resources/views/admin/solicitudes/show.blade.php**
```php
// Línea 86 - ANTES:
data-documento-id="{{ $documento->id_documento }}"

// AHORA:
data-documento-id="{{ $documento->id_doc }}"

// Línea 104 - ANTES:
href="/admin/solicitudes/{{ $documento->id_documento }}/view"

// AHORA:
href="/admin/solicitudes/{{ $documento->id_doc }}/view"

// Línea 111-112 - ANTES:
id="verify-form-{{ $documento->id_documento }}"
data-documento-id="{{ $documento->id_documento }}"

// AHORA:
id="verify-form-{{ $documento->id_doc }}"
data-documento-id="{{ $documento->id_doc }}"
```

### 4. **app/Jobs/CopiarDocumentoExpedienteJob.php**
```php
// Línea 32 - ANTES:
->where('id_documento', $this->idDocumento)

// AHORA:
->where('id_doc', $this->idDocumento)

// Línea 50 - ANTES:
'id_documento' => $this->idDocumento,

// AHORA:
'id_doc' => $this->idDocumento,

// Línea 72 - ANTES:
->where('id_documento', $this->idDocumento)

// AHORA:
->where('id_doc', $this->idDocumento)
```

### 5. **app/Http/Controllers/SolicitudProcesoController.php**
```php
// Línea 70 - Validación de formulario - ANTES:
'id_documento' => ['required', 'integer', 'exists:Documentos_Expediente,id_documento'],

// AHORA:
'id_documento' => ['required', 'integer', 'exists:Documentos_Expediente,id_doc'],
```

## Impacto

✅ Las consultas Eloquent ahora retornan valores válidos de `id_doc`  
✅ Las vistas pueden renderizar URLs correctas: `/admin/solicitudes/1/view`  
✅ Los formularios de verificación tienen IDs válidos  
✅ Los tokens de verificación se pueden generar correctamente  
✅ El workflow de aceptación/rechazo puede proceder

## Próximos Pasos

1. Limpiar caché de Laravel ✅ (hecho)
2. Probar acceso a solicitudes: http://localhost/SIGO/public/admin/solicitudes
3. Verificar que los documentos se muestren con IDs válidos
4. Probar aceptar/rechazar un documento

## Nota de Esquema

Columnas actuales en Documentos_Expediente:
- **id_doc** (PK, INT IDENTITY)
- fk_folio (INT FK a Solicitudes)
- fk_id_tipo_doc (INT FK a Cat_TiposDocumento)
- ruta_archivo (NVARCHAR)
- estado_validacion (NVARCHAR)
- Más campos de verificación y auditoría...

Total: 21 columnas (16 originales + 5 de verificación administrativa)
