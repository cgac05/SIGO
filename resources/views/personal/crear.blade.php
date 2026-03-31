<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Agregar Personal
        </h2>
    </x-slot>

    <link href="https://fonts.bunny.net/css?family=sora:400,600,700,800&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --navy:  #0f2044;
            --blue:  #1a4a8a;
            --light: #eef3fb;
        }
        body { font-family: 'Sora', sans-serif; }

        .hero-panel {
            background: linear-gradient(135deg, var(--navy) 0%, var(--blue) 60%, #2563eb 100%);
            position: relative; overflow: hidden;
        }
        .hero-panel::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(circle at 80% 20%, rgba(232,160,32,.15) 0%, transparent 55%);
            pointer-events: none;
        }
        .hero-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 24px 24px; pointer-events: none;
        }
        .form-card {
            background: #fff; border-radius: 20px;
            box-shadow: 0 4px 24px rgba(15,32,68,.10);
            border: 1.5px solid #e2e8f0; padding: 2rem 2.5rem;
        }
        .field-group { display: flex; flex-direction: column; gap: .4rem; }
        .field-label {
            font-size: .8rem; font-weight: 700; color: #475569;
            text-transform: uppercase; letter-spacing: .05em;
        }
        .field-label span { color: #ef4444; margin-left: 2px; }
        .field-input {
            width: 100%; padding: .65rem 1rem;
            border: 1.5px solid #cbd5e1; border-radius: 10px;
            font-size: .9rem; color: #1e293b;
            transition: border-color .2s, box-shadow .2s;
            outline: none; background: #f8fafc;
        }
        .field-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(26,74,138,.12);
            background: #fff;
        }
        .field-input.error { border-color: #ef4444; }
        .field-error { font-size: .78rem; color: #ef4444; margin-top: .2rem; }
        select.field-input { cursor: pointer; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
        .section-title {
            font-size: .75rem; font-weight: 700; color: var(--blue);
            text-transform: uppercase; letter-spacing: .08em;
            padding-bottom: .5rem;
            border-bottom: 2px solid var(--light);
            margin-bottom: 1rem;
        }
        .btn-primary {
            width: 100%; padding: .9rem;
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: #fff; border: none; border-radius: 12px;
            font-size: 1rem; font-weight: 700; cursor: pointer;
            letter-spacing: .03em; transition: opacity .2s, transform .15s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
        .alert-success {
            background: #f0fdf4; border: 1px solid #bbf7d0;
            color: #166534; border-radius: 12px; padding: .9rem 1.2rem;
            display: flex; align-items: center; gap: .75rem;
            font-size: .9rem; font-weight: 600; margin-bottom: 1.5rem;
        }
        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca;
            color: #991b1b; border-radius: 12px; padding: .9rem 1.2rem;
            display: flex; align-items: center; gap: .75rem;
            font-size: .9rem; font-weight: 600; margin-bottom: 1.5rem;
        }
        .pass-wrap { position: relative; }
        .pass-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #94a3b8;
            padding: 0; display: flex; align-items: center;
        }
        .pass-toggle:hover { color: var(--blue); }
    </style>

    <div class="hero-panel py-8 px-4 md:px-8">
        <div class="hero-dots"></div>
        <div class="max-w-3xl mx-auto relative z-10">
            <div class="flex items-center gap-3 mb-1">
                <svg class="w-6 h-6 text-yellow-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-yellow-300 font-semibold text-sm uppercase tracking-widest">Gestión de Personal</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mb-1">Agregar Nuevo Personal</h1>
            <p class="text-blue-200 text-sm">Completa el formulario para registrar un nuevo miembro del equipo en el sistema.</p>
        </div>
    </div>

    <div class="py-10 px-4 md:px-8 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto">

            @if(session('exito'))
            <div class="alert-success">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('exito') }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert-error">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif

            <div class="form-card">
                <form action="{{ route('personal.store') }}" method="POST"
                      x-data="{ showPass: false, showPass2: false }">
                    @csrf

                    <p class="section-title">Datos personales</p>
                    <div class="grid-2 mb-5">

                        <div class="field-group" style="grid-column: 1 / -1">
                            <label class="field-label">Número de empleado <span>*</span></label>
                            <input type="text" name="numero_empleado"
                                   value="{{ old('numero_empleado') }}"
                                   placeholder="Ej. EMP-001"
                                   class="field-input {{ $errors->has('numero_empleado') ? 'error' : '' }}"/>
                            @error('numero_empleado')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">Nombre(s) <span>*</span></label>
                            <input type="text" name="nombre"
                                   value="{{ old('nombre') }}"
                                   placeholder="Ej. Juan Carlos"
                                   class="field-input {{ $errors->has('nombre') ? 'error' : '' }}"/>
                            @error('nombre')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">Apellido paterno <span>*</span></label>
                            <input type="text" name="apellido_paterno"
                                   value="{{ old('apellido_paterno') }}"
                                   placeholder="Ej. García"
                                   class="field-input {{ $errors->has('apellido_paterno') ? 'error' : '' }}"/>
                            @error('apellido_paterno')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">Apellido materno</label>
                            <input type="text" name="apellido_materno"
                                   value="{{ old('apellido_materno') }}"
                                   placeholder="Ej. López"
                                   class="field-input"/>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Rol <span>*</span></label>
                            <select name="fk_rol"
                                    class="field-input {{ $errors->has('fk_rol') ? 'error' : '' }}">
                                <option value="">Selecciona un rol...</option>
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->id_rol }}"
                                        {{ old('fk_rol') == $rol->id_rol ? 'selected' : '' }}>
                                        {{ $rol->nombre_rol }}
                                    </option>
                                @endforeach
                            </select>
                            @error('fk_rol')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <p class="section-title">Acceso al sistema</p>
                    <div class="grid-2 mb-6">

                        <div class="field-group" style="grid-column: 1 / -1">
                            <label class="field-label">Correo institucional <span>*</span></label>
                            <input type="email" name="email"
                                   value="{{ old('email') }}"
                                   placeholder="nombre@tectepic.edu.mx"
                                   class="field-input {{ $errors->has('email') ? 'error' : '' }}"/>
                            @error('email')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">Contraseña <span>*</span></label>
                            <div class="pass-wrap">
                                <input :type="showPass ? 'text' : 'password'"
                                       name="password"
                                       placeholder="Mínimo 8 caracteres"
                                       class="field-input {{ $errors->has('password') ? 'error' : '' }}"
                                       style="padding-right: 2.5rem"/>
                                <button type="button" class="pass-toggle" @click="showPass = !showPass">
                                    <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">Confirmar contraseña <span>*</span></label>
                            <div class="pass-wrap">
                                <input :type="showPass2 ? 'text' : 'password'"
                                       name="password_confirmation"
                                       placeholder="Repite la contraseña"
                                       class="field-input"
                                       style="padding-right: 2.5rem"/>
                                <button type="button" class="pass-toggle" @click="showPass2 = !showPass2">
                                    <svg x-show="!showPass2" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPass2" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="btn-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Registrar Personal
                    </button>

                </form>
            </div>
        </div>
    </div>

</x-app-layout>