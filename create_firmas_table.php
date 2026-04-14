<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CREAR TABLA firmas_electronicas ===\n\n";

// Verificar si la tabla ya existe
$exists = DB::select(
    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = 'BD_SIGO' AND TABLE_NAME = 'firmas_electronicas'"
);

if (count($exists) > 0) {
    echo "✓ Tabla 'firmas_electronicas' ya existe\n";
} else {
    echo "Creando tabla 'firmas_electronicas'...\n";
    
    DB::statement("
        CREATE TABLE firmas_electronicas (
            id_firma INT PRIMARY KEY IDENTITY(1,1),
            folio INT NOT NULL,
            cuv NVARCHAR(255) NOT NULL UNIQUE,
            usuario_id INT NOT NULL,
            fecha_firma DATETIME2 DEFAULT GETDATE(),
            ip_address NVARCHAR(45),
            user_agent NVARCHAR(MAX),
            created_at DATETIME2 DEFAULT GETDATE(),
            updated_at DATETIME2 DEFAULT GETDATE(),
            
            -- Relaciones
            CONSTRAINT FK_firmas_solicitudes FOREIGN KEY (folio) 
                REFERENCES Solicitudes(folio) ON DELETE CASCADE,
            CONSTRAINT FK_firmas_usuarios FOREIGN KEY (usuario_id) 
                REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
        )
    ");
    
    echo "✓ Tabla 'firmas_electronicas' creada exitosamente\n\n";
    
    // Crear índices
    DB::statement("CREATE UNIQUE INDEX UX_firmas_cuv ON firmas_electronicas(cuv)");
    echo "✓ Índice único en cuv creado\n";
    
    DB::statement("CREATE INDEX IX_firmas_folio ON firmas_electronicas(folio)");
    echo "✓ Índice en folio creado\n";
}

echo "\n=== ESTRUCTURA DE TABLA ===\n";
$columns = DB::select(
    "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'firmas_electronicas' AND TABLE_SCHEMA = 'BD_SIGO'
     ORDER BY ORDINAL_POSITION"
);

foreach ($columns as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
    echo "  - {$col->COLUMN_NAME} ({$col->DATA_TYPE}) {$nullable}\n";
}

echo "\n✅ Tabla lista para registrar firmas electrónicas\n";
