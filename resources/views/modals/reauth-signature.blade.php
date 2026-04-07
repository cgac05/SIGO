<!-- 
    Modal de Re-autenticación para Firma Electrónica
    Fase 8: Sistema de Firma Digital Directiva
    Propósito: Validar identidad del directivo antes de firmar/autorizar solicitudes
    Características: Contraseña + 2FA (OTP opcional), efectos visuales, validaciones
-->
<div x-data="reauthModal()" x-cloak>
    <!-- Backdrop -->
    <div x-show="open" 
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm transition-opacity" 
         @click="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    <!-- Modal -->
    <div x-show="open"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-white animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Re-autenticación Requerida</h3>
                        <p class="text-green-100 text-sm">Verifica tu identidad para continuar</p>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <form @submit.prevent="handleSubmit" class="px-6 py-6 space-y-4">
                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password"
                            x-model="form.password"
                            @keydown.enter="handleSubmit"
                            placeholder="Ingresa tu contraseña"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition"
                            :class="{ 'border-red-500 focus:ring-red-500': errors.password }">
                    </div>
                    <template x-if="errors.password">
                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="errors.password"></span>
                        </p>
                    </template>
                </div>

                <!-- 2FA OTP Input (if required) -->
                <template x-if="requires2fa">
                    <div>
                        <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">
                            Código de Verificación (2FA)
                            <span class="text-xs text-gray-500">(Google Authenticator, Authy)</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="otp"
                                x-model="form.otp"
                                @keydown.enter="handleSubmit"
                                placeholder="000000"
                                maxlength="6"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition tracking-wider text-center text-lg font-mono"
                                :class="{ 'border-red-500 focus:ring-red-500': errors.otp }">
                        </div>
                        <template x-if="errors.otp">
                            <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <span x-text="errors.otp"></span>
                            </p>
                        </template>
                    </div>
                </template>

                <!-- General Error Message -->
                <template x-if="errors.general">
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-700 flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="errors.general"></span>
                        </p>
                    </div>
                </template>

                <!-- Info Message about Verified Identity -->
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-700">
                        <strong>Nota:</strong> Tu identidad será verificada de forma segura. Se guardará un registro de auditoría de este acceso.
                    </p>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-2">
                    <button 
                        type="button"
                        @click="open = false"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button 
                        type="submit"
                        :disabled="isLoading"
                        class="flex-1 px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition flex items-center justify-center gap-2">
                        <template x-if="!isLoading">
                            <span>Verificar</span>
                        </template>
                        <template x-if="isLoading">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <span>Verificando...</span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Alpine.js Component: reauthModal()
 * Maneja la lógica del modal de re-autenticación
 * Incluye validaciones, 2FA, y manejo de errores
 */
function reauthModal() {
    return {
        open: false,
        isLoading: false,
        requires2fa: false,
        form: {
            password: '',
            otp: ''
        },
        errors: {
            password: '',
            otp: '',
            general: ''
        },

        openModal() {
            this.open = true;
            this.resetForm();
            // Focus password field
            this.$nextTick(() => {
                document.getElementById('password')?.focus();
            });
        },

        closeModal() {
            this.open = false;
            this.resetForm();
        },

        resetForm() {
            this.form = { password: '', otp: '' };
            this.errors = { password: '', otp: '', general: '' };
            this.requires2fa = false;
            this.isLoading = false;
        },

        async handleSubmit() {
            // Validaciones de cliente
            this.errors.password = '';
            this.errors.otp = '';
            this.errors.general = '';

            if (!this.form.password) {
                this.errors.password = 'La contraseña es requerida';
                return;
            }

            if (this.form.password.length < 6) {
                this.errors.password = 'La contraseña debe tener al menos 6 caracteres';
                return;
            }

            if (this.requires2fa && !this.form.otp) {
                this.errors.otp = 'El código de verificación es requerido';
                return;
            }

            if (this.requires2fa && this.form.otp.length !== 6) {
                this.errors.otp = 'El código debe tener 6 dígitos';
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/auth/reauth-verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        password: this.form.password,
                        otp: this.form.otp || null
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Guardar el token de re-autenticación
                    window.reauthToken = data.reauth_token;
                    window.reauthUserData = data.usuario;

                    // Disparar evento personalizado para que otras partes de la app lo usen
                    window.dispatchEvent(new CustomEvent('reauthSuccess', { 
                        detail: { token: data.reauth_token, usuario: data.usuario } 
                    }));

                    // Mostrar notificación de éxito
                    if (window.showNotification) {
                        window.showNotification('success', 'Identidad verificada correctamente');
                    }

                    // Cerrar modal después de 500ms
                    setTimeout(() => {
                        this.closeModal();
                    }, 500);
                } else {
                    // Si requiere 2FA y aún no lo hemos enviado
                    if (!this.requires2fa && data.requires_2fa) {
                        this.requires2fa = true;
                        this.$nextTick(() => {
                            document.getElementById('otp')?.focus();
                        });
                    } else {
                        this.errors.general = data.message || 'Error en la verificación. Intenta de nuevo.';
                    }
                }
            } catch (error) {
                console.error('Error de re-autenticación:', error);
                this.errors.general = 'Error de conexión. Por favor, intenta de nuevo.';
            } finally {
                this.isLoading = false;
            }
        }
    };
}

// Helper global para abrir el modal desde cualquier parte
window.openReauthModal = function() {
    // Buscar el componente Alpine en el DOM
    const element = document.querySelector('[x-data="reauthModal()"]');
    if (element && element.__x) {
        element.__x.openModal();
    }
};

// Helper para esperar re-autenticación exitosa
window.waitForReauth = function() {
    return new Promise((resolve) => {
        const listener = (event) => {
            window.removeEventListener('reauthSuccess', listener);
            resolve(event.detail);
        };
        window.addEventListener('reauthSuccess', listener);
    });
};
</script>
