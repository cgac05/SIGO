<?php
/**
 * Script para crear la tabla auditoria_folios
 * 
 * Ejecutar desde terminal:
 * php artisan tinker < crear_tabla_auditoria_folios.php
 */

// Verificar si la tabla ya existe
$tablaExiste = \Illuminate\Support\Facades\Schema::hasTable('auditoria_folios');

if ($tablaExiste) {
    echo "\n✅ Tabla 'auditoria_folios' ya existe\n";
    dd('Tabla ya creada');
}

try {
    echo "\n⏳ Creando tabla...\n";
    
    \Illuminate\Support\Facades\Schema::create('auditoria_folios', function ($table) {
        $table->id('id_auditoria_folio');
        $table->string('folio_completo', 50)->unique();
        $table->string('numero_base', 5);
        $table->integer('digito_verificador');
        $table->integer('fk_id_beneficiario')->nullable()->index();
        $table->integer('fk_folio_solicitud')->nullable()->index();
        $table->integer('año_fiscal')->index();
        $table->dateTime('fecha_generacion')->default(\Illuminate\Support\Facades\DB::raw('GETDATE()'));
        $table->integer('generado_por')->nullable();
        $table->string('ip_generacion', 45)->nullable();
        $table->timestamps();
    });
    
    echo "✅ Tabla 'auditoria_folios' creada exitosamente\n";
    echo "\nEstructura:\n";
    echo "  - id_auditoria_folio: INT (PRIMARY KEY)\n";
    echo "  - folio_completo: VARCHAR(50) UNIQUE\n";
    echo "  - numero_base: VARCHAR(5)\n";
    echo "  - digito_verificador: INT\n";
    echo "  - fk_id_beneficiario: INT (NULLABLE)\n";
    echo "  - fk_folio_solicitud: INT (NULLABLE)\n";
    echo "  - año_fiscal: INT\n";
    echo "  - fecha_generacion: DATETIME2\n";
    echo "  - generado_por: INT (NULLABLE)\n";
    echo "  - ip_generacion: VARCHAR(45)\n";
    echo "  - created_at, updated_at\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error al crear la tabla:\n";
    echo $e->getMessage() . "\n";
}
?>
