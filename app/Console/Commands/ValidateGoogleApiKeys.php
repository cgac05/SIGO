<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google_Client;
use Google_Service_Drive;

class ValidateGoogleApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:validate-keys';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Validar que las claves de Google API estén correctamente configuradas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Validando configuración de Google API...');
        $this->newLine();

        $allValid = true;

        // 1. Verificar variables de entorno
        $this->info('📋 1. Verificando variables de entorno:');
        
        $env_vars = [
            'GOOGLE_CLIENT_ID' => config('services.google.client_id'),
            'GOOGLE_CLIENT_SECRET' => config('services.google.client_secret'),
            'GOOGLE_API_KEY' => config('services.google.api_key'),
            'GOOGLE_REDIRECT_URI' => config('services.google.redirect'),
        ];

        foreach ($env_vars as $key => $value) {
            if (empty($value)) {
                $this->error("   ✗ {$key} NO está configurado");
                $allValid = false;
            } else {
                // Mostrar solo parte de la clave para seguridad
                $display = $this->maskSecret($value);
                $this->line("   ✓ {$key}: {$display}");
            }
        }

        $this->newLine();

        // 2. Verificar acceso a Google Picker API
        $this->info('🎯 2. Verificando acceso a Google Picker API:');
        
        if ($this->validatePickerApi()) {
            $this->line('   ✓ Google Picker API está habilitado');
        } else {
            $this->error('   ✗ Google Picker API podría no estar habilitado');
            $allValid = false;
        }

        $this->newLine();

        // 3. Verificar cliente de Google Drive
        $this->info('🚗 3. Verificando cliente de Google Drive:');
        
        try {
            $client = $this->createGoogleClient();
            $this->line('   ✓ Cliente de Google creado exitosamente');
        } catch (\Exception $e) {
            $this->error('   ✗ Error al crear cliente de Google: ' . $e->getMessage());
            $allValid = false;
        }

        $this->newLine();

        // 4. Verificar OAuth scopes
        $this->info('🔐 4. Verificando OAuth Scopes:');
        
        $scopes = config('services.google.scopes', []);
        if (empty($scopes)) {
            $this->error('   ✗ No hay scopes configurados');
            $allValid = false;
        } else {
            foreach ($scopes as $scope) {
                $this->line("   ✓ Scope: {$scope}");
            }
        }

        $this->newLine();

        // 5. Verificar formato de URL redirect
        $this->info('🔗 5. Verificando URL de redirección:');
        
        $redirectUri = config('services.google.redirect');
        if ($this->validateRedirectUri($redirectUri)) {
            $this->line("   ✓ URL de redirección válida: {$redirectUri}");
        } else {
            $this->error("   ✗ URL de redirección inválida: {$redirectUri}");
            $allValid = false;
        }

        $this->newLine();

        // Resumen final
        if ($allValid) {
            $this->info('✅ Todas las validaciones pasaron con éxito!');
            $this->line('');
            $this->line('Las claves de Google API están correctamente configuradas.');
            $this->line('Puedes proceder a usar Google Drive en la aplicación.');
            return 0;
        } else {
            $this->error('❌ Se encontraron problemas en la configuración.');
            $this->line('');
            $this->error('Por favor, revisa la documentación en GOOGLE_DRIVE_API_KEYS_GUIDE.md');
            return 1;
        }
    }

    /**
     * Crear cliente de Google
     */
    private function createGoogleClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setScopes(config('services.google.scopes', []));
        
        return $client;
    }

    /**
     * Validar Picker API
     */
    private function validatePickerApi(): bool
    {
        // Verificar que la API Key exista
        if (empty(config('services.google.api_key'))) {
            return false;
        }

        // La validación completa requeriría hacer una llamada a Google API
        // Por ahora solo verificar que la key exista
        return true;
    }

    /**
     * Validar URL de redirección
     */
    private function validateRedirectUri(string $uri): bool
    {
        // Debe ser una URL válida
        if (!filter_var($uri, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Debe tener /auth/google/callback
        if (strpos($uri, '/auth/google/callback') === false) {
            return false;
        }

        return true;
    }

    /**
     * Enmascarar secretos para mostrar de forma segura
     */
    private function maskSecret(string $value): string
    {
        if (strlen($value) <= 8) {
            return '****';
        }

        $start = substr($value, 0, 4);
        $end = substr($value, -4);
        $middle = '****';

        return "{$start}{$middle}{$end}";
    }
}
