<?php
require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

if (!Schema::hasTable('oauth_states')) {
    echo "Creating oauth_states table...\n";
    DB::statement("
        CREATE TABLE oauth_states (
            id BIGINT PRIMARY KEY IDENTITY(1,1),
            state NVARCHAR(255) UNIQUE NOT NULL,
            directivo_id BIGINT NOT NULL,
            provider NVARCHAR(50) DEFAULT 'google',
            created_at DATETIME DEFAULT GETDATE(),
            expires_at DATETIME NULL,
            used_at DATETIME NULL,
            redirect_url NVARCHAR(255) NULL,
            FOREIGN KEY (directivo_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            INDEX idx_state (state),
            INDEX idx_directivo (directivo_id)
        )
    ");
    echo "Table created successfully!\n";
} else {
    echo "oauth_states table already exists.\n";
}
