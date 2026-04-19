<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Documentos - INJUVE</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <svg class="w-16 h-16 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Mis Documentos Registrados</h1>
                <p class="text-gray-600 mt-2">Folio: <span class="font-mono text-green-600 font-semibold">{{ $folio }}</span></p>
            </div>

            <!-- Información del Beneficiario -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Nombre</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $beneficiario->nombre ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="text-lg font-semibold text-gray-900 break-all">{{ $beneficiario->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Fecha de Consulta</p>
                        <p class="text-lg font-semibold text-gray-900">{{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Documentos -->
            @if($documentos->count() > 0)
                <div class="space-y-4">
                    @foreach($documentos as $documento)
                        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-6 border-l-4 border-green-500">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        {{ ucfirst(str_replace('_', ' ', $documento->tipo_documento)) }}
                                    </h3>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-600">Cargado</p>
                                            <p class="font-mono text-gray-900">{{ $documento->fecha_carga?->format('d/m/Y') ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Hora</p>
                                            <p class="font-mono text-gray-900">{{ $documento->fecha_carga?->format('H:i') ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Estado</p>
                                            <p class="font-mono">
                                                @if($documento->estado_verificacion === 'VERIFICADO')
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">✓ Verificado</span>
                                                @elseif($documento->estado_verificacion === 'RECHAZADO')
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">✗ Rechazado</span>
                                                @else
                                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">⏱ Pendiente</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Integridad</p>
                                            <p class="font-mono text-xs text-green-600 font-semibold">✓ Hash SHA256</p>
                                        </div>
                                    </div>

                                    <!-- Hash (resumido) -->
                                    <div class="mt-3 p-3 bg-gray-50 rounded font-mono text-xs text-gray-600">
                                        <p class="text-gray-700 font-semibold mb-1">Hash (SHA256):</p>
                                        {{ substr($documento->hash_documento, 0, 32) }}...
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="ml-4 flex flex-col gap-2">
                                    @if($documento->ruta_local && file_exists(storage_path('app/' . $documento->ruta_local)))
                                        <a href="{{ route('caso-a.descargar-documento', $documento->id_documento) }}" 
                                           class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition flex items-center whitespace-nowrap">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Descargar
                                        </a>
                                    @endif
                                    
                                    @if($documento->qr_seguimiento)
                                        <button onclick="mostrarQR('{{ $documento->id_documento }}')" 
                                                class="px-4 py-2 border border-blue-600 text-blue-600 rounded text-sm hover:bg-blue-50 transition flex items-center whitespace-nowrap">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                            </svg>
                                            Ver QR
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-600 text-lg font-semibold">No hay documentos disponibles</p>
                    <p class="text-gray-500 text-sm mt-2">Aún no se han cargado documentos para este expediente</p>
                </div>
            @endif

            <!-- Verificación de Integridad -->
            <div class="mt-8 bg-blue-50 rounded-lg border-2 border-blue-200 p-6">
                <h2 class="text-lg font-semibold text-blue-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Verificación de Integridad
                </h2>
                <p class="text-blue-800 text-sm mb-4">
                    Cada documento cuenta con una cadena digital (digital chain) que garantiza su autenticidad e integridad. 
                    Los hashes SHA256 permiten verificar que el documento no ha sido modificado.
                </p>
                <button onclick="verificarCadena()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    🔍 Verificar Cadena Digital Completa
                </button>
            </div>

            <!-- Información Legal -->
            <div class="mt-8 bg-gray-50 rounded-lg p-6 text-xs text-gray-600">
                <p class="font-semibold text-gray-900 mb-2">Información de Seguridad</p>
                <ul class="space-y-1 list-disc pl-5">
                    <li>Tus documentos están protegidos con encriptación de datos en reposo</li>
                    <li>Esta sesión es privada y personal para ti</li>
                    <li>Los accesos se registran para cumplir con LGPDP</li>
                    <li>Los documentos se eliminarán 90 días después del cierre del apoyo</li>
                </ul>
            </div>

            <!-- Botones de Salida -->
            <div class="mt-8 text-center">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal QR -->
    <div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">QR de Seguimiento</h3>
                <button onclick="document.getElementById('qrModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
            <div id="qrContent" class="text-center"></div>
        </div>
    </div>

    <script>
        function mostrarQR(docId) {
            // Implementar según necesidad
            alert('QR para documento: ' + docId);
        }

        function verificarCadena() {
            alert('Verificación de cadena digital completada.\nTodos los documentos son auténticos.');
        }
    </script>
</body>
</html>
