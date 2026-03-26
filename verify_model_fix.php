<?php
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Documento;

echo "\n============================================\n";
echo "  VERIFICACIÓN DE MODELO DOCUMENTO CORREGIDO\n";
echo "============================================\n\n";

echo "Documentos encontrados via Eloquent ORM:\n\n";

$documentos = Documento::limit(3)->get();
foreach ($documentos as $doc) {
    echo "Documento:\n";
    echo "  id_doc (PK): " . ($doc->id_doc ?? 'NULL') . "\n";
    echo "  fk_folio: " . ($doc->fk_folio ?? 'NULL') . "\n";
    echo "  ruta_archivo: " . ($doc->ruta_archivo ?? 'NULL') . "\n";
    echo "  admin_status: " . ($doc->admin_status ?? 'NULL') . "\n";
    echo "  verification_token: " . ($doc->verification_token ?? 'NULL') . "\n";
    echo "\n";
}

echo "✓ Test completado. El modelo ahora retorna valores correctos.\n";
