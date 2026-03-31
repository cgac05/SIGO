<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GoogleAvatarService;
use Illuminate\Console\Command;

class DownloadGoogleAvatars extends Command
{
    protected $signature = 'google:download-avatars {--email= : Email específico del usuario}';

    protected $description = 'Descargar y guardar localmente los avatares de Google para usuarios existentes';

    public function handle(): int
    {
        $email = $this->option('email');

        $query = User::whereNotNull('google_avatar')
            ->where(function ($q) {
                $q->whereNull('foto_ruta')
                  ->orWhere('foto_ruta', '');
            });

        if ($email) {
            $query->where('email', $email);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No hay usuarios para procesar.');
            return 0;
        }

        $this->info("Procesando {$users->count()} usuario(s)...");

        foreach ($users as $user) {
            $this->line("Descargando avatar para: {$user->email}");

            $userType = $user->tipo_usuario === 'Beneficiario' ? 'beneficiarios' : 'personal';
            
            $fotoRuta = GoogleAvatarService::downloadAndStore(
                $user->google_avatar,
                $userType,
                $user->id_usuario
            );

            if ($fotoRuta) {
                $user->foto_ruta = $fotoRuta;
                $user->save();
                $this->info("✓ Avatar guardado: {$fotoRuta}");
            } else {
                $this->error("✗ Error descargando avatar para {$user->email}");
            }
        }

        $this->info('Proceso completado!');
        return 0;
    }
}
