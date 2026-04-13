<!-- Seguridad y Sesiones -->
<section>
    @php($activeSessions = collect($activeSessions ?? []))

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            🔐 Seguridad y Sesiones
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Gestiona la seguridad de tu cuenta y las sesiones activas.
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <!-- Cambiar Contraseña -->
        @if ($user->password_hash)
            <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4 rounded">
                <h3 class="font-medium text-yellow-900">🔑 Cambiar Contraseña</h3>
                <p class="text-sm text-yellow-700 mt-1">
                    Cambia tu contraseña regularmente para mantener tu cuenta segura.
                </p>
                <a href="#password-section" onclick="document.getElementById('password-section').scrollIntoView({ behavior: 'smooth' })" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 mt-3">
                    🔐 Cambiar Contraseña
                </a>
            </div>
        @endif

        <!-- Estado de 2FA -->
        <div class="border-l-4 border-purple-400 bg-purple-50 p-4 rounded">
            <h3 class="font-medium text-purple-900">📱 Autenticación de Dos Factores (2FA)</h3>
            @if ($user->two_factor_enabled)
                <div class="flex items-center gap-2 mt-2 text-purple-700">
                    <span class="text-lg">✅</span>
                    <span>2FA activada en tu cuenta</span>
                </div>
                <p class="text-sm text-purple-700 mt-2">
                    Tu cuenta está protegida con autenticación de dos factores.
                </p>
                <form action="{{ route('profile.disable-2fa') }}" method="POST" class="inline mt-2">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Desactivar 2FA? Tu cuenta será menos segura.')" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                        🔓 Desactivar 2FA
                    </button>
                </form>
            @else
                <p class="text-sm text-purple-700 mt-2">
                    ⚠️ 2FA no está activada. Se recomienda activarla para mayor seguridad.
                </p>
                <form action="{{ route('profile.enable-2fa') }}" method="POST" class="inline mt-2">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                        📱 Activar 2FA
                    </button>
                </form>
                <p class="text-xs text-purple-600 mt-2 font-medium">
                    (Nota: 2FA es experimental, usa con cuidado)
                </p>
            @endif
        </div>

        <!-- Sesiones Activas -->
        <div class="border-l-4 border-blue-400 bg-blue-50 p-4 rounded">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-medium text-blue-900">💻 Sesiones Activas</h3>
                    <p class="text-sm text-blue-700 mt-1">
                        Aquí se muestran los dispositivos y navegadores con sesión abierta.
                    </p>
                </div>

                <span class="inline-flex items-center rounded-full border border-blue-200 bg-white px-3 py-1 text-xs font-semibold text-blue-700">
                    {{ $activeSessions->count() }} activas
                </span>
            </div>
            
            <div class="mt-4 space-y-3">
                @forelse($activeSessions as $session)
                    <div class="bg-white border border-blue-200 rounded p-3 flex justify-between items-start gap-4 shadow-sm">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium text-gray-900">{{ $session['device_type'] }} · {{ $session['browser'] }}</p>

                                @if($session['is_current'])
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                        Sesión actual
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-gray-600 mt-1">
                                {{ $session['user_agent_preview'] }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1">
                                {{ $session['summary'] }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1">
                                IP: {{ $session['ip_address'] }}
                            </p>
                            <p class="text-xs text-blue-600 mt-1">
                                Última actividad: {{ $session['last_activity_human'] }}
                            </p>
                        </div>

                        <span class="text-lg">{{ $session['is_current'] ? '✅' : '💠' }}</span>
                    </div>
                @empty
                    <div class="bg-white border border-dashed border-blue-200 rounded p-3 text-sm text-blue-700">
                        No se pudieron cargar sesiones activas.
                    </div>
                @endforelse

                <form action="{{ route('profile.logout-all-sessions') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Cerrar todas las demás sesiones? Tendrás que volver a iniciar sesión en otros dispositivos.');" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                        🔒 Cerrar demás sesiones
                    </button>
                </form>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="border-l-4 border-green-400 bg-green-50 p-4 rounded">
            <h3 class="font-medium text-green-900">📊 Actividad Reciente</h3>
            <p class="text-sm text-green-700 mt-1">
                Último acceso: {{ $user->ultima_conexion?->format('d/m/Y H:i:s') ?? 'Nunca' }}
            </p>
            <p class="text-sm text-green-700 mt-2">
                ℹ️ Si ves actividad sospechosa, <a href="mailto:seguridad@injuve.gob.mx" class="underline font-medium">reporta a seguridad</a>
            </p>
        </div>
    </div>
</section>
