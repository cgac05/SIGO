@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">✓ Expediente Presencial Creado</h1>
            <p class="text-gray-600 mt-2">Imprimir y entregar al beneficiario</p>
        </div>

        <!-- Ticket para Imprimir -->
        <div id="ticketImpresion" class="bg-white rounded-lg shadow-2xl p-8 max-w-2xl mx-auto border-4 border-blue-200">
            
            <!-- Logo INJUVE (simulado) -->
            <div class="text-center border-b-2 border-blue-300 pb-6 mb-6">
                <div class="flex justify-center mb-4">
                    <svg class="w-16 h-16 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">INJUVE NAYARIT</h2>
                <p class="text-sm text-gray-600">Instituto Nayarita de la Juventud</p>
                <p class="text-xs text-gray-500 mt-1">Sistema Integrado de Gestión Operativa</p>
            </div>

            <!-- Datos del Beneficiario -->
            <div class="mb-6 p-4 bg-blue-50 rounded border border-blue-200">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Datos del Beneficiario</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Nombre:</p>
                        <p class="font-semibold text-gray-900">
                            {{ trim(($beneficiario->nombre ?? '') . ' ' . ($beneficiario->apellido_paterno ?? '') . ' ' . ($beneficiario->apellido_materno ?? '')) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Email:</p>
                        <p class="font-semibold text-gray-900 break-all">{{ $beneficiario->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">CURP:</p>
                        <p class="font-mono font-semibold text-gray-900">{{ $beneficiario->curp ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Teléfono:</p>
                        <p class="font-semibold text-gray-900">{{ $beneficiario->telefono ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Datos del Apoyo -->
            <div class="mb-6 p-4 bg-green-50 rounded border border-green-200">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Apoyo Solicitado</h3>
                <p class="font-semibold text-gray-900 text-lg">{{ $apoyo->nombre_apoyo ?? 'N/A' }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ $apoyo->descripcion_apoyo ?? '' }}</p>
            </div>

            <!-- FOLIO ÚNICO (Destacado) -->
            <div class="mb-6 p-6 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg text-white text-center">
                <p class="text-xs font-semibold uppercase tracking-widest">Folio de Expediente</p>
                <p class="text-4xl font-mono font-bold mt-2">{{ $folio }}</p>
                <p class="text-xs mt-2 opacity-90">Conservar este folio para consultar status</p>
            </div>

            <!-- CLAVE PRIVADA (Super Importante) -->
            <div class="mb-6 p-6 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-lg">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-900">Clave de Acceso Privado</p>
                <p class="text-3xl font-mono font-bold mt-2 text-gray-900">{{ $clave }}</p>
                <p class="text-xs mt-2 text-gray-800 font-semibold">
                    ⚠️ IMPORTANTE: Guardar esta clave en lugar seguro
                </p>
                <p class="text-xs mt-1 text-gray-800">
                    La clave es personal e intransferible. Necesaria para consultar documentos.
                </p>
            </div>

            <!-- QR de Folio -->
            <div class="mb-6 text-center p-4 bg-gray-50 rounded border border-gray-300">
                <p class="text-xs font-semibold text-gray-700 mb-2">QR de Seguimiento</p>
                <img src="data:image/svg+xml,{{ urlencode($qrData) }}" alt="QR" class="h-32 mx-auto" style="image-rendering: pixelated;">
            </div>

            <!-- Documentos Entregados -->
            <div class="mb-6 p-4 bg-purple-50 rounded border border-purple-200">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Documentos Entregados Hoy</h3>
                <ul class="text-sm space-y-1">
                    @if(session('documentos_listados'))
                        @foreach(session('documentos_listados') as $doc)
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-purple-600 rounded-full mr-2"></span>
                                {{ ucfirst(str_replace('_', ' ', $doc)) }}
                            </li>
                        @endforeach
                    @else
                        <li class="text-gray-600">Documentos registrados en el sistema</li>
                    @endif
                </ul>
            </div>

            <!-- Instrucciones -->
            <div class="mb-6 p-4 bg-blue-100 rounded border-l-4 border-blue-600">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">📋 Próximos Pasos:</h3>
                <ol class="text-xs text-gray-800 space-y-1 list-decimal list-inside">
                    <li><strong>Conservar este ticket</strong> con el folio y clave privada</li>
                    <li><strong>En 24-48 horas</strong>, el personal administrativo escaneará los documentos</li>
                    <li><strong>La solicitud entra al FLUJO ORDINARIO:</strong> Admin verifica en la interfaz normal (no separada)</li>
                    <li>Directivo firma digitalmente como de costumbre</li>
                    <li>Beneficiario recibe notificación</li>
                    <li>Puede consultar el status en: <span class="font-mono bg-white px-1">sigo.injuve.mx/consulta-privada</span></li>
                </ol>
            </div>

            <!-- Fecha y Hora -->
            <div class="border-t-2 border-gray-300 pt-4 text-center text-xs text-gray-600">
                <p>Fecha y Hora: {{ $fechaCreacion }}</p>
                <p class="mt-1">Documento generado automáticamente por SIGO</p>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="mt-8 flex justify-center gap-4">
            <button onclick="window.print()" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H7a2 2 0 01-2-2v-4a2 2 0 012-2h10a2 2 0 012 2v4a2 2 0 01-2 2zm0 0h6a2 2 0 002-2v-4a2 2 0 00-2-2h-.5"/>
                </svg>
                Imprimir Ticket
            </button>

            <button onclick="copiarAlPortapapeles()" class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Copiar Folio y Clave
            </button>

            <a href="{{ route('admin.caso-a.momento-uno') }}" class="px-8 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-semibold flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Expediente
            </a>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            background: white;
        }
        .min-h-screen {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #ticketImpresion {
            box-shadow: none;
            max-width: 100%;
        }
        button {
            display: none !important;
        }
    }
</style>

<script>
    function copiarAlPortapapeles() {
        const folio = '{{ $folio }}';
        const clave = '{{ $clave }}';
        const texto = `FOLIO: ${folio}\nCLAVE: ${clave}`;
        
        navigator.clipboard.writeText(texto).then(() => {
            alert('✓ Folio y clave copiados al portapapeles');
        });
    }
</script>
@endsection
