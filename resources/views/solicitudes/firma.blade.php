@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Encabezado -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                <a href="{{ route('solicitudes.proceso.index') }}" class="hover:text-blue-600">
                    Solicitudes en Proceso
                </a>
                <span>/</span>
                <span class="font-semibold text-gray-900">Firma Digital</span>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Firma de Solicitud
                    </h1>
                    <p class="text-gray-600">
                        Folio: <span class="font-mono font-semibold text-gray-900" id="folio-number">{{ $folio ?? 'Cargando...' }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- PANTALLA 1: RESUMEN CRÍTICO ANTES DE FIRMAR -->
        <div id="resumen-screen" class="max-w-4xl mx-auto">
            @component('components.firma.resumen-critico', [
                'beneficiario' => $beneficiario ?? (object)[],
                'apoyo' => $apoyo ?? (object)[],
                'documentos' => $documentos ?? [],
                'monto_solicitud' => $monto_solicitud ?? 0,
                'hito_actual' => $hito_actual ?? null
            ])
            @endcomponent

            <div class="mt-8 flex gap-3">
                <button id="btn-proceder-firma" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold rounded-lg hover:shadow-lg hover:from-blue-700 hover:to-blue-800 transition transform hover:-translate-y-0.5">
                    <svg class="inline w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Proceder a Firmar
                </button>
                <button id="btn-cancelar" class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
            </div>
        </div>


    </div>
</div>

<style>
    .skeleton {
        @apply bg-gray-200 rounded animate-pulse;
    }
</style>

<script>
    const folio = window.location.pathname.split('/')[2];
    
    const btnProceder = document.getElementById('btn-proceder-firma');
    const btnCancelar = document.getElementById('btn-cancelar');
    
    if (btnProceder) btnProceder.addEventListener('click', procederAFirma);
    if (btnCancelar) btnCancelar.addEventListener('click', () => window.history.back());
});

async function procederAFirma() {
    const folio = window.location.pathname.split('/')[2];
    const checkboxIds = ['confirm-beneficiario', 'confirm-monto', 'confirm-documentos', 'confirm-responsabilidad'];
    let uncheckedBoxes = [];
    
    for (let id of checkboxIds) {
        const checkbox = document.getElementById(id);
        if (!checkbox || !checkbox.checked) {
            uncheckedBoxes.push(id);
        }
    }
    
    if (uncheckedBoxes.length > 0) {
        showNotification('error', 'Debes marcar TODOS los checkboxes antes de proceder');
        return;
    }

    try {
        showNotification('success', 'Completando Fase 2...');
        
        const response = await fetch(`/solicitudes/${folio}/completar-fase-2`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        const data = await response.json();
        if (data.success) {
            showNotification('success', 'Fase 2 completada. Redirigiendo...');
            setTimeout(() => {
                window.location.href = `/solicitudes/proceso`;
            }, 1500);
        } else {
            showNotification('error', data.message || 'Error al completar la fase');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Error al procesar solicitud');
    }
}


function showNotification(type, message) {
    const color = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${color} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

@endsection
