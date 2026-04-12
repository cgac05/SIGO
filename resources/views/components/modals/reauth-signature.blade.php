{{-- Modal de Re-autenticación para Firma Directiva --}}
@props(['solicitudId' => null, 'onlyPassword' => false])

<div id="modal-reauth-signature"
     x-data="reauthSignature()"
     x-show="open"
     style="position: fixed; inset: 0; z-index: 9998; align-items: center; justify-content: center; padding: 1rem;"
     @keydown.escape="closeModal()"
     class="transition-opacity duration-300"
     :class="{ 'opacity-0 pointer-events-none': !open, 'opacity-100': open }">

    {{-- Fondo bloqueante --}}
    <div style="position: fixed; inset: 0; background: rgba(10, 20, 50, 0.75); backdrop-filter: blur(6px);"
         @click="closeModal()"></div>

    {{-- Caja del modal --}}
    <div style="position: relative; background: #fff; border-radius: 20px; width: 100%; max-width: 500px; box-shadow: 0 24px 64px rgba(10, 20, 50, 0.30); overflow: hidden;"
         @click.stop
         class="transform transition-transform duration-300"
         :class="{ 'scale-95': !open, 'scale-100': open }">

        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #0f2044, #1a4a8a); padding: 1.75rem 2rem 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <div style="background: rgba(255, 255, 255, 0.15); border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 22px; height: 22px; color: #06b6d4;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <div>
                    <h2 style="color: #fff; font-size: 1.1rem; font-weight: 800; margin: 0;">Verificación de identidad</h2>
                    <p style="color: #93c5fd; font-size: 0.8rem; margin: 0;">Re-autenticación requerida para firmar</p>
                </div>
            </div>
        </div>

        {{-- Cuerpo --}}
        <div style="padding: 1.75rem 2rem;">

            {{-- Avisos de error --}}
            <template x-if="error">
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="error"></span>
                </div>
            </template>

            {{-- Información de solicitud --}}
            <div style="background: #f0f9ff; border-left: 4px solid #06b6d4; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.5rem;">
                <p style="color: #0369a1; font-size: 0.85rem; font-weight: 600; margin: 0;">
                    <template x-if="solicitudId">
                        Está por firmar la solicitud <strong x-text="solicitudId"></strong>
                    </template>
                    <template x-if="!solicitudId">
                        Debe verificar su identidad para continuar
                    </template>
                </p>
            </div>

            <form @submit.prevent="submit()" style="display: flex; flex-direction: column; gap: 1rem;">

                {{-- Contraseña --}}
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">
                        Contraseña <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="position: relative;">
                        <input
                            type="password"
                            x-ref="password"
                            x-model="form.password"
                            placeholder="Ingresa tu contraseña"
                            required
                            style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; font-family: 'Courier New', monospace;"
                            @focus="$el.style.borderColor = '#06b6d4'"
                            @blur="$el.style.borderColor = '#e2e8f0'"
                            @input="error = null">
                        <svg style="position: absolute; left: 0.75rem; top: 0.75rem; width: 18px; height: 18px; color: #94a3b8; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                </div>

                {{-- Código OTP (si está habilitado 2FA) --}}
                <template x-if="requires2fa && !onlyPassword">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem;">
                            Código de verificación 2FA <span style="color: #ef4444;">*</span>
                        </label>
                        <p style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.5rem;">
                            Se envió un código a tu correo electrónico o aplicación autenticadora.
                        </p>
                        <input
                            type="text"
                            x-model="form.otp"
                            placeholder="000000"
                            maxlength="6"
                            inputmode="numeric"
                            style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1.25rem; font-weight: bold; text-align: center; letter-spacing: 0.5em; transition: all 0.3s;"
                            @focus="$el.style.borderColor = '#06b6d4'"
                            @blur="$el.style.borderColor = '#e2e8f0'"
                            @input="form.otp = $el.value.replace(/[^0-9]/g, ''); error = null">
                    </div>
                </template>

                {{-- Info de seguridad --}}
                <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.8rem; color: #92400e; display: flex; gap: 0.5rem;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0; margin-top: 0.15rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p style="margin: 0;">
                        Esta acción es auditada. Tu firma será registrada de forma permanente en el sistema para cumplir con normativas LGPDP.
                    </p>
                </div>

                {{-- Botones --}}
                <div style="display: flex; gap: 0.75rem; padding-top: 0.5rem;">
                    <button
                        type="button"
                        @click="closeModal()"
                        style="flex: 1; padding: 0.75rem; border: 2px solid #e2e8f0; background: #f8fafc; color: #475569; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;"
                        @mouseenter="$el.style.background = '#f1f5f9'"
                        @mouseleave="$el.style.background = '#f8fafc'">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="loading"
                        style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #0f2044, #1a4a8a); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                        :class="{ 'opacity-50 cursor-not-allowed': loading }"
                        @mouseenter="!loading && ($el.style.filter = 'brightness(1.1)')"
                        @mouseleave="!loading && ($el.style.filter = 'brightness(1)')">
                        <template x-if="!loading">
                            <span>Verificar identidad</span>
                        </template>
                        <template x-if="loading">
                            <svg class="animate-spin" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.25" stroke-width="3"/>
                                <path d="M4 12a8 8 0 018-8" stroke-linecap="round" stroke-width="3"/>
                            </svg>
                            <span>Verificando...</span>
                        </template>
                    </button>
                </div>

            </form>

            {{-- Link olvidaste contraseña --}}
            <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <a href="{{ route('password.request') }}" style="color: #0369a1; font-size: 0.85rem; font-weight: 500; text-decoration: none;">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

        </div>

    </div>

</div>

<script>
    function reauthSignature() {
        return {
            open: false,
            loading: false,
            error: null,
            requires2fa: false,
            onlyPassword: {{ $onlyPassword ? 'true' : 'false' }},
            solicitudId: {{ $solicitudId ? "'" . $solicitudId . "'" : 'null' }},
            form: {
                password: '',
                otp: '',
            },

            openModal() {
                this.open = true;
                this.form = { password: '', otp: '' };
                this.error = null;
                setTimeout(() => this.$refs.password?.focus(), 100);
            },

            closeModal() {
                this.open = false;
                this.form = { password: '', otp: '' };
                this.error = null;
                this.loading = false;
            },

            async submit() {
                if (this.loading) return;
                this.error = null;
                this.loading = true;

                try {
                    const response = await fetch('{{ route("auth.reauth-verify") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                        },
                        body: JSON.stringify({
                            password: this.form.password,
                            otp: this.form.otp || null,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.error = data.message || 'Error en la verificación. Intenta de nuevo.';
                        return;
                    }

                    // Éxito: ejecutar callback
                    if (window.onReauthSuccess && typeof window.onReauthSuccess === 'function') {
                        window.onReauthSuccess(data);
                    }

                    this.closeModal();

                } catch (err) {
                    this.error = 'Error de conexión. Intenta de nuevo.';
                    console.error('Reauth error:', err);
                } finally {
                    this.loading = false;
                }
            },
        };
    }

    // Helper global para abrir modal desde cualquier lugar
    window.openReauthModal = function() {
        const modal = document.getElementById('modal-reauth-signature');
        if (modal && modal.__alpine) {
            modal.__alpine.openModal();
        }
    };
</script>
