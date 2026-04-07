<!-- Google Vinculación -->
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            🔐 Vinculación con Google
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Conecta tu cuenta conGoogle para iniciar sesión más rápidamente y sincronizar datos.
        </p>
    </header>

    <div class="mt-6 space-y-6">
        @if ($user->google_id)
            <!-- Google Ya Vinculado -->
            <div class="bg-green-50 border border-green-300 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <div class="text-3xl">✅</div>
                    <div class="flex-1">
                        <p class="font-medium text-green-900">Tu cuenta está vinculada con Google</p>
                        <p class="text-sm text-green-700 mt-1">
                            Google ID: <code class="bg-white px-2 py-1 rounded text-xs font-mono">{{ $user->google_id }}</code>
                        </p>
                        <p class="text-sm text-green-700 mt-2">
                            Puedes iniciar sesión con Google y tu información está sincronizada.
                        </p>
                        
                        <div class="mt-4 flex gap-2">
                            <form action="{{ route('profile.google-disconnect') }}" method="POST" class="inline">
                                @csrf
                                <button 
                                    type="submit" 
                                    onclick="return confirm('¿Estás seguro? Perderás acceso rápido con Google y tu avatar.')"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"
                                >
                                    🔓 Desvincular de Google
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Google No Vinculado -->
            <div class="bg-gray-50 border border-gray-300 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <div class="text-3xl">🔗</div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Tu cuenta NO está vinculada con Google</p>
                        <p class="text-sm text-gray-700 mt-1">
                            Vincular tu cuenta te permitirá:
                        </p>
                        <ul class="text-sm text-gray-700 list-disc list-inside mt-2 space-y-1">
                            <li>✨ Iniciar sesión directamente con Google</li>
                            <li>👤 Usar tu avatar de Google automáticamente</li>
                            <li>📧 Sincronizar tu información de perfil</li>
                            <li>🔐 Seguridad adicional con 2FA de Google</li>
                        </ul>

                        <div class="mt-4">
                            <a 
                                href="{{ route('auth.google') }}"
                                class="inline-flex items-center px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"
                            >
                                🌐 Vincular con Google Ahora
                            </a>
                        </div>

                        <p class="text-xs text-gray-500 mt-3">
                            Serás redirigido a Google para autorizar la vinculación.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
