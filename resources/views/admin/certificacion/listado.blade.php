@extends('layouts.app')

@section('title', 'Listado de Certificados Digital')

@section('content')
<div class="container mx-auto p-6">
    <!-- Encabezado -->
    <div class="mb-8">
        <a href="{{ route('certificacion.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">← Volver al Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">📋 Listado de Certificados Digitales</h1>
        <p class="text-gray-600 mt-2">Todos los desembolsos certificados y validados</p>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <input type="text" 
               id="searchInput" 
               placeholder="🔍 Buscar por folio, hash del certificado..." 
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
               autocomplete="off">
        <div id="searchResults" class="mt-4 hidden bg-gray-50 rounded p-4 max-h-64 overflow-y-auto"></div>
    </div>

    <!-- Tabla de Certificados -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4">
            <h2 class="text-xl font-bold">🔐 Certificados Digitales</h2>
            <p class="text-blue-100">{{ $certificados->total() }} certificados encontrados</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Folio</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Hash (primeros 16 caracteres)</th>
                        <th class="px-6 py-3 text-right text-sm font-bold text-gray-700">Monto</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Estado</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Fecha Certificación</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Beneficiario</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificados as $cert)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <!-- Folio -->
                        <td class="px-6 py-4 font-mono text-blue-600 font-bold">
                            {{ $cert->fk_folio }}
                        </td>

                        <!-- Hash (primeros 16 caracteres) -->
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded text-gray-700">
                                    {{ Str::limit($cert->hash_certificado, 16, '...') }}
                                </span>
                                <button onclick="copiarHash('{{ $cert->hash_certificado }}')" 
                                        class="ml-2 text-gray-500 hover:text-blue-600" title="Copiar hash">
                                    📋
                                </button>
                            </div>
                        </td>

                        <!-- Monto -->
                        <td class="px-6 py-4 text-right font-bold text-green-600">
                            ${{ number_format($cert->monto_entregado, 2) }}
                        </td>

                        <!-- Estado -->
                        <td class="px-6 py-4 text-center">
                            @if($cert->estado_certificacion === 'CERTIFICADO')
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded font-bold text-sm">
                                    ✅ Certificado
                                </span>
                            @elseif($cert->estado_certificacion === 'VALIDADO')
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded font-bold text-sm">
                                    🔍 Validado
                                </span>
                            @else
                                <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded font-bold text-sm">
                                    ⏳ {{ $cert->estado_certificacion }}
                                </span>
                            @endif
                        </td>

                        <!-- Fecha Certificación -->
                        <td class="px-6 py-4 text-center text-sm">
                            {{ $cert->fecha_certificacion ? $cert->fecha_certificacion->format('d/m/Y') : 'N/A' }}
                            <br>
                            <span class="text-xs text-gray-500">
                                {{ $cert->fecha_certificacion ? $cert->fecha_certificacion->format('H:i') : '' }}
                            </span>
                        </td>

                        <!-- Beneficiario -->
                        <td class="px-6 py-4 text-center text-sm">
                            {{ $cert->solicitud->beneficiario->display_name ?? 'N/A' }}
                        </td>

                        <!-- Acciones -->
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                <!-- Ver Detalles -->
                                <a href="{{ route('certificacion.ver', $cert->id_historico) }}" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition" 
                                   title="Ver detalles">
                                    👁️
                                </a>

                                <!-- Validar (si no está validado) -->
                                @if($cert->estado_certificacion === 'CERTIFICADO')
                                <a href="{{ route('certificacion.validar-form', $cert->id_historico) }}" 
                                   class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition" 
                                   title="Validar">
                                    ✅
                                </a>
                                @endif

                                <!-- Descargar Comprobante -->
                                <button onclick="descargarComprobante({{ $cert->id_historico }})" 
                                        class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded text-sm transition" 
                                        title="Descargar comprobante">
                                    📥
                                </button>

                                <!-- Cadena de Custodia -->
                                <button onclick="verCadenaCustodia({{ $cert->id_historico }}, '{{ $cert->fk_folio }}')" 
                                        class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm transition" 
                                        title="Cadena de custodia">
                                    🔗
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No hay certificados registrados aún
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $certificados->links() }}
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Total Certificados</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $certificados->total() }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Validados</p>
            <p class="text-3xl font-bold text-green-600 mt-2">
                @php
                    $validados = $certificados->getCollection()->where('estado_certificacion', 'VALIDADO')->count();
                @endphp
                {{ $validados }}
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-medium">Monto Total Certificado</p>
            <p class="text-3xl font-bold text-green-600 mt-2">
                $@php
                    $total_monto = $certificados->getCollection()->sum('monto_entregado');
                @endphp
                {{ number_format($total_monto, 2) }}
            </p>
        </div>
    </div>
</div>

<!-- Modal para Cadena de Custodia -->
<div id="modalCadena" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-96 overflow-y-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800" id="modalTitle">Cadena de Custodia</h3>
            <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700 text-2xl">✕</button>
        </div>
        <div id="modalContent" class="space-y-4">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function copiarHash(hash) {
    navigator.clipboard.writeText(hash).then(() => {
        alert('✅ Hash copiado al portapapeles');
    });
}

function descargarComprobante(id_historico) {
    fetch(`/api/certificacion/comprobante/${id_historico}`)
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
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
        });
}

function verCadenaCustodia(id_historico, folio) {
    fetch(`/api/certificacion/cadena-custodia/${id_historico}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').innerText = `Cadena de Custodia - ${folio}`;
            let contenido = `
                <div class="bg-gray-100 p-4 rounded mb-4">
                    <p><strong>Folio:</strong> ${data.folio}</p>
                    <p><strong>Monto:</strong> $${data.monto}</p>
                    <p><strong>Estado:</strong> ${data.estado_certificacion}</p>
                    <p><strong>Hash:</strong> <code>${data.hash_certificado.substring(0, 20)}...</code></p>
                </div>
                <div class="space-y-3">
                    <h4 class="font-bold">Eventos registrados:</h4>
            `;
            
            if (data.cadena_custodia.length > 0) {
                data.cadena_custodia.forEach(evento => {
                    contenido += `
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <p class="font-bold">${evento.tipo_evento}</p>
                            <p class="text-sm text-gray-600">
                                ${evento.fecha_creacion ? new Date(evento.fecha_creacion).toLocaleString() : ''}
                                ${evento.fecha_validacion ? new Date(evento.fecha_validacion).toLocaleString() : ''}
                            </p>
                            <p class="text-xs text-gray-500">IP: ${evento.ip_terminal}</p>
                        </div>
                    `;
                });
            } else {
                contenido += '<p class="text-gray-500">Sin eventos registrados</p>';
            }
            
            contenido += '</div>';
            document.getElementById('modalContent').innerHTML = contenido;
            document.getElementById('modalCadena').classList.remove('hidden');
        });
}

function cerrarModal() {
    document.getElementById('modalCadena').classList.add('hidden');
}

// Búsqueda en tiempo real
document.getElementById('searchInput').addEventListener('keyup', function(e) {
    const query = this.value;
    if (query.length < 3) {
        document.getElementById('searchResults').classList.add('hidden');
        return;
    }

    fetch(`/admin/certificacion/search?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            data.forEach(cert => {
                html += `
                    <div class="flex justify-between items-center p-2 hover:bg-white rounded cursor-pointer" 
                         onclick="window.location.href='/admin/certificacion/${cert.id_historico}'">
                        <div>
                            <p class="font-bold">${cert.fk_folio}</p>
                            <p class="text-xs text-gray-600">${cert.hash_certificado.substring(0, 20)}...</p>
                        </div>
                        <span class="text-sm">${cert.estado_certificacion}</span>
                    </div>
                `;
            });
            
            if (html === '') {
                html = '<p class="text-gray-500">Sin resultados</p>';
            }
            
            document.getElementById('searchResults').innerHTML = html;
            document.getElementById('searchResults').classList.remove('hidden');
        });
});

// Cerrar modal al presionar Esc
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});
</script>
@endsection
