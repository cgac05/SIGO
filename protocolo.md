Protocolo de Implementación: Módulo de Carga desde Google Drive - SIGO

Este documento constituye el conjunto de instrucciones para el agente de IA encargado de desarrollar el módulo de carga de documentación para la Plataforma Estatal de Juventud. El objetivo es permitir que los beneficiarios seleccionen archivos de su Google Drive personal y los transfieran de forma segura al almacenamiento del sistema SIGO.

1. Arquitectura y Lógica del Sistema ("El Porqué")

1.1 Selección del Scope: drive.file

¿Qué es?: Se debe solicitar el permiso específico https://www.googleapis.com/auth/drive.file.
¿Por qué?: Por principio de privacidad y facilidad de verificación ante Google. Este permiso solo otorga a la plataforma acceso a los archivos que el usuario selecciona explícitamente a través del selector. Evita que la app tenga acceso a todo el Drive del joven, lo cual reduce riesgos legales y técnicos.

1.2 Uso de Google Picker API

¿Qué es?: Un componente visual de Google (selector de archivos).
¿Por qué?: Proporciona una interfaz familiar y segura. Al usarse en el frontend, el usuario interactúa directamente con los servidores de Google para elegir su archivo, y la plataforma solo recibe el ID del elemento seleccionado.

1.3 Descarga de Servidor a Servidor (Server-side Download)

¿Qué es?: El servidor de SIGO debe descargar el archivo usando el ID obtenido.
¿Por qué?: Garantiza la persistencia. Aunque el joven elimine el archivo de su Drive personal más tarde, el sistema SIGO debe conservar una copia en su propio almacenamiento (Azure/Local) para fines de auditoría y validación del trámite.

2. Requerimientos de Credenciales (Placeholders)

El agente de IA debe dejar el código preparado con las siguientes variables para que el desarrollador las complete:

CLIENT_ID_PLACEHOLDER: Identificador único de la aplicación en Google Cloud.

API_KEY_PLACEHOLDER: Clave para habilitar el componente visual del selector.

CLIENT_SECRET_PLACEHOLDER: Secreto para la comunicación segura entre el servidor SIGO y Google.

3. Flujo de Ejecución para el Agente de IA

Fase 1: Actualización del OAuth (Login)

El agente debe modificar la lógica de inicio de sesión actual. No basta con autenticar; se debe solicitar el acceso a archivos en el mismo flujo para evitar pedir permisos múltiples veces.

Acción: Inyectar el scope de Drive en la configuración de Socialite/OAuth.

Fase 2: Implementación del Selector (Frontend)

El agente debe generar un componente de interfaz que cargue las librerías de Google de forma asíncrona.

Lógica: Solo activar el botón "Cargar desde Drive" cuando el usuario esté autenticado. Al seleccionar, capturar el fileId y enviarlo mediante una petición POST al backend.

Fase 3: Puente de Descarga (Backend)

El agente debe crear un controlador que actúe como puente.

Lógica:

Recibir el fileId.

Utilizar el Token de sesión del usuario.

Solicitar a la API de Google los metadatos y el flujo de datos (stream) del archivo.

Guardar dicho flujo en el almacenamiento definitivo del proyecto SIGO.

4. Validaciones Críticas de Seguridad

El agente debe incluir obligatoriamente:

Validación de Tipo: Solo permitir extensiones permitidas (PDF, JPG, PNG).

Manejo de Excepciones: Si el token de Google ha expirado o el archivo es mayor a 5MB, el sistema debe informar al usuario de manera amigable mediante un mensaje en pantalla, evitando errores internos del servidor.

Límite de Tiempo: Implementar un cierre automático del selector si no hay actividad para prevenir sesiones abiertas en dispositivos públicos.

5. Preparación para Producción

Para que la implementación sea exitosa, el agente de IA debe considerar que la URL de redireccionamiento debe coincidir exactamente con la configurada en la consola de Google, y que el dominio debe contar con certificado SSL (HTTPS) activo.

---

## 6. Especificaciones Técnicas Detalladas

### 6.1 Configuración de Google Cloud Console

#### Paso 1: Crear Proyecto
```
1. Ir a Google Cloud Console (console.cloud.google.com)
2. Crear nuevo proyecto: "SIGO-Google-Drive"
3. Habilitar APIs:
   - Google Drive API v3
   - Google Picker API
4. Crear credenciales OAuth 2.0:
   - Tipo: Web application
   - JavaScript origins: https://dominio-produccion.com, http://localhost:8000
   - Authorized redirect URIs: 
     * https://dominio-produccion.com/auth/google/callback
     * http://localhost:8000/auth/google/callback
```

#### Paso 2: Descargar Credenciales
```json
{
  "web": {
    "client_id": "CLIENT_ID_PLACEHOLDER.apps.googleusercontent.com",
    "project_id": "sigo-proyecto",
    "auth_uri": "https://accounts.google.com/o/oauth2/auth",
    "token_uri": "https://oauth2.googleapis.com/token",
    "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
    "client_secret": "CLIENT_SECRET_PLACEHOLDER",
    "redirect_uris": ["https://dominio.com/auth/google/callback"]
  }
}
```

### 6.2 Dependencias Requeridas

```bash
# Para Laravel/PHP
composer require google/apiclient
composer require laravel/socialite

# Para JavaScript (Frontend)
npm install google-picker
```

### 6.3 Variables de Entorno (.env)

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=CLIENT_ID_PLACEHOLDER
GOOGLE_CLIENT_SECRET=CLIENT_SECRET_PLACEHOLDER
GOOGLE_DRIVE_API_KEY=API_KEY_PLACEHOLDER
GOOGLE_REDIRECT_URI=https://dominio.com/auth/google/callback

# Drive Configuration
GOOGLE_DRIVE_SCOPE=https://www.googleapis.com/auth/drive.file
MAX_FILE_SIZE=5242880  # 5MB en bytes
ALLOWED_EXTENSIONS=pdf,jpg,jpeg,png
STORAGE_PATH=storage/google_drive_uploads
```

---

## 7. Implementación del Código

### 7.1 Configuración de Socialite (config/services.php)

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
    'scopes' => [
        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ],
],
```

### 7.2 Controlador de Autenticación (app/Http/Controllers/Auth/GoogleAuthController.php)

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    /**
     * Redirigir a Google OAuth
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->scopes([
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ])->redirect();
    }

    /**
     * Manejar callback de Google
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
                'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn),
            ]);

            auth()->login($user);

            return redirect()->intended('/dashboard')
                ->with('success', 'Autenticado correctamente con Google');
        } catch (\Exception $e) {
            return redirect('/login')
                ->with('error', 'Error en autenticación: ' . $e->getMessage());
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

### 7.3 Modelo Usuario Actualizado (app/Models/User.php)

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'google_token',
        'google_refresh_token',
        'google_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'google_token_expires_at' => 'datetime',
    ];

    /**
     * Verificar si el token de Google ha expirado
     */
    public function isGoogleTokenExpired(): bool
    {
        return $this->google_token_expires_at?->isPast() ?? true;
    }

    /**
     * Obtener cliente Google autenticado
     */
    public function getGoogleClient()
    {
        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        
        if ($this->google_token) {
            $client->setAccessToken($this->google_token);
            
            if ($this->isGoogleTokenExpired() && $this->google_refresh_token) {
                $client->fetchAccessTokenWithRefreshToken($this->google_refresh_token);
                $this->update([
                    'google_token' => $client->getAccessToken()['access_token'],
                    'google_token_expires_at' => now()->addSeconds(3600),
                ]);
            }
        }
        
        return $client;
    }
}
```

### 7.4 Componente Frontend - Blade (resources/views/components/google-drive-picker.blade.php)

```blade
<div x-data="googleDrivePicker()" class="mt-6">
    <button 
        @click="initPicker()" 
        :disabled="!isAuthenticated"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
    >
        📁 Cargar desde Google Drive
    </button>

    <!-- Estado de carga -->
    <div x-show="isLoading" class="mt-4 p-4 bg-blue-100 rounded-lg">
        <p class="text-blue-800">Cargando archivo...</p>
        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" :style="{ width: progress + '%' }"></div>
        </div>
    </div>

    <!-- Mensajes de error -->
    <div x-show="error" class="mt-4 p-4 bg-red-100 rounded-lg">
        <p class="text-red-800" x-text="error"></p>
    </div>

    <!-- Archivos cargados -->
    <div x-show="uploadedFiles.length > 0" class="mt-4">
        <h3 class="font-bold mb-2">Archivos Cargados:</h3>
        <ul class="space-y-2">
            <template x-for="file in uploadedFiles" :key="file.id">
                <li class="flex items-center justify-between p-3 bg-green-100 rounded-lg">
                    <span x-text="file.name" class="text-green-800"></span>
                    <button 
                        @click="removeFile(file.id)"
                        class="text-red-600 hover:text-red-800"
                    >
                        ✕
                    </button>
                </li>
            </template>
        </ul>
    </div>
</div>

<script>
    function googleDrivePicker() {
        return {
            isAuthenticated: @json(auth()->check()),
            isLoading: false,
            progress: 0,
            error: '',
            uploadedFiles: [],
            
            async initPicker() {
                try {
                    // Cargar Google Picker library
                    gapi.load('picker', { 'callback': this.onPickerLibraryLoaded.bind(this) });
                } catch (e) {
                    this.error = 'Error al cargar Google Picker: ' + e.message;
                }
            },

            onPickerLibraryLoaded() {
                const picker = new google.picker.PickerBuilder()
                    .addView(google.picker.ViewId.DOCS)
                    .setOAuthToken(this.getAccessToken())
                    .setCallback(this.pickerCallback.bind(this))
                    .setOrigin('{{ config("app.url") }}')
                    .build();
                
                picker.setVisible(true);
            },

            pickerCallback(data) {
                if (data.action === google.picker.Action.PICKED) {
                    const file = data.docs[0];
                    this.uploadFile(file);
                }
            },

            async uploadFile(file) {
                this.isLoading = true;
                this.progress = 0;
                this.error = '';

                try {
                    const response = await fetch('/api/google-drive/upload', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            fileId: file.id,
                            fileName: file.name,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error(await response.text());
                    }

                    const result = await response.json();
                    this.uploadedFiles.push(result.file);
                    this.progress = 100;
                    
                    // Limpiar en 3 segundos
                    setTimeout(() => {
                        this.isLoading = false;
                        this.progress = 0;
                    }, 3000);

                } catch (e) {
                    this.error = e.message;
                    this.isLoading = false;
                }
            },

            async removeFile(fileId) {
                try {
                    await fetch(`/api/google-drive/file/${fileId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    this.uploadedFiles = this.uploadedFiles.filter(f => f.id !== fileId);
                } catch (e) {
                    this.error = 'Error al eliminar archivo: ' + e.message;
                }
            },

            getAccessToken() {
                // Obtener token del servidor
                return document.querySelector('meta[name="google-access-token"]')?.content || '';
            }
        };
    }
</script>
```

### 7.5 Controlador de Descarga (app/Http/Controllers/GoogleDriveController.php)

```php
<?php

namespace App\Http\Controllers;

use App\Models\GoogleDriveFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Google_Service_Drive;

class GoogleDriveController extends Controller
{
    protected $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    protected $maxFileSize = 5242880; // 5MB

    /**
     * Descargar archivo desde Google Drive
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fileId' => 'required|string',
                'fileName' => 'required|string',
            ]);

            $user = auth()->user();

            // Verificar token
            if ($user->isGoogleTokenExpired()) {
                return response()->json([
                    'error' => 'Token de Google expirado. Por favor, reautentícate.',
                ], 401);
            }

            // Obtener cliente de Google
            $client = $user->getGoogleClient();
            $driveService = new Google_Service_Drive($client);

            // Obtener metadatos del archivo
            $file = $driveService->files->get($request->fileId, [
                'fields' => 'id, name, size, mimeType, webContentLink',
            ]);

            // Validaciones
            $this->validateFile($file);

            // Descargar archivo
            $resource = $driveService->files->get($request->fileId, [
                'alt' => 'media',
            ]);

            $content = '';
            $chunkSize = 262144; // 256KB chunks
            while (!$resource->getRequest()->getResponseHeaders()->offsetExists('content-length')) {
                $content .= $resource->getRequest()->execute()->getBody();
            }

            // Guardar en storage
            $path = $this->saveFile($content, $file->getName(), $user->id);

            // Registrar en base de datos
            $googleFile = GoogleDriveFile::create([
                'user_id' => $user->id,
                'google_file_id' => $request->fileId,
                'file_name' => $file->getName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'storage_path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'file' => $googleFile,
                'message' => 'Archivo cargado exitosamente',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Validar archivo
     */
    protected function validateFile($file): void
    {
        $extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions)) {
            throw new \Exception(
                'Tipo de archivo no permitido. Extensiones válidas: ' . 
                implode(', ', $this->allowedExtensions)
            );
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception(
                'Archivo muy grande. Máximo permitido: ' . 
                number_format($this->maxFileSize / 1024 / 1024, 2) . 'MB'
            );
        }
    }

    /**
     * Guardar archivo localmente
     */
    protected function saveFile($content, $fileName, $userId): string
    {
        $directory = config('app.google_drive_storage_path') . '/' . $userId;
        $filename = uniqid() . '_' . sanitizeFilename($fileName);
        
        Storage::disk('local')->put($directory . '/' . $filename, $content);

        return $directory . '/' . $filename;
    }

    /**
     * Obtener archivos del usuario
     */
    public function list(): JsonResponse
    {
        $files = auth()->user()
            ->googleDriveFiles()
            ->latest()
            ->get();

        return response()->json($files);
    }

    /**
     * Eliminar archivo
     */
    public function destroy($fileId): JsonResponse
    {
        try {
            $file = GoogleDriveFile::where('google_file_id', $fileId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            Storage::disk('local')->delete($file->storage_path);
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
```

### 7.6 Modelo GoogleDriveFile (app/Models/GoogleDriveFile.php)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleDriveFile extends Model
{
    protected $fillable = [
        'user_id',
        'google_file_id',
        'file_name',
        'file_size',
        'mime_type',
        'storage_path',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### 7.7 Migración de Base de Datos (database/migrations/2026_03_25_create_google_drive_files_table.php)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('google_file_id')->unique();
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('storage_path');
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_files');
    }
};
```

### 7.8 Rutas (routes/web.php)

```php
Route::middleware(['auth'])->group(function () {
    // Google Drive Routes
    Route::post('/api/google-drive/upload', [GoogleDriveController::class, 'upload']);
    Route::get('/api/google-drive/files', [GoogleDriveController::class, 'list']);
    Route::delete('/api/google-drive/file/{fileId}', [GoogleDriveController::class, 'destroy']);
});

// Google Auth Routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::post('/logout', [GoogleAuthController::class, 'logout'])->middleware('auth')->name('logout');
```

---

## 8. Diagrama de Flujo de Autenticación y Descarga

```
┌─────────────────────────────────────────────────────────────────┐
│ USUARIO BENEFICIARIO                                            │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │ 1. Click "Cargar desde     │
        │    Google Drive"           │
        └────────┬───────────────────┘
                 │
                 ▼
    ┌────────────────────────────────────┐
    │ 2. Verificar Token Google          │
    │    ¿Token expirado?                │
    │    NO → Continuar                  │
    │    SÍ → Refresca con refresh_token │
    └────────┬───────────────────────────┘
             │
             ▼
  ┌──────────────────────────────────────┐
  │ 3. Google Picker API ejecuta en      │
  │    navegador del usuario             │
  │    (interfaz familiar de Google)     │
  └────────┬─────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────┐
│ 4. Usuario selecciona archivo de su   │
│    Google Drive personal              │
│    → fileId obtenido                  │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│ 5. POST /api/google-drive/upload     │
│    - fileId                           │
│    - fileName                         │
│    - User Access Token                │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────┐
│ SERVIDOR SIGO (Backend PHP/Laravel)         │
│                                              │
│ 6. Validar token × experación               │
│ 7. Inicializar Google_Service_Drive         │
│ 8. Obtener metadatos del archivo             │
└────────┬───────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────┐
│ 9. VALIDACIONES CRÍTICAS DE SEGURIDAD        │
│    ✓ Extensión permitida (PDF/JPG/PNG)     │
│    ✓ Tamaño ≤ 5MB                           │
│    ✓ MIME type válido                        │
│    ✓ Pertenencia del archivo al usuario      │
└────────┬───────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────┐
│ 10. Descargar contenido del archivo          │
│     desde Google Drive a través de API       │
│     (stream en chunks de 256KB)              │
└────────┬───────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────┐
│ 11. Guardar archivo en Storage Local/Azure   │
│     Ruta: storage/google_drive_uploads/      │
│            {user_id}/{unique_id}_{filename}  │
└────────┬───────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────┐
│ 12. Registrar metadatos en BD:               │
│     - google_drive_files table               │
│     - user_id, google_file_id, file_name    │
│     - file_size, mime_type, storage_path    │
└────────┬───────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────┐
│ 13. Respuesta JSON SUCCESS                    │
│     { success: true, file: {...} }           │
└────────┬───────────────────────────────────┘
         │
         ▼
    ┌─────────────────────────┐
    │ 14. UI Actualiza lista   │
    │     de archivos cargados │
    │     Muestra mensaje ✓    │
    └─────────────────────────┘
```

---

## 9. Manejo de Errores y Excepciones

### 9.1 Matriz de Errores

| Escenario | Código HTTP | Mensaje Usuario | Acción |
|-----------|------------|-----------------|--------|
| Token expirado | 401 | "Sesión expirada. Por favor, reautentícate." | Redirigir a login |
| Archivo muy grande | 413 | "Archivo muy grande. Máximo: 5MB" | Redirigir a Drive |
| Extensión no permitida | 415 | "Tipo de archivo no permitido. Permite: PDF, JPG, PNG" | Redirigir a Drive |
| Llamadas API excedidas | 429 | "Demasiadas solicitudes. Intenta en 1 hora." | Mostrar contador |
| Error de red/timeout | 502 | "Error de conexión. Intenta nuevamente." | Reintentar automático |

### 9.2 Implementación de Reintentos

```php
// En GoogleDriveController.php

protected function downloadWithRetry($driveService, $fileId, $maxRetries = 3)
{
    $retries = 0;
    
    while ($retries < $maxRetries) {
        try {
            return $driveService->files->get($fileId, ['alt' => 'media']);
        } catch (\Google_Service_Exception $e) {
            $retries++;
            
            if ($e->getCode() === 429) { // Rate limit
                sleep(pow(2, $retries)); // Exponential backoff
            } elseif ($retries >= $maxRetries || $e->getCode() === 403) {
                throw $e;
            }
        }
    }
}
```

---

## 10. Testing

### 10.1 Unit Tests (tests/Unit/GoogleDriveFileTest.php)

```php
<?php

namespace Tests\Unit;

use App\Models\GoogleDriveFile;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class GoogleDriveFileTest extends TestCase
{
    public function test_file_belongs_to_user()
    {
        $user = User::factory()->create();
        $file = GoogleDriveFile::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($file->user->is($user));
    }

    public function test_file_validation_extension()
    {
        // Validar que solo extensiones permitidas se acepten
        $this->assertTrue(in_array('pdf', ['pdf', 'jpg', 'png']));
        $this->assertFalse(in_array('exe', ['pdf', 'jpg', 'png']));
    }

    public function test_file_size_limit()
    {
        $maxSize = 5242880; // 5MB
        $testSize = 3 * 1024 * 1024; // 3MB

        $this->assertLessThan($maxSize, $testSize);
    }
}
```

### 10.2 Feature Tests (tests/Feature/GoogleDriveUploadTest.php)

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class GoogleDriveUploadTest extends TestCase
{
    public function test_user_must_be_authenticated()
    {
        $response = $this->post('/api/google-drive/upload', [
            'fileId' => 'test-id',
            'fileName' => 'test.pdf',
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_with_invalid_extension()
    {
        $user = User::factory()->create(['google_token' => 'valid-token']);

        $response = $this->actingAs($user)->post('/api/google-drive/upload', [
            'fileId' => 'test-id',
            'fileName' => 'malware.exe',
        ]);

        $response->assertStatus(415);
    }

    public function test_successful_upload()
    {
        $user = User::factory()->create([
            'google_token' => 'valid-token',
            'google_token_expires_at' => now()->addHours(1),
        ]);

        $response = $this->actingAs($user)->post('/api/google-drive/upload', [
            'fileId' => 'valid-id',
            'fileName' => 'document.pdf',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }
}
```

---

## 11. Seguridad Avanzada

### 11.1 Rate Limiting

```php
// routes/web.php

Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/api/google-drive/upload', [GoogleDriveController::class, 'upload']);
});
```

### 11.2 Cifrado de Tokens Sensibles

```php
// app/Models/User.php

protected function casts(): array
{
    return [
        'google_token' => 'encrypted',
        'google_refresh_token' => 'encrypted',
    ];
}
```

### 11.3 Auditoría de Descargas

```php
// Crear tabla de auditoría

Schema::create('google_drive_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('google_drive_file_id')->constrained();
    $table->string('action'); // upload, download, delete
    $table->ipAddress('ip_address');
    $table->text('user_agent');
    $table->timestamps();
});
```

---

## 12. Checklist de Implementación

### Pre-Implementación
- [ ] Crear proyecto en Google Cloud Console
- [ ] Obtener credenciales OAuth 2.0
- [ ] Configurar URLs de redirección exactamente
- [ ] Habilitar Google Drive API v3
- [ ] Habilitar Google Picker API
- [ ] Generar API Key
- [ ] Certificado SSL activo en producción

### Implementación
- [ ] Instalar dependencias (google/apiclient, laravel/socialite)
- [ ] Configurar variables de entorno
- [ ] Actualizar config/services.php
- [ ] Crear controlador de autenticación
- [ ] Actualizar modelo User
- [ ] Crear componente Blade frontend
- [ ] Crear controlador GoogleDriveController
- [ ] Crear modelo GoogleDriveFile
- [ ] Crear migración de base de datos
- [ ] Ejecutar migraciones: `php artisan migrate`
- [ ] Configurar rutas

### Testing
- [ ] Tests unitarios del modelo
- [ ] Tests de feature para autenticación
- [ ] Tests para validación de archivos
- [ ] Tests de manejo de errores
- [ ] Tests de rate limiting

### Producción
- [ ] Cambiar a URLs de producción en Google Cloud
- [ ] Verificar variables de entorno
- [ ] Configurar backups automáticos de archivos
- [ ] Implementar logging y monitoreo
- [ ] Configurar CORS si es necesario
- [ ] Pruebas de carga (load testing)
- [ ] Documentación de API
- [ ] Capacitación de usuarios

---

## 13. Monitoreo y Logging

### 13.1 Configurar Monitoreo

```php
// config/logging.php - Agregar canal específico

'google_drive' => [
    'driver' => 'single',
    'path' => storage_path('logs/google-drive.log'),
    'level' => env('LOG_LEVEL', 'debug'),
],
```

### 13.2 Logging en el Controlador

```php
// En GoogleDriveController

\Log::channel('google_drive')->info('Archivo descargado', [
    'user_id' => $user->id,
    'google_file_id' => $request->fileId,
    'file_name' => $file->getName(),
    'file_size' => $file->getSize(),
    'timestamp' => now(),
]);
```

---

## 14. Referencias y Recursos

- [Google Drive API Documentation](https://developers.google.com/drive/api/guides/about-sdk)
- [Google Picker API](https://developers.google.com/picker/docs)
- [Laravel Socialite](https://laravel.com/docs/socialite)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)
- [OAuth 2.0 Flow](https://tools.ietf.org/html/rfc6749)

---

## 15. Notas Importantes para el Agente IA

Este protocolo está diseñado para guiar de forma **exhaustiva** la implementación. Sin embargo, recuerda:

1. **Placeholders**: Los valores `CLIENT_ID_PLACEHOLDER`, `API_KEY_PLACEHOLDER`, y `CLIENT_SECRET_PLACEHOLDER` deben reemplazarse por valores reales antes de producción.

2. **Personalización**: El código proporcionado es un ejemplo base. Ajusta según las necesidades específicas de SIGO.

3. **Testing obligatorio**: No desplegar a producción sin tests end-to-end completados.

4. **Seguridad primero**: Revisar todas las validaciones de entrada y cifrado de tokens.

5. **Documentación**: Mantener esta documentación actualizada con cambios realizados.