<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Historico_Cierre', function (Blueprint $table) {
            // Hash único del certificado para trazabilidad
            $table->string('hash_certificado')->nullable()->after('ip_terminal');
            
            // Datos codificados en el QR (folio|fecha|monto|hash)
            $table->text('qrcode_data')->nullable()->after('hash_certificado');
            
            // Ruta al archivo QR generado (PNG)
            $table->string('ruta_qrcode')->nullable()->after('qrcode_data');
            
            // Firma digital en base64 (firma electrónica del desembolso)
            $table->longText('firma_digital')->nullable()->after('ruta_qrcode');
            
            // Fecha cuando se generó el certificado digital
            $table->timestamp('fecha_certificacion')->nullable()->after('firma_digital');
            
            // Estado: PENDIENTE, CERTIFICADO, VALIDADO
            $table->string('estado_certificacion')->default('PENDIENTE')->after('fecha_certificacion');
            
            // JSON con registro de cadena de custodia (quién validó, cuándo, desde dónde)
            $table->json('cadena_custodia_json')->nullable()->after('estado_certificacion');
            
            // Índices para búsquedas rápidas
            $table->index('hash_certificado');
            $table->index('estado_certificacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Historico_Cierre', function (Blueprint $table) {
            $table->dropColumn([
                'hash_certificado',
                'qrcode_data',
                'ruta_qrcode',
                'firma_digital',
                'fecha_certificacion',
                'estado_certificacion',
                'cadena_custodia_json',
            ]);
        });
    }
};
