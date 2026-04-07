<!-- Información Personal Mejorada -->
<section id="rectification-form">
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

        <!-- Email (Readonly si viene de Google) -->
        <div>
            <x-input-label for="email" :value="__('📧 Correo Electrónico')" />
            <x-text-input 
                id="email" 
                name="email" 
                type="email" 
                class="mt-1 block w-full" 
                :value="old('email', $user->email)" 
                {{ $user->google_id ? 'readonly disabled' : 'required' }}
                autocomplete="email"
            />
            @if ($user->google_id)
                <p class="text-xs text-blue-600 mt-1">✓ Sincronizado con Google</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <!-- Nombre (Readonly si existe) -->
        <div>
            <x-input-label for="display_name" :value="__('👤 Nombre Completo')" />
            <x-text-input 
                id="display_name" 
                name="display_name" 
                type="text" 
                class="mt-1 block w-full" 
                :value="old('display_name', $user->display_name ?? ($user->personal?->nombre_completo ?? $user->beneficiario?->nombre_completo ?? ''))" 
                readonly 
                disabled 
            />
            <p class="text-xs text-gray-500 mt-1">⚠️ Este campo se completa automáticamente según tu tipo de usuario</p>
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
                        <p class="text-xs text-green-600">RFC</p>
                        <p class="font-mono text-sm">{{ $user->personal->rfc ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-600">Número de Empleado</p>
                        <p class="text-sm">{{ $user->personal->numero_empleado ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-600">Cargo</p>
                        <p class="text-sm">{{ $user->personal->cargo ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-600">Departamento</p>
                        <p class="text-sm">{{ $user->personal->departamento ?? '—' }}</p>
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

        <!-- Botones de Acción -->
        <div class="flex items-center gap-4 pt-4 border-t">
            <x-primary-button>💾 Guardar Cambios</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 flex items-center gap-1"
                >✅ Guardado correctamente</p>
            @endif
        </div>
    </form>
</section>
