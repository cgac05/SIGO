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
        Schema::create('google_drive_files', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('google_file_id')->unique()->comment('ID del archivo en Google Drive');
            $table->string('file_name')->comment('Nombre del archivo original');
            $table->bigInteger('file_size')->comment('Tamaño del archivo en bytes');
            $table->string('mime_type')->comment('Tipo MIME del archivo');
            $table->text('storage_path')->comment('Ruta del archivo en el almacenamiento local');
            $table->timestamps();

            // Índices
            $table->index('user_id');
            $table->index('created_at');
            $table->index('google_file_id');

            // Foreign key - SQL Server compatible
            $table->foreign('user_id')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_files');
    }
};
