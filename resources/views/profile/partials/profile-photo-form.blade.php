<!-- Foto de Perfil -->
<section>
    @php($avatarUrl = $user->avatar_url)
    @php($avatarFallbackUrl = $user->avatar_placeholder_url)

    <header>
        <h2 class="text-lg font-medium text-gray-900">
            🖼️ Foto de Perfil
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Actualiza tu foto de perfil. Se mostrará en toda la plataforma.
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <!-- Foto Actual -->
        <div class="flex items-center gap-6">
            <div>
                @if ($user->google_avatar)
                    <img
                        src="{{ $avatarUrl }}"
                        alt="Avatar"
                        class="h-24 w-24 rounded-full border-2 border-blue-500 object-cover"
                        onerror="this.onerror=null;this.src='{{ $avatarFallbackUrl }}';"
                    >
                    <p class="text-xs text-gray-500 mt-2">👤 Google Avatar</p>
                @elseif ($user->foto_perfil)
                    <img
                        src="{{ $avatarUrl }}"
                        alt="Foto"
                        class="h-24 w-24 rounded-full border-2 border-gray-300 object-cover"
                        onerror="this.onerror=null;this.src='{{ $avatarFallbackUrl }}';"
                    >
                    <p class="text-xs text-gray-500 mt-2">📷 Foto Local</p>
                @else
                    <img
                        src="{{ $avatarFallbackUrl }}"
                        alt="Sin foto"
                        class="h-24 w-24 rounded-full border-2 border-gray-300 object-cover"
                    >
                    <p class="text-xs text-gray-500 mt-2">😌 Sin foto</p>
                @endif
            </div>

            <div class="flex-1">
                @if ($user->google_id)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-blue-900">✅ Vinculado con Google</p>
                        <p class="text-xs text-blue-700 mt-1">Tu foto viene de Google. Para cambiarla:</p>
                        <button type="button" onclick="showGooglePhotoOptions()" class="text-xs text-blue-600 hover:text-blue-800 underline mt-2">
                            Usa la foto de Google
                        </button>
                        <span class="text-xs text-gray-500 mx-2">o</span>
                        <form action="{{ route('profile.google-disconnect') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" onclick="return confirm('¿Desvincularte de Google? Perderás acceso al avatar.')" class="text-xs text-red-600 hover:text-red-800 underline">
                                Desvincúlate de Google
                            </button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('profile.upload-photo') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        
                        <div>
                            <label for="foto_perfil" class="block text-sm font-medium text-gray-700">
                                📤 Selecciona una foto (máx 5 MB)
                            </label>
                            <input 
                                type="file" 
                                id="foto_perfil" 
                                name="foto_perfil" 
                                accept="image/jpeg,image/png,image/gif"
                                onchange="previewPhoto(this)"
                                class="mt-1 block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4 file:rounded-full
                                    file:border-0 file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100"
                            >
                            <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG, GIF</p>
                        </div>

                        <div id="photoPreview" class="hidden">
                            <img id="previewImg" class="h-20 w-20 rounded-lg border border-blue-300">
                        </div>

                        @error('foto_perfil')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            💾 Guardar Foto
                        </button>
                    </form>

                    <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <p class="text-xs text-amber-800">
                            💡 <strong>Consejo:</strong> Puedes vincular tu cuenta con Google para usar tu avatar de Google automáticamente.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('photoPreview').classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function showGooglePhotoOptions() {
    alert('Tu foto de Google está siendo utilizada. Para cambiarla, desvincula tu cuenta.');
}
</script>
