<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Eliminar cuenta - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100">
    <div class="min-h-screen bg-slate-900/60 px-4 py-8 sm:px-6 lg:px-8">
        <main class="flex min-h-[calc(100vh-4rem)] items-center justify-center">
            <section class="w-full max-w-3xl overflow-hidden rounded-3xl border border-red-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.35)]">
                <div class="bg-gradient-to-r from-red-700 via-red-600 to-orange-500 px-6 py-8 sm:px-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-red-100">Zona de peligro</p>
                    <h1 class="mt-3 text-3xl font-bold text-white">Eliminar cuenta</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-red-50">
                        Esta acción no borra físicamente el registro. Solo desactiva tu cuenta cambiando <span class="font-semibold">Usuarios.activo</span> a <span class="font-semibold">0</span>.
                    </p>
                </div>

                <div class="grid gap-8 px-6 py-8 sm:px-10 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-slate-900">Confirma tu identidad</h2>
                        <p class="text-sm leading-6 text-slate-600">
                            Para proteger tu cuenta, primero debemos verificar que eres la persona propietaria antes de desactivarla.
                        </p>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-sm font-medium text-slate-900">Cuenta actual</p>
                            <dl class="mt-4 space-y-3 text-sm text-slate-700">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <dt class="font-medium text-slate-500">Correo</dt>
                                    <dd>{{ $user?->email }}</dd>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <dt class="font-medium text-slate-500">Acceso</dt>
                                    <dd>{{ filled($user?->password) ? 'Contraseña' : 'Google' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-900">Importante</p>
                            <p class="mt-1 text-sm text-amber-800">
                                Al continuar, tu cuenta quedará inactiva y se cerrará tu sesión.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        @if (filled($user?->password))
                            <form method="post" action="{{ route('profile.destroy') }}" class="space-y-5">
                                @csrf
                                @method('delete')

                                <div>
                                    <x-input-label for="password" value="Contraseña actual" />
                                    <x-text-input
                                        id="password"
                                        name="password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        placeholder="Escribe tu contraseña"
                                        autocomplete="current-password"
                                    />
                                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                                </div>

                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        Cancelar
                                    </a>
                                    <x-danger-button>
                                        Desactivar cuenta
                                    </x-danger-button>
                                </div>
                            </form>
                        @else
                            <div class="space-y-5">
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-medium text-amber-900">Esta cuenta no tiene contraseña local</p>
                                    <p class="mt-1 text-sm text-amber-800">
                                        Confirma tu identidad iniciando sesión con Google para desactivar la cuenta.
                                    </p>
                                </div>

                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        Cancelar
                                    </a>
                                    <a href="{{ route('profile.delete-account.google') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                        Iniciar sesión con Google
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>