@if(session('forzar_cambio_password') || (Auth::check() && Auth::user()->tipo_usuario === 'personal' && Auth::user()->debe_cambiar_password))
<div id="modal-cambio-pass"
     style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem"
     x-data="{ showPass: false, showPass2: false }">

    {{-- Fondo bloqueante --}}
    <div style="position:fixed;inset:0;background:rgba(10,20,50,.75);backdrop-filter:blur(6px)"></div>

    {{-- Caja del modal --}}
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:440px;box-shadow:0 24px 64px rgba(10,20,50,.30);overflow:hidden">

        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#0f2044,#1a4a8a);padding:1.75rem 2rem 1.5rem">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
                <div style="background:rgba(255,255,255,.15);border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center">
                    <svg style="width:20px;height:20px;color:#fbbf24" fill="none" stroke="#fbbf24" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h2 style="color:#fff;font-size:1.1rem;font-weight:800;margin:0">Cambio de contraseña requerido</h2>
                    <p style="color:#93c5fd;font-size:.8rem;margin:0">Debes cambiarla antes de continuar</p>
                </div>
            </div>
        </div>

        {{-- Cuerpo --}}
        <div style="padding:1.75rem 2rem">

            @if(session('error_password'))
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:.75rem 1rem;font-size:.85rem;font-weight:600;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem">
                <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error_password') }}
            </div>
            @endif

            <p style="color:#475569;font-size:.85rem;margin-bottom:1.25rem;line-height:1.5">
                Por seguridad, es necesario que establezcas una nueva contraseña para tu cuenta antes de usar el sistema.
            </p>

            <form action="{{ route('password.forzar.update') }}" method="POST">
                @csrf

                {{-- Nueva contraseña --}}
                <div style="margin-bottom:1rem">
                    <label style="display:block;font-size:.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">
                        Nueva contraseña <span style="color:#ef4444">*</span>
                    </label>
                    <div style="position:relative">
                        <input :type="showPass ? 'text' : 'password'"
                               name="password"
                               placeholder="Mínimo 8 caracteres"
                               style="width:100%;padding:.65rem 2.5rem .65rem 1rem;border:1.5px solid #cbd5e1;border-radius:10px;font-size:.9rem;color:#1e293b;outline:none;background:#f8fafc;box-sizing:border-box"
                               required/>
                        <button type="button"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;display:flex"
                                @click="showPass = !showPass">
                            <svg x-show="!showPass" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" style="width:18px;height:18px;display:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span style="font-size:.78rem;color:#ef4444;margin-top:.2rem;display:block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Confirmar contraseña --}}
                <div style="margin-bottom:1.5rem">
                    <label style="display:block;font-size:.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">
                        Confirmar contraseña <span style="color:#ef4444">*</span>
                    </label>
                    <div style="position:relative">
                        <input :type="showPass2 ? 'text' : 'password'"
                               name="password_confirmation"
                               placeholder="Repite la contraseña"
                               style="width:100%;padding:.65rem 2.5rem .65rem 1rem;border:1.5px solid #cbd5e1;border-radius:10px;font-size:.9rem;color:#1e293b;outline:none;background:#f8fafc;box-sizing:border-box"
                               required/>
                        <button type="button"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;display:flex"
                                @click="showPass2 = !showPass2">
                            <svg x-show="!showPass2" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass2" style="width:18px;height:18px;display:none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        style="width:100%;padding:.9rem;background:linear-gradient(135deg,#0f2044,#1a4a8a);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem">
                    <svg style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Actualizar contraseña
                </button>

            </form>
        </div>
    </div>
</div>
@endif