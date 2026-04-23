<!-- Información Personal Mejorada -->
<section id="rectification-form">
    @php($isGoogleLinked = filled($user->google_id))
    @php($personalCargo = $user->personal?->role?->nombre_rol ?? '—')
    @php($personalPuesto = $user->personal?->puesto ?? '—')

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            👤 Información Personal
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Actualiza tu información de perfil. Estos datos son visibles en tu actividad en la plataforma.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Nombre Completo -->
        <div class="space-y-2">
            <x-input-label for="display_name" :value="__('👤 Nombre Completo')" />
            <div class="relative mt-1">
                <x-text-input 
                    id="display_name" 
                    name="display_name" 
                    type="text" 
                    class="block w-full pr-12 bg-gray-50 cursor-default" 
                    :value="old('display_name', $user->display_name ?? ($user->personal?->nombre_completo ?? $user->beneficiario?->nombre_completo ?? ''))" 
                    readonly
                    required
                    autocomplete="name"
                />
                <button
                    type="button"
                    data-edit-trigger="display_name"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 transition hover:text-gray-700"
                    aria-label="Editar nombre completo"
                    title="Editar nombre completo"
                >
                    <img src="{{ asset('images/lapiz.png') }}" alt="" class="h-5 w-5">
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Pulsa el lápiz para habilitar la edición del nombre.</p>
            <x-input-error class="mt-2" :messages="$errors->get('display_name')" />
            <div id="display_name-save-wrap" class="hidden pt-2">
                <div class="flex items-center gap-2">
                    <x-primary-button type="submit">Guardar nombre</x-primary-button>
                    <button
                        type="button"
                        data-cancel-edit="display_name"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Correo del usuario -->
        <div class="space-y-2">
            <x-input-label for="email" :value="__('📧 Correo Electrónico')" />
            <div class="relative mt-1">
                <x-text-input 
                    id="email" 
                    name="email"
                    type="email" 
                    class="block w-full pr-12 bg-gray-50 {{ $isGoogleLinked ? 'cursor-not-allowed border-blue-200 bg-blue-50 text-gray-600' : 'cursor-default' }}" 
                    :value="old('email', $user->email)" 
                    readonly
                    required
                    autocomplete="email"
                />
                @if ($isGoogleLinked)
                    <span
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-blue-600"
                        aria-hidden="true"
                        title="Correo administrado por Google"
                    >
                        🔒
                    </span>
                @else
                    <button
                        type="button"
                        data-edit-trigger="email"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 transition hover:text-gray-700"
                        aria-label="Editar correo electrónico"
                        title="Editar correo electrónico"
                    >
                        <img src="{{ asset('images/lapiz.png') }}" alt="" class="h-5 w-5">
                    </button>
                @endif
            </div>
            @if ($isGoogleLinked)
                <p class="text-xs text-blue-700 mt-1">La cuenta está vinculada con Google, por eso el correo queda bloqueado.</p>
            @else
                <p class="text-xs text-gray-500 mt-1">Pulsa el lápiz para habilitar la edición del correo.</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
            @unless ($isGoogleLinked)
                <div id="email-save-wrap" class="hidden pt-2">
                    <div class="flex items-center gap-2">
                        <x-primary-button type="submit">Guardar correo</x-primary-button>
                        <button
                            type="button"
                            data-cancel-edit="email"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            @endunless
        </div>

        <!-- Tipo de Usuario -->
        <div>
            <x-input-label for="tipo_usuario" :value="__('🏷️ Tipo de Usuario')" />
            <div class="mt-1 flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                @if ($user->isBeneficiario())
                    <span class="text-lg">📋</span>
                    <span class="text-gray-800">Beneficiario</span>
                @elseif ($user->isPersonal())
                    <span class="text-lg">👔</span>
                    <span class="text-gray-800">Personal {{ $user->personal?->cargo ? '(' . $user->personal->cargo . ')' : '' }}</span>
                @else
                    <span class="text-lg">🔧</span>
                    <span class="text-gray-800">{{ $user->tipo_usuario }}</span>
                @endif
            </div>
        </div>

        <!-- Información adicional según tipo -->
        @if ($user->isBeneficiario() && $user->beneficiario)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                <h4 class="font-medium text-blue-900">📋 Información del Beneficiario</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-blue-600">CURP</p>
                        <p class="font-mono text-sm">{{ $user->beneficiario->curp ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600">Fecha de Nacimiento</p>
                        <p class="text-sm">{{ $user->beneficiario->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600">Teléfono</p>
                        <p class="text-sm">{{ $user->beneficiario->telefono ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600">Sexo</p>
                        <p class="text-sm">{{ $user->beneficiario->sexo_label ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600">Edad</p>
                        <p class="text-sm">{{ $user->beneficiario->edad ?? '—' }} años</p>
                    </div>
                </div>
            </div>
        @elseif ($user->isPersonal() && $user->personal)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 space-y-3">
                <h4 class="font-medium text-green-900">👔 Información del Personal</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-green-600">Número de Empleado</p>
                        <p class="text-sm">{{ $user->personal->numero_empleado ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-600">Cargo</p>
                        <p class="text-sm">{{ $personalCargo }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-600">Puesto</p>
                        <p class="text-sm">{{ $personalPuesto }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Información de Cuenta -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
            <h4 class="font-medium text-gray-900">ℹ️ Información de Cuenta</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-600">🗓️ Fecha de Registro</p>
                    <p class="text-gray-800">{{ $user->fecha_creacion?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">⏱️ Última Conexión</p>
                    <p class="text-gray-800">
                        @if ($user->ultima_conexion)
                            {{ $user->ultima_conexion->diffForHumans() }}
                        @else
                            Nunca
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">🔐 Autenticación</p>
                    <p class="text-gray-800">
                        @if ($user->google_id)
                            <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">🌐 Google</span>
                        @endif
                        @if ($user->password_hash)
                            <span class="inline-block bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">🔑 Contraseña</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-600">✅ Estado</p>
                    <p class="text-gray-800">
                        @if ($user->activo)
                            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs">🟢 Activo</span>
                        @else
                            <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs">🔴 Inactivo</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        @if (session('status') === 'profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-green-600 flex items-center gap-1 pt-2 border-t"
            >✅ Guardado correctamente</p>
        @endif
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editableFields = [
            { key: 'display_name', saveWrapId: 'display_name-save-wrap' },
            { key: 'email', saveWrapId: 'email-save-wrap' },
        ];

        editableFields.forEach(({ key, saveWrapId }) => {
            const trigger = document.querySelector('[data-edit-trigger="' + key + '"]');
            const cancel = document.querySelector('[data-cancel-edit="' + key + '"]');
            const input = document.getElementById(key);
            const saveWrap = document.getElementById(saveWrapId);

            if (!trigger || !input || !saveWrap) {
                return;
            }

            input.dataset.originalValue = input.value;

            const enableEditing = () => {
                if (input.readOnly) {
                    input.readOnly = false;
                    input.classList.remove('bg-gray-50', 'cursor-default');
                    input.classList.add('bg-white', 'ring-1', 'ring-blue-200', 'cursor-text');
                    saveWrap.classList.remove('hidden');
                }

                input.focus();

                if (typeof input.select === 'function') {
                    input.select();
                }
            };

            const disableEditing = () => {
                input.value = input.dataset.originalValue ?? input.value;
                input.readOnly = true;
                input.classList.remove('bg-white', 'ring-1', 'ring-blue-200', 'cursor-text');
                input.classList.add('bg-gray-50', 'cursor-default');
                saveWrap.classList.add('hidden');
            };

            trigger.addEventListener('click', enableEditing);

            if (cancel) {
                cancel.addEventListener('click', disableEditing);
            }
        });
    });
</script>
