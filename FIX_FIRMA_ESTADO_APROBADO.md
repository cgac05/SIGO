# Fix: Habilitar Firma en Solicitudes Aprobadas

## Problema
Cuando el admin aprobaba todos los documentos de una solicitud (folio 1014), ésta cambiaba a estado "Aprobado" (ID 4), pero **la fase de firma no se habilitaba**. El directivo veía el mensaje "La fase de firma se habilitará cuando se completen las verificaciones" aunque ya estaban aprobadas.

## Causa
El código tenía validaciones que SOLO permitían firmar si el estado era exactamente `DOCUMENTOS_VERIFICADOS` (ID 10), pero:
1. Al aprobar documentos en admin → estado cambiaba a ID 4 (Aprobado)
2. La vista solo mostraba botón de firma si estado era `DOCUMENTOS_VERIFICADOS`
3. El controlador de firma solo aceptaba estado 10

Result: **Solicitud aprobada pero sin poder ser firmada**

## Soluciones Implementadas

### 1. **Vista** - `resources/views/solicitudes/proceso/show.blade.php`

#### Antes (❌ incorrecto)
```blade
@if($estadoActual->nombre_estado === 'DOCUMENTOS_VERIFICADOS')
    <!-- Mostrar formulario de firma -->
@elseif($estadoActual->nombre_estado === 'APROBADA' && $solicitud->cuv)
    <!-- Mostrar CUV ya generado -->
@else
    <!-- Mensaje de espera -->
@endif
```

#### Después (✅ correcto)
```blade
@if($estadoActual->nombre_estado === 'DOCUMENTOS_VERIFICADOS' || $estadoActual->nombre_estado === 'Aprobado')
    <!-- Mostrar formulario de firma -->
```

También se corrigió la comparación de estados a usar nombres reales en BD: `'Aprobado'` en lugar de `'APROBADA'`

### 2. **Controlador** - `app/Http/Controllers/SolicitudProcesoController.php` línea 770

#### Antes (❌ incorrecto)
```php
if ($solicitud->fk_id_estado != 10) {
    return back()->withErrors(['error' => 'No está en estado para firmar']);
}
```

#### Después (✅ correcto)
```php
if (!in_array($solicitud->fk_id_estado, [4, 10])) {
    return back()->withErrors(['error' => 'No está en estado para firmar']);
}
```

### 3. **Nuevo Comando de Verificación**
```bash
php artisan verify:firma-enable {folio}
```

Verifica si una solicitud está lista para firmar y muestra:
- Estado actual
- Si ya tiene CUV
- Si cumple requisitos para firma
- Próximo paso

## Resultado

✅ **Folio 1014 - Verificación**
```
Estado: ID 4 (Aprobado)
CUV: NO GENERADO
✅ LISTA PARA FIRMAR
   El directivo puede hacer clic en 'Firmar y Generar CUV'
```

## Flujo Corregido

```
[ADMIN]
  ↓
Admin aprueba todos documentos
  ↓
Solicitud → Estado 4 (Aprobado)
  ↓
[DIRECTIVO VIEW]
  ↓
Detecta: Estado = "Aprobado" (ID 4) ✅
  ↓
✓ Muestra botón "Firmar y Generar CUV"
  ↓
Directivo ingresa contraseña y firma
  ↓
Genera CUV y completa proceso ✅
```

## Estados Válidos para Firma
- **ID 4**: Aprobado (nuevo - después de aprobación admin)
- **ID 10**: Documentos Verificados (antiguo - flujo legacy)

Ambos permiten que el directivo firme y genere CUV.

## Archivos Modificados
1. `resources/views/solicitudes/proceso/show.blade.php` - Condición de visualización de firma
2. `app/Http/Controllers/SolicitudProcesoController.php` - Validación de estado para firma
3. `app/Console/Commands/VerifyFirmaEnable.php` - Nuevo comando de diagnóstico

---

**Status**: ✅ RESUELTO  
**Solicitud Testing**: Folio 1014  
**Fecha**: 16 Abril 2026
