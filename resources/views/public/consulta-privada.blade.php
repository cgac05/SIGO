<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INJUVE - Consulta Privada de Documentos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="max-w-md w-full">
            <!-- Logo INJUVE -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <svg class="w-16 h-16 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">INJUVE</h1>
                <p class="text-gray-600 text-sm">Instituto Nayarita de la Juventud</p>
            </div>

            <!-- Card Principal -->
            <div class="bg-white rounded-lg shadow-2xl p-8 fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Consulta Privada</h2>
                <p class="text-gray-600 text-sm mb-6">Ingresa tu folio y clave para ver tus documentos</p>

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-800 text-sm font-semibold mb-2">Error de Acceso</p>
                        @foreach($errors->all() as $error)
                            <p class="text-red-700 text-xs">• {{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if(session('bloqueado'))
                    <div class="mb-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                        <p class="text-orange-800 text-sm font-semibold">⚠️ Acceso Bloqueado</p>
                        <p class="text-orange-700 text-xs mt-2">
                            Intentos fallidos excedidos. Contacta a INJUVE para desbloquear.
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('caso-a.verificar-acceso') }}" class="space-y-4">
                    @csrf

                    <!-- Entrada: Folio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Folio de Expediente
                        </label>
                        <input type="text" 
                               name="folio" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                               placeholder="Ej: 001-2026-TEP"
                               value="{{ old('folio') }}"
                               required
                               autofocus>
                        <p class="text-xs text-gray-500 mt-1">Se encuentra en tu ticket</p>
                    </div>

                    <!-- Entrada: Clave Privada -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Clave Privada
                        </label>
                        <input type="password" 
                               name="clave" 
                               id="claveInput"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="••••••••••••••"
                               required>
                        <div class="mt-2 flex items-center">
                            <input type="checkbox" id="mostrarClave" class="w-4 h-4 text-blue-600 rounded">
                            <label for="mostrarClave" class="ml-2 text-xs text-gray-600">Mostrar clave</label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Clave de 16 caracteres en tu ticket</p>
                    </div>

                    <!-- Botón de Envío -->
                    <button type="submit" class="w-full mt-6 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Acceder a Mis Documentos
                    </button>
                </form>

                <!-- Información Adicional -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-xs font-semibold text-blue-900 mb-2">ℹ️ ¿No tienes folio?</p>
                    <p class="text-xs text-blue-800">
                        Si no recibiste tu folio y clave, contacta a la oficina de INJUVE más cercana.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-xs text-gray-600">
                <p>Sistema Integrado de Gestión Operativa (SIGO)</p>
                <p class="mt-1">Conexión segura | Datos encriptados</p>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/Ocultar clave
        document.getElementById('mostrarClave').addEventListener('change', (e) => {
            const input = document.getElementById('claveInput');
            input.type = e.target.checked ? 'text' : 'password';
        });

        // Convertir folio a mayúsculas automáticamente
        document.querySelector('input[name="folio"]').addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>
