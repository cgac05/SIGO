# QR Code Generation - Phase 2 Implementation Guide

**Status**: Planned - After Phase 1 Completion  
**Dependency**: `simplesoftwareio/simple-qrcode` package

## Overview

Phase 2 will add visual QR code generation capabilities to the administrative verification system. Tokens are already being generated in Phase 1, but Phase 2 will create the actual QR images and PDF receipts.

## Installation

```bash
composer require simplesoftware/simple-qrcode
```

## Implementation Steps

### Step 1: Update Service - Add QR Generation Method

```php
// In app/Services/AdministrativeVerificationService.php

use SimpleSoftwareIO\QrCode\Facades\QrCode;

public function generateQrCode(string $token): string
{
    // Generate QR code that points to validation route
    $validationUrl = route('admin.validacion.publico', ['token' => $token]);
    
    // Generate QR and store temporarily
    $qrImage = QrCode::format('png')
        ->size(300)
        ->margin(2)
        ->generate($validationUrl);
    
    return base64_encode($qrImage);
}

public function generateVerificationReceipt(Documento $documento): \Barryvdh\DomPDF\PDF
{
    // Not yet implemented - requires barryvdh/laravel-dompdf
    // Will generate PDF with QR code, beneficiary info, document details
}
```

### Step 2: Update Views - Display QR Code

```html
<!-- In admin/solicitudes/show.blade.php -->
@if($documento->verification_token)
<div class="mt-4 pt-4 border-t border-slate-200">
    <img src="data:image/png;base64,{{ $service->generateQrCode($documento->verification_token) }}" 
         alt="Código QR" class="w-48 h-48" />
</div>
@endif
```

### Step 3: Update Validation View - Display QR

```html
<!-- In admin/validacion-exitosa.blade.php -->
<div class="mt-4">
    <img src="data:image/png;base64,{{ $qrImage }}" 
         alt="Código QR de Verificación" class="w-64 h-64 mx-auto" />
</div>
```

### Step 4: Add PDF Export (Optional)

```bash
composer require barryvdh/laravel-dompdf
```

Then implement:

```php
public function downloadReceipt(Documento $documento)
{
    $pdf = PDF::loadView('admin.documentos.receipt-pdf', [
        'documento' => $documento,
        'qrCode' => $this->generateQrCode($documento->verification_token),
    ]);
    
    return $pdf->download('acuse-verificacion-' . $documento->id_documento . '.pdf');
}
```

## Testing QR Codes

### Manual Testing

1. Generate a token through verification workflow
2. Open QR code generation endpoint
3. Use mobile phone QR reader to scan
4. Should link to `/validacion/{token}`

### Automated Testing

```php
public function testQrCodeGeneration()
{
    $token = 'abc123...';
    $qrCode = $this->service->generateQrCode($token);
    
    $this->assertNotEmpty($qrCode);
    $this->assertStringContainsString('base64', $qrCode);
}
```

## Configuration

In `config/qrcode.php` (if needed):

```php
return [
    'default' => 'svg',
    'image' => 'png',
    'size' => 300,
    'margin' => 2,
];
```

## Deployment Notes

⚠️ **Important**: QR code generation is image processing intensive
- Consider caching generated QR codes
- Implement rate limiting on QR generation endpoints
- Monitor server resources

## Next Phases

**Phase 3**: Email notifications with QR code attached  
**Phase 4**: Mobile app integration for QR scanning at field offices  
**Phase 5**: Analytics dashboard showing QR scan counts

---

*Estimated Implementation Time*: 2-3 hours development + testing
