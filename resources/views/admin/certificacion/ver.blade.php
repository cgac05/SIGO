@extends('layouts.app')

@section('title', 'Detalles del Certificado Digital')

@section('content')
<div class="container mx-auto p-6">
    <!-- Encabezado -->
    <div class="mb-8">
        <a href="{{ route('certificacion.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">← Volver</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">🔐 Certificado Digital Nº {{ $desembolso->id_historico }}</h1>
    </div>

    <!-- Estado del Certificado -->
    <div class="mb-8 p-6 rounded-lg 
        @if($desembolso->estado_certificacion === 'CERTIFICADO') 
            bg-green-50 border-l-4 border-green-500
        @elseif($desembolso->estado_certificacion === 'VALIDADO')
            bg-blue-50 border-l-4 border-blue-500
        @else
            bg-orange-50 border-l-4 border-orange-500
        @endif">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold">
                    @if($desembolso->estado_certificacion === 'CERTIFICADO')
                        ✅ Certificado Generado
                    @elseif($desembolso->estado_certificacion === 'VALIDADO')
                        🔍 Certificado Validado
                    @else
                        ⏳ Pendiente
                    @endif
                </h2>
                <p class="text-gray-600 mt-2">
                    Generado: {{ $desembolso->fecha_certificacion ? $desembolso->fecha_certificacion->format('d/m/Y H:i:s') : 'N/A' }}
                </p>
            </div>
            <span class="text-4xl">
                @if($desembolso->estado_certificacion === 'CERTIFICADO') ✅ @elseif($desembolso->estado_certificacion === 'VALIDADO') 🔍 @else ⏳ @endif
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Principal: Detalles del Certificado -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Información del Desembolso -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">📋 Información del Desembolso</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Folio</p>
                        <p class="text-lg font-bold text-blue-600">{{ $desembolso->fk_folio }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Monto</p>
                        <p class="text-lg font-bold text-green-600">${{ number_format($desembolso->monto_entregado, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Fecha Entrega</p>
                        <p class="text-sm font-mono">{{ $desembolso->fecha_entrega->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Usuario Registrador</p>
                        <p class="text-sm">{{ $desembolso->usuario->display_name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Datos del Certificado -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">🔐 Datos del Certificado</h3>
                
                <div class="space-y-4">
                    <!-- Hash Certificado -->
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Hash SHA-256 del Certificado</p>
                        <div class="bg-gray-100 p-3 rounded font-mono text-sm break-all text-gray-700">
                            {{ $desembolso->hash_certificado ?? 'N/A' }}
                        </div>
                        @if($desembolso->hash_certificado)
                        <button onclick="copiarAlPortapapeles('{{ $desembolso->hash_certificado }}')" 
                                class="text-blue-600 hover:text-blue-800 text-xs mt-2 font-medium">
                            📋 Copiar Hash
                        </button>
                        @endif
                    </div>

                    <!-- Datos QR -->
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Datos del Código QR</p>
                        <div class="bg-gray-100 p-3 rounded font-mono text-xs break-all text-gray-700">
                            {{ $desembolso->qrcode_data ?? 'N/A' }}
                        </div>
                    </div>

                    <!-- Ruta QR Code -->
                    @if($desembolso->ruta_qrcode)
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Código QR</p>
                        <div class="bg-white border-2 border-gray-300 p-4 rounded text-center">
                            <p class="text-gray-600 text-sm">📱 QR Code almacenado</p>
                            <p class="text-xs text-gray-500 mt-2">{{ $desembolso->ruta_qrcode }}</p>
                            <a href="{{ asset($desembolso->ruta_qrcode) }}" target="_blank" 
                               class="text-blue-600 hover:text-blue-800 text-xs mt-2 inline-block">
                                🔗 Ver QR Code
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Fecha Certificación -->
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Fecha de Certificación</p>
                        <p class="text-sm font-mono mt-1">
                            {{ $desembolso->fecha_certificacion ? $desembolso->fecha_certificacion->format('d/m/Y H:i:s') : 'Por generar' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Cadena de Custodia -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">🔗 Cadena de Custodia</h3>
                
                @if(!empty($cadena_custodia))
                <div class="space-y-4">
                    @foreach($cadena_custodia as $evento)
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-bold text-gray-800">
                                    @if($evento['tipo_evento'] === 'CREACION_CERTIFICADO')
                                        🔐 Creación del Certificado
                                    @elseif($evento['tipo_evento'] === 'VALIDACION')
                                        🔍 Validación
                                    @else
                                        📝 {{ $evento['tipo_evento'] }}
                                    @endif
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ isset($evento['fecha_creacion']) ? \Carbon\Carbon::parse($evento['fecha_creacion'])->format('d/m/Y H:i:s') : '' }}
                                    {{ isset($evento['fecha_validacion']) ? \Carbon\Carbon::parse($evento['fecha_validacion'])->format('d/m/Y H:i:s') : '' }}
                                </p>
                            </div>
                            <span class="text-sm bg-gray-100 px-3 py-1 rounded text-gray-600">
                                {{ $evento['ip_terminal'] ?? 'N/A' }}
                            </span>
                        </div>
                        @if(isset($evento['id_usuario_validador']))
                        <p class="text-xs text-gray-500 mt-2">Usuario: {{ $evento['id_usuario_validador'] }}</p>
                        @endif
                        @if(isset($evento['notas']) && $evento['notas'])
                        <p class="text-sm text-gray-600 mt-2">{{ $evento['notas'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 text-sm">Sin eventos registrados aún</p>
                @endif
            </div>

            <!-- Snapshot del Estado Anterior -->
            @if($desembolso->snapshot_json)
            <div class="bg-gray-50 rounded-lg border-l-4 border-yellow-500 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📸 Snapshot (Estado Anterior al Pago)</h3>
                <pre class="bg-white p-4 rounded font-mono text-xs overflow-auto max-h-40 text-gray-700">{{ json_encode($desembolso->snapshot_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif
        </div>

        <!-- Sidebar: Acciones Rápidas -->
        <div class="lg:col-span-1 space-y-4">
            <!-- Card: Estado -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h4 class="font-bold text-gray-800 mb-4">📊 Estado</h4>
                <p class="text-sm text-gray-600">Estado Certificación:</p>
                <div class="mt-2 px-4 py-2 rounded text-white font-bold text-center
                    @if($desembolso->estado_certificacion === 'CERTIFICADO')
                        bg-green-500
                    @elseif($desembolso->estado_certificacion === 'VALIDADO')
                        bg-blue-500
                    @else
                        bg-orange-500
                    @endif">
                    {{ $desembolso->estado_certificacion }}
                </div>
            </div>

            <!-- Card: Acciones -->
            <div class="bg-blue-50 rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
                <h4 class="font-bold text-gray-800 mb-4">⚡ Acciones</h4>
                
                @if($desembolso->estado_certificacion === 'PENDIENTE')
                <a href="{{ route('certificacion.crear', $desembolso->id_historico) }}"
                   class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm transition block text-center mb-2">
                    🔐 Generar Certificado
                </a>
                @endif

                @if($desembolso->estado_certificacion === 'CERTIFICADO')
                <a href="{{ route('certificacion.validar-form', $desembolso->id_historico) }}"
                   class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm transition block text-center mb-2">
                    🔍 Validar Certificado
                </a>
                @endif

                <button onclick="descargarComprobante({{ $desembolso->id_historico }})"
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded text-sm transition">
                    📥 Descargar Comprobante
                </button>
            </div>

            <!-- Card: Información Técnica -->
            <div class="bg-gray-50 rounded-lg shadow-lg p-6 border-l-4 border-gray-400">
                <h4 class="font-bold text-gray-800 mb-4">🔧 Información Técnica</h4>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="font-medium text-gray-600">ID Histórico:</dt>
                        <dd class="font-mono text-gray-800">{{ $desembolso->id_historico }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-600">IP Terminal:</dt>
                        <dd class="font-mono text-gray-800 break-all">{{ $desembolso->ip_terminal ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-600">Creado:</dt>
                        <dd class="font-mono text-gray-800">{{ $desembolso->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        alert('✅ Hash copiado al portapapeles');
    });
}

function descargarComprobante(id_historico) {
    fetch(`/api/certificacion/comprobante/${id_historico}`)
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                // Generar descarga de comprobante
                const elemento = document.createElement('a');
                elemento.setAttribute('href', 'data:application/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(data.contenido_pdf, null, 2)));
                elemento.setAttribute('download', `comprobante_${id_historico}.json`);
                elemento.style.display = 'none';
                document.body.appendChild(elemento);
                elemento.click();
                document.body.removeChild(elemento);
            } else {
                alert('❌ ' + data.razon);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error descargando comprobante');
        });
}

// Cargar cadena de custodia
function cargarCadenaCustodia() {
    const id_historico = {{ $desembolso->id_historico }};
    fetch(`/api/certificacion/cadena-custodia/${id_historico}`)
        .then(response => response.json())
        .then(data => {
            console.log('Cadena de custodia actualizada:', data);
        });
}
</script>
@endsection
