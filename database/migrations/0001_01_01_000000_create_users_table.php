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
        Schema::create('Cat_Roles', function (Blueprint $table) {
            $table->unsignedInteger('id_rol')->primary();
            $table->string('nombre_rol', 20)->unique();
        });

        Schema::create('Usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('email', 100)->unique();
            $table->string('password_hash')->nullable();
            $table->string('tipo_usuario', 20);
            $table->string('google_id')->nullable()->unique();
            $table->text('google_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->text('google_avatar')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('ultima_conexion')->nullable();
            $table->rememberToken();
        });

        Schema::create('Personal', function (Blueprint $table) {
            $table->string('numero_empleado', 15)->primary();
            $table->unsignedInteger('fk_id_usuario')->unique();
            $table->string('nombre', 150);
            $table->string('apellido_paterno', 50);
            $table->string('apellido_materno', 50);
            $table->unsignedInteger('fk_rol')->nullable();
            $table->string('puesto', 100)->nullable();

            $table->foreign('fk_id_usuario')->references('id_usuario')->on('Usuarios')->cascadeOnDelete();
            $table->foreign('fk_rol')->references('id_rol')->on('Cat_Roles')->nullOnDelete();
        });

        Schema::create('Beneficiarios', function (Blueprint $table) {
            $table->char('curp', 18)->primary();
            $table->unsignedInteger('fk_id_usuario')->unique();
            $table->string('nombre', 150);
            $table->string('apellido_paterno', 50);
            $table->string('apellido_materno', 50);
            $table->string('telefono', 15)->nullable();
            $table->date('fecha_nacimiento');
            $table->string('genero', 10)->nullable();
            $table->dateTime('fecha_registro')->useCurrent();
            $table->boolean('acepta_privacidad')->default(false);

            $table->foreign('fk_id_usuario')->references('id_usuario')->on('Usuarios')->cascadeOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('Beneficiarios');
        Schema::dropIfExists('Personal');
        Schema::dropIfExists('Usuarios');
        Schema::dropIfExists('Cat_Roles');
    }
};
