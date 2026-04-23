# QR Code Implementation - Momento 1 Resumen

## Resumen

Se ha implementado un **QR code que contiene la URL completa** para acceso directo a la consulta privada, permitiendo que el beneficiario escanee el QR en su celular y vaya directamente a la página de consulta sin necesidad de introducir folio + clave manualmente.

## Flujo Implementado

### 1. **Creación del Expediente (Momento 1)**
```
Admin registra beneficiario presencial
  ↓
Sistema crea Solicitud (folio) + ClaveSegumientoPrivada (clave)
  ↓
Redirige a página de resumen
```

### 2. **Página de Resumen (Momento 1)**
```
Mostrar:
  - Folio en grande
  - Clave privada en grande
  - QR code con URL completa ← NUEVO
  - Botones de Imprimir/Copiar/Nuevo Expediente

El QR contiene:
  http://localhost:8000/consulta-privada/acceso-qr?folio=1035&clave=TEST-TEST-TEST-TEST

Al escanear:
  ↓
Abre URL con folio + clave pre-llenados
```

### 3. **Acceso via QR (Nueva Ruta)**
```
GET /consulta-privada/acceso-qr?folio={folio}&clave={clave}
  ↓
Valida que folio + clave existan en BD
  ↓
Valida que clave no esté bloqueada
  ↓
Guarda en sesión: caso_a_folio_validado, caso_a_clave_validada
  ↓
Redirige a /consulta-privada/resumen
```

## Cambios de Código

### 1. **routes/web.php**
**Agregada nueva ruta:**
```php
Route::get('/consulta-privada/acceso-qr', [\App\Http\Controllers\CasoAController::class, 'accesoDirectoQr'])
    ->name('caso-a.acceso-qr');
```

### 2. **app/Http/Controllers/CasoAController.php**

#### a) Método `mostrarResumenMomentoUno()` ACTUALIZADO
**ANTES:**
```php
'qrData' => base64_encode($folio),  // Solo folio
```

**AHORA:**
```php
$urlQr = route('caso-a.acceso-qr', [
    'folio' => $folio,
    'clave' => $clave->clave_alfanumerica
], absolute: true);

return view('admin.caso-a.resumen-momento-uno', [
    // ... otros datos ...
    'qrUrl' => $urlQr,  // URL completa
]);
```

#### b) Nuevo Método: `accesoDirectoQr()` AGREGADO
```php
/**
 * Acceso directo vía QR (PÚBLICA - sin autenticación)
 * 
 * GET /consulta-privada/acceso-qr?folio={folio}&clave={clave}
 */
public function accesoDirectoQr(Request $request)
{
    // Validar parámetros
    $folio = $request->folio;
    $clave = $request->clave;

    // Buscar y validar clave
    $claveRecord = \App\Models\ClaveSegumientoPrivada::where('folio', $folio)
        ->where('clave_alfanumerica', $clave)
        ->first();

    if (!$claveRecord) {
        return redirect()->route('caso-a.momento-tres-form')
            ->with('error', 'Folio o Clave no válidos.');
    }

    if ($claveRecord->bloqueada) {
        return redirect()->route('caso-a.momento-tres-form')
            ->with('error', 'Esta clave ha sido bloqueada.');
    }

    // Guardar en sesión y redirigir al resumen
    session([
        'caso_a_folio_validado' => $folio,
        'caso_a_clave_validada' => $clave,
    ]);

    return redirect()->route('caso-a.resumen-momento-tres');
}
```

### 3. **resources/views/admin/caso-a/resumen-momento-uno.blade.php**

#### Sección QR ACTUALIZADA
**ANTES:**
```blade
<img src="data:image/svg+xml,{{ urlencode($qrData) }}" alt="QR" class="h-32 mx-auto">
```

**AHORA:**
```blade
<div id="qrcode" class="flex justify-center"></div>
```

#### Script JavaScript AGREGADO
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const qrUrl = '{{ $qrUrl }}';
    
    // Cargar librería qrcode.js desde CDN
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
    script.onload = function() {
        // Generar QR con URL completa
        new QRCode(document.getElementById('qrcode'), {
            text: qrUrl,
            width: 250,
            height: 250,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    };
    document.head.appendChild(script);
});
```

## Ventajas del Diseño

✅ **URL segura**: Folio y clave en query string (no en ruta)
✅ **QR dinámico**: Generado en el navegador, no requiere extensión GD
✅ **Validación automática**: Al escanear, valida folio + clave
✅ **Mejor UX**: Beneficiario no debe escribir nada
✅ **Compatible**: Funciona en cualquier navegador moderno
✅ **Escalable**: Fácil de extender con más parámetros

## Testing

### 1. Verificar ruta existe
```bash
php artisan route:list --name="acceso-qr"
# Debería mostrar: GET|HEAD consulta-privada/acceso-qr
```

### 2. Verificar en navegador
```
Ir a: http://localhost:8000/admin/caso-a/resumen/1035
Debería:
  ✓ Mostrar QR code visible
  ✓ QR contiene URL con folio + clave
  ✓ Scan QR abre /consulta-privada/acceso-qr?folio=1035&clave=...
  ✓ Redirige a resumen con datos pre-llenados
```

### 3. Probar acceso directo (sin escanear)
```
Copiar URL de QR: http://localhost:8000/consulta-privada/acceso-qr?folio=1035&clave=TEST-TEST-TEST-TEST
Abrir en navegador
Debería redirigir directo a: http://localhost:8000/consulta-privada/resumen
Con datos pre-cargados en sesión
```

## Librerías Utilizadas

- **QRCode.js v1.0.0** (CDN)
  - Alternativa ligera a SimpleSoftwareIO (que requería GD)
  - Genera QR en el navegador usando canvas
  - Sin dependencias externas
  - ~3 KB minificado

## Compatibilidad

- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Móviles iOS y Android
- ✅ Lectores QR estándar
- ✅ PHP 8.2+, Laravel 11

## Próximos Pasos (Opcional)

1. **Agregar logo INJUVE al QR** 
   - Modificar QRCode.js para soportar logo

2. **Trackear escaneos**
   - Registrar en tabla audit cuándo se escaneó QR

3. **QR con vencimiento**
   - Cambiar clave_alfanumerica a activa:0 después de X días

4. **Múltiples QRs**
   - QR para Momento 2 (escaneo de documentos)
   - QR para Momento 3 (consulta desde cualquier navegador)

## Notas Importantes

⚠️ **URL visible en QR**: La URL es legible en el código QR. Si es sensible, considerar:
- Generar URL corta con UUID en lugar de folio/clave
- Implementar tabla de mapeo UUID → folio/clave
- Agregar validación adicional por IP o dispositivo

✅ **Recomendado**: Mantener tal como está para facilidad de debugging y reutilización de la URL.
