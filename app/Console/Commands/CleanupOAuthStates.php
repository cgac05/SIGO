<?php

namespace App\Console\Commands;

use App\Models\OAuthState;
use Illuminate\Console\Command;

class CleanupOAuthStates extends Command
{
    protected $signature = 'oauth:cleanup';
    protected $description = 'Limpiar estados OAuth expirados de la base de datos';

    public function handle()
    {
        $deleted = OAuthState::cleanupExpired();
        $this->info("Se eliminaron {$deleted} estados OAuth expirados.");
        return 0;
    }
}
