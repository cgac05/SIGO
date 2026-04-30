<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Eliminar cuenta') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Antes de continuar, descargue cualquier información que desee conservar.') }}
        </p>
    </header>

    <x-danger-button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        {{ __('Eliminar cuenta') }}
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable maxWidth="3xl">
        <div class="bg-gradient-to-r from-red-700 via-red-600 to-orange-500 px-6 py-6 sm:px-8">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-red-100">Zona de peligro</p>
            <h2 class="mt-2 text-2xl font-bold text-white">Eliminar cuenta</h2>
            <p class="mt-2 text-sm leading-6 text-red-50">
                Esta acción desactiva tu cuenta cambiando <span class="font-semibold">Usuarios.activo</span> a <span class="font-semibold">0</span>.
            </p>
        </div>

        <div class="grid gap-8 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Confirma tu identidad</h3>
                <p class="text-sm leading-6 text-gray-600">
                    Para proteger tu cuenta, primero debemos verificar que eres la persona propietaria antes de desactivarla.
                </p>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                    <p class="text-sm font-medium text-gray-900">Cuenta actual</p>
                    <dl class="mt-4 space-y-3 text-sm text-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <dt class="font-medium text-gray-500">Correo</dt>
                            <dd>{{ $user?->email }}</dd>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <dt class="font-medium text-gray-500">Acceso</dt>
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

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
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
                            <x-secondary-button x-on:click="$dispatch('close')">
                                Cancelar
                            </x-secondary-button>
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
                            <x-secondary-button x-on:click="$dispatch('close')">
                                Cancelar
                            </x-secondary-button>
                            <a href="{{ route('profile.delete-account.google') }}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Iniciar sesión con Google
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-modal>
</section>
