<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agregar Personal - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Editar Personal
                </h2>
            </div>
        </header>

        <main>

    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 py-12 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 24px 24px;"></div>
        </div>
        <div class="relative max-w-3xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-amber-400 font-semibold text-sm uppercase tracking-widest">Gestión de Personal</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mb-2">Editar Registro de Personal</h1>
            <p class="text-slate-300 text-sm">Modifica los datos del miembro del equipo en el sistema. Deja la contraseña en blanco si no deseas cambiarla.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
        <div class="max-w-3xl mx-auto">

            @if(session('exito'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-green-800 font-medium text-sm">{{ session('exito') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-800 font-medium text-sm">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
                <form action="{{ route('personal.update', $personal->numero_empleado) }}" method="POST" x-data="{ showPass: false, showPass2: false }">
                    @csrf
                    @method('PUT')

                    <!-- Datos Personales Section -->
                    <div class="mb-8">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide pb-3 border-b-2 border-slate-100 mb-6">
                            👤 Datos Personales
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Número de Empleado -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Número de empleado
                                </label>
                                <input type="text" name="numero_empleado" value="{{ old('numero_empleado', $personal->numero_empleado) }}"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-slate-500 bg-slate-100 focus:outline-none" />
                            </div>

                            <!-- Nombre -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Nombre(s) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nombre" value="{{ old('nombre', $personal->nombre) }}"
                                    placeholder="Ej. Juan Carlos"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition @error('nombre') border-red-500 @enderror" />
                                @error('nombre')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Apellido Paterno -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Apellido paterno <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="apellido_paterno" value="{{ old('apellido_paterno', $personal->apellido_paterno) }}"
                                    placeholder="Ej. García"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition @error('apellido_paterno') border-red-500 @enderror" />
                                @error('apellido_paterno')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Apellido Materno -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Apellido materno
                                </label>
                                <input type="text" name="apellido_materno" value="{{ old('apellido_materno', $personal->apellido_materno) }}"
                                    placeholder="Ej. López"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition" />
                            </div>

                            <!-- Rol -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Rol <span class="text-red-500">*</span>
                                </label>
                                <select name="fk_rol"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition @error('fk_rol') border-red-500 @enderror">
                                    <option value="">📋 Selecciona un rol...</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id_rol }}" {{ old('fk_rol', $personal->fk_rol) == $rol->id_rol ? 'selected' : '' }}>
                                            {{ $rol->nombre_rol }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fk_rol')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Acceso al Sistema Section -->
                    <div class="mb-8">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wide pb-3 border-b-2 border-slate-100 mb-6">
                            🔐 Acceso al Sistema
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Correo -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Correo institucional <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" value="{{ old('email', $personal->user->email ?? '') }}"
                                    placeholder="nombre@tectepic.edu.mx"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition @error('email') border-red-500 @enderror" />
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Contraseña -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Contraseña (dejar en blanco para no cambiar)
                                </label>
                                <div class="relative">
                                    <input :type="showPass ? 'text' : 'password'" name="password"
                                        placeholder="Mínimo 8 caracteres"
                                        class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition @error('password') border-red-500 @enderror" />
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700 transition"
                                        @click="showPass = !showPass">
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
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2">
                                    Confirmar contraseña
                                </label>
                                <div class="relative">
                                    <input :type="showPass2 ? 'text' : 'password'" name="password_confirmation"
                                        placeholder="Repite la contraseña"
                                        class="w-full px-4 py-2.5 pr-10 border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent transition" />
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700 transition"
                                        @click="showPass2 = !showPass2">
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
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6 border-t border-slate-200">
                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-lg transition duration-200 ease-in flex items-center justify-center gap-2 shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        </main>
    </div>
</body>
</html>