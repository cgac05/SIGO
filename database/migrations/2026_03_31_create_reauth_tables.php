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
        // Tabla para tokens de re-autenticación
        Schema::create('reauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('token')->unique(); // SHA256
            $table->dateTime('expira_en');
            $table->boolean('usado')->default(false);
            $table->dateTime('usado_en')->nullable();
            $table->dateTime('creado_en');

            $table->foreign('usuario_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');

            $table->index('expira_en');
            $table->index(['usuario_id', 'usado']);
        });

        // Tabla de auditoría para intentos de re-autenticación
        Schema::create('auditoria_reauthenticacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->boolean('exitoso');
            $table->enum('razon', [
                'contraseña_incorrecta',
                'otp_incorrecto',
                'sesion_expirada',
                'verificado',
                'otro',
            ])->default('otro');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->foreign('usuario_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');

            $table->index(['usuario_id', 'timestamp']);
            $table->index('timestamp');
        });

        // Tabla OTP temporal para 2FA
        Schema::create('otp_temporal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('codigo', 6);
            $table->enum('metodo', ['email', 'sms', 'authenticator'])->default('email');
            $table->dateTime('expira_en');
            $table->integer('intentos_restantes')->default(3);
            $table->timestamp('creado_en')->useCurrent();

            $table->foreign('usuario_id')
                ->references('id')
                ->on('usuarios')
                ->onDelete('cascade');

            $table->index(['usuario_id', 'expira_en']);
            $table->unique(['usuario_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_temporal');
        Schema::dropIfExists('auditoria_reauthenticacion');
        Schema::dropIfExists('reauth_tokens');
    }
};
