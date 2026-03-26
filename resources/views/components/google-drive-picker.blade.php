<div x-data="googleDrivePicker()" class="mt-6">
    <!-- Botón principal -->
    <div class="flex items-center gap-2 mb-4">
        <button 
            @click="initPicker()" 
            :disabled="!isAuthenticated || isLoading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
        >
            <span x-show="!isLoading">📁 Cargar desde Google Drive</span>
            <span x-show="isLoading">⏳ Procesando...</span>
        </button>
        
        <button
            @click="refreshList()"
            :disabled="isLoading"
            class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 disabled:opacity-50 transition-all duration-200"
            title="Actualizar lista"
        >
            🔄
        </button>
    </div>

    <!-- Estado de carga -->
    <div x-show="isLoading && progress > 0" class="mb-4 p-4 bg-blue-100 rounded-lg">
        <p class="text-blue-800 mb-2">Cargando archivo...</p>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all" :style="{ width: progress + '%' }"></div>
        </div>
        <p class="text-sm text-blue-600 mt-2" x-text="progress + '%'"></p>
    </div>

    <!-- Mensajes de error -->
    <div x-show="error" class="mb-4 p-4 bg-red-100 rounded-lg border border-red-300">
        <div class="flex items-start gap-2">
            <span class="text-xl">⚠️</span>
            <div>
                <p class="font-semibold text-red-800">Error</p>
                <p class="text-red-700" x-text="error"></p>
            </div>
            <button @click="error = ''" class="ml-auto text-red-600 hover:text-red-800">✕</button>
        </div>
    </div>

    <!-- Mensaje de éxito -->
    <div x-show="successMessage" class="mb-4 p-4 bg-green-100 rounded-lg border border-green-300">
        <div class="flex items-start gap-2">
            <span class="text-xl">✓</span>
            <div>
                <p class="font-semibold text-green-800">Éxito</p>
                <p class="text-green-700" x-text="successMessage"></p>
            </div>
            <button @click="successMessage = ''" class="ml-auto text-green-600 hover:text-green-800">✕</button>
        </div>
    </div>

    <!-- Archivos cargados -->
    <div x-show="uploadedFiles.length > 0" class="mt-6">
        <h3 class="text-lg font-bold mb-3 text-gray-800">Archivos Cargados ({{ count($uploadedFiles ?? []) }})</h3>
        <div class="space-y-2">
            <template x-for="(file, index) in uploadedFiles" :key="file.id">
                <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="text-2xl" x-text="getFileIcon(file.name)"></span>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800" x-text="file.name"></p>
                            <p class="text-sm text-gray-600" x-text="file.size + ' • ' + file.created_at"></p>
                        </div>
                    </div>
                    <button 
                        @click="removeFile(file.id, index)"
                        :disabled="isDeleting"
                        class="ml-2 px-3 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-50 transition-colors"
                    >
                        <span x-show="!isDeleting">Eliminar</span>
                        <span x-show="isDeleting">⏳</span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Sin archivos -->
    <div x-show="!isLoading && uploadedFiles.length === 0 && !error" class="mt-6 text-center p-6 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
        <p class="text-gray-500">📂 No hay archivos cargados aún</p>
        <p class="text-sm text-gray-400 mt-1">Haz clic en "Cargar desde Google Drive" para agregar archivos</p>
    </div>
</div>

<script>
    // Cargar Google API SDKs
    function loadGoogleAPIs() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://apis.google.com/js/api.js';
            script.onload = () => {
                gapi.load('picker', { 'callback': resolve });
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function googleDrivePicker() {
        return {
            isAuthenticated: @json(auth()->check()),
            isLoading: false,
            isDeleting: false,
            progress: 0,
            error: '',
            successMessage: '',
            uploadedFiles: [],
            maxFileSize: 5242880, // 5MB
            apisLoaded: false,

            async init() {
                if (!this.isAuthenticated) {
                    return;
                }

                try {
                    await loadGoogleAPIs();
                    this.apisLoaded = true;
                    await this.loadFiles();
                } catch (e) {
                    console.error('Error al cargar APIs de Google:', e);
                }
            },

            async initPicker() {
                if (!this.apisLoaded) {
                    try {
                        await loadGoogleAPIs();
                        this.apisLoaded = true;
                    } catch (e) {
                        this.error = 'Error al cargar Google Picker. Intenta recargar la página.';
                        return;
                    }
                }

                try {
                    const token = await this.getAccessToken();
                    if (!token) {
                        this.error = 'No se pudo obtener el token de acceso. Por favor, reautentícate.';
                        return;
                    }

                    const picker = new google.picker.PickerBuilder()
                        .addView(google.picker.ViewId.DOCS)
                        .setOAuthToken(token)
                        .setCallback(this.pickerCallback.bind(this))
                        .setOrigin(window.location.origin)
                        .build();

                    picker.setVisible(true);
                } catch (e) {
                    this.error = 'Error al abrir el selector de archivos: ' + e.message;
                    console.error(e);
                }
            },

            pickerCallback(data) {
                if (data.action === google.picker.Action.PICKED) {
                    const file = data.docs[0];
                    console.log('Archivo seleccionado:', file);
                    this.uploadFile(file);
                } else if (data.action === google.picker.Action.CANCEL) {
                    console.log('Selector cancelado');
                }
            },

            async uploadFile(file) {
                this.isLoading = true;
                this.progress = 0;
                this.error = '';
                this.successMessage = '';

                try {
                    // Simular progreso
                    const progressInterval = setInterval(() => {
                        if (this.progress < 90) {
                            this.progress += Math.random() * 30;
                        }
                    }, 500);

                    const response = await fetch('/api/google-drive/upload', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            fileId: file.id,
                            fileName: file.getName(),
                        }),
                    });

                    clearInterval(progressInterval);
                    this.progress = 100;

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.error || 'Error al cargar el archivo');
                    }

                    this.uploadedFiles.unshift(result.file);
                    this.successMessage = `✓ Archivo "${result.file.name}" cargado exitosamente`;

                    // Limpiar en 5 segundos
                    setTimeout(() => {
                        this.isLoading = false;
                        this.progress = 0;
                        this.successMessage = '';
                    }, 5000);

                } catch (e) {
                    this.error = e.message || 'Error desconocido al cargar el archivo';
                    console.error('Error:', e);
                    this.isLoading = false;
                }
            },

            async removeFile(fileId, index) {
                if (!confirm('¿Estás seguro de que deseas eliminar este archivo?')) {
                    return;
                }

                this.isDeleting = true;

                try {
                    const response = await fetch(`/api/google-drive/file/${fileId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Error al eliminar el archivo');
                    }

                    this.uploadedFiles.splice(index, 1);
                    this.successMessage = 'Archivo eliminado correctamente';

                    setTimeout(() => {
                        this.successMessage = '';
                    }, 3000);

                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.isDeleting = false;
                }
            },

            async loadFiles() {
                try {
                    const response = await fetch('/api/google-drive/files', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    const result = await response.json();
                    if (result.success) {
                        this.uploadedFiles = result.files;
                    }
                } catch (e) {
                    console.error('Error al cargar archivos:', e);
                }
            },

            async refreshList() {
                await this.loadFiles();
            },

            async getAccessToken() {
                // Obtener el token del servidor
                try {
                    const response = await fetch('/api/google-drive/token', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    if (!response.ok) {
                        return null;
                    }

                    const data = await response.json();
                    return data.token;
                } catch (e) {
                    console.error('Error al obtener token:', e);
                    return null;
                }
            },

            getFileIcon(filename) {
                const ext = filename.split('.').pop().toLowerCase();
                const icons = {
                    'pdf': '📄',
                    'jpg': '🖼️',
                    'jpeg': '🖼️',
                    'png': '🖼️',
                };
                return icons[ext] || '📎';
            }
        };
    }

    // Inicializar cuando esté listo el DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Las rutas de Alpine.js se cargarán automáticamente
        if (document.querySelector('[x-data="googleDrivePicker()"]')) {
            // Esperar a que Alpine esté listo
            Alpine.nextTick(() => {
                const element = document.querySelector('[x-data="googleDrivePicker()"]');
                if (element.__x) {
                    element.__x.init();
                }
            });
        }
    });
</script>
