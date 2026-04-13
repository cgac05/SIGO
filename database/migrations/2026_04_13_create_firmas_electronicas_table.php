<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Tabla ya existe en BD, solo verificamos que tenga las columnas necesarias
        if (Schema::hasTable('firmas_electronicas')) {
            \Log::info('✅ Tabla firmas_electronicas ya existe - migración completada');
            return;
        }

        Schema::create('firmas_electronicas', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->string('cuv')->unique(); // Código único de verificación
            $table->unsignedInteger('usuario_id'); // ID del directivo que firmó (INT, no BIGINT)
            $table->timestamp('fecha_firma')->useCurrent();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id_usuario')->on('Usuarios')->onDelete('cascade');
            $table->index('folio');
            $table->index('cuv');
            $table->index('fecha_firma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmas_electronicas');
    }
};
