<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateOAuthStatesTable extends Command
{
    protected $signature = 'oauth:create-table';
    protected $description = 'Create OAuth states table';

    public function handle()
    {
        if (Schema::hasTable('oauth_states')) {
            $this->info('oauth_states table already exists.');
            return;
        }

        DB::statement("
            CREATE TABLE oauth_states (
                id BIGINT PRIMARY KEY IDENTITY(1,1),
                state NVARCHAR(255) UNIQUE NOT NULL,
                directivo_id INT NOT NULL,
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

        $this->info('oauth_states table created successfully!');
    }
}
