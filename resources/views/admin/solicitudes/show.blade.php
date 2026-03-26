@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.solicitudes.index', ['apoyo' => $apoyoFilter]) }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Folio {{ $solicitud->folio }}</h1>
                    <p class="text-sm text-slate-500">{{ $solicitud->apoyo->nombre_apoyo }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-3 gap-6">
            <!-- Info Panel (Left) -->
            <div class="col-span-1 space-y-4">
                <!-- Beneficiary Info -->
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wide mb-3">Beneficiario</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="text-slate-600">Nombre:</span> <strong>{{ $solicitud->beneficiario->nombre }}</strong></p>
                        <p><span class="text-slate-600">Apellidos:</span> <strong>{{ $solicitud->beneficiario->apellido_paterno }} {{ $solicitud->beneficiario->apellido_materno }}</strong></p>
                        <p><span class="text-slate-600">CURP:</span> <strong>{{ $solicitud->beneficiario->curp }}</strong></p>
                        <p><span class="text-slate-600">Teléfono:</span> <strong>{{ $solicitud->beneficiario->telefono ?? 'No registrado' }}</strong></p>
                    </div>
                </div>

                <!-- Support Info -->
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wide mb-3">Apoyo</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="text-slate-600">Tipo:</span> <strong>{{ $solicitud->apoyo->nombre_apoyo }}</strong></p>
                        <p><span class="text-slate-600">Descripción:</span> <strong>{{ strip_tags($solicitud->apoyo->descripcion ?? 'N/A') }}</strong></p>
                    </div>
                </div>

                <!-- Documents Status -->
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wide mb-3">Estatus Documentos</h3>
                    <div class="space-y-2 text-xs">
                        @php
                            $pendientes = $documentos->where('admin_status', 'pendiente')->count();
                            $aceptados = $documentos->where('admin_status', 'aceptado')->count();
                            $rechazados = $documentos->where('admin_status', 'rechazado')->count();
                        @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Pendientes:</span>
                            <span class="font-bold text-amber-600">{{ $pendientes }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Aceptados:</span>
                            <span class="font-bold text-green-600">{{ $aceptados }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Rechazados:</span>
                            <span class="font-bold text-red-600">{{ $rechazados }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Panel (Right) -->
            <div class="col-span-2">
                <div class="rounded-lg border border-slate-200 bg-white overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wide">Documentos para Verificar</h3>
                    </div>

                    @if($documentos->isEmpty())
                    <div class="px-6 py-12 text-center text-slate-500">
                        <p class="text-sm">No hay documentos para esta solicitud</p>
                    </div>
                    @else
                    <div class="divide-y divide-slate-200">
                        @foreach($documentos as $documento)
                        <div class="px-6 py-4 hover:bg-slate-50 transition-colors" data-documento-id="{{ $documento->id_doc }}">
                            <!-- Document Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-900">{{ $documento->tipoDocumento->nombre_documento }}</h4>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="text-xs px-2 py-1 rounded-full 
                                            {{ $documento->admin_status === 'aceptado' ? 'bg-green-100 text-green-700' : 
                                               ($documento->admin_status === 'rechazado' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                            {{ ucfirst($documento->admin_status) }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            {{ $documento->fecha_carga?->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- View Button -->
                                <a href="/admin/solicitudes/{{ $documento->id_doc }}/view" target="_blank"
                                   class="ml-4 px-3 py-1 text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 rounded hover:bg-blue-100 transition-colors">
                                    Ver
                                </a>
                            </div>

                            <!-- Verification Panel -->
                            <div class="mt-4 pt-4 border-t border-slate-200" id="verify-form-{{ $documento->id_doc }}" style="display: {{ $documento->admin_status === 'pendiente' ? 'block' : 'none' }}">
                                <form class="space-y-3 verify-form" data-documento-id="{{ $documento->id_doc }}">
                                    @csrf
                                    
                                    <!-- Status Buttons -->
                                    <div class="flex gap-2">
                                        <button type="button" class="flex-1 px-4 py-2 bg-green-50 text-green-700 font-medium border border-green-200 rounded hover:bg-green-100 transition-colors accept-btn">
                                            ✓ Aceptar
                                        </button>
                                        <button type="button" class="flex-1 px-4 py-2 bg-red-50 text-red-700 font-medium border border-red-200 rounded hover:bg-red-100 transition-colors reject-btn">
                                            ✕ Rechazar
                                        </button>
                                    </div>

                                    <!-- Rejection Reason Field (hidden by default) -->
                                    <div class="rejection-reason-field" style="display: none">
                                        <label class="block text-sm font-semibold text-slate-900 mb-2">
                                            <span class="text-red-600">*</span> Motivo del rechazo (obligatorio)
                                        </label>
                                        <textarea name="observations" placeholder="Explique clara y detalladamente por qué rechaza este documento. El beneficiario podrá ver este motivo..." 
                                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                                  rows="4" minlength="10" required></textarea>
                                        <p class="text-xs text-slate-500 mt-1">Mínimo 10 caracteres. El beneficiario recibirá este mensaje.</p>
                                        <button type="submit" class="mt-3 w-full px-4 py-2 bg-red-600 text-white font-medium rounded hover:bg-red-700 transition-colors submit-reject-btn">
                                            Confirmar Rechazo
                                        </button>
                                    </div>

                                    <!-- Status Input -->
                                    <input type="hidden" name="status" class="status-input">
                                </form>
                            </div>

                            <!-- Display Observations (if not pending) -->
                            @if($documento->admin_observations)
                            <div class="mt-4 pt-4 border-t border-slate-200">
                                <p class="text-xs text-slate-600 uppercase tracking-wide font-medium mb-1">Observaciones del Admin:</p>
                                <p class="text-sm text-slate-700">{{ strip_tags($documento->admin_observations) }}</p>
                            </div>
                            @endif

                            <!-- Verification Token (if accepted) -->
                            @if($documento->verification_token)
                            <div class="mt-4 pt-4 border-t border-slate-200">
                                <p class="text-xs text-slate-600 uppercase tracking-wide font-medium mb-1">Token de Verificación:</p>
                                <code class="text-xs bg-slate-100 px-2 py-1 rounded block break-all">{{ $documento->verification_token }}</code>
                            </div>
                            @endif

                            <!-- Admin Info (if verified) -->
                            @if($documento->id_admin)
                            <div class="mt-2 text-xs text-slate-500">
                                Verificado por {{ $documento->admin->name ?? 'Admin' }} el 
                                {{ $documento->fecha_verificacion?->format('d/m/Y H:i') }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Accept/Reject buttons
    document.querySelectorAll('.verify-form').forEach(form => {
        const docId = form.dataset.documentoId;
        const acceptBtn = form.querySelector('.accept-btn');
        const rejectBtn = form.querySelector('.reject-btn');
        const statusInput = form.querySelector('.status-input');
        const rejectionField = form.querySelector('.rejection-reason-field');
        const submitRejectBtn = form.querySelector('.submit-reject-btn');
        const textarea = form.querySelector('textarea[name="observations"]');

        // Validar que los elementos existan
        if (!acceptBtn || !rejectBtn || !statusInput) {
            console.warn('Elementos del formulario no encontrados para documento', docId);
            return;
        }

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function(e) {
                e.preventDefault();
                statusInput.value = 'aceptado';
                if (rejectionField) rejectionField.style.display = 'none';
                if (textarea) {
                    textarea.removeAttribute('required');
                    textarea.value = '';
                }
                submitVerification(form, docId);
            });
        }

        if (rejectBtn) {
            rejectBtn.addEventListener('click', function(e) {
                e.preventDefault();
                statusInput.value = 'rechazado';
                if (rejectionField) rejectionField.style.display = 'block';
                if (textarea) {
                    textarea.setAttribute('required', 'required');
                    textarea.focus();
                }
            });
        }

        // Handle rejection submission
        if (submitRejectBtn && textarea) {
            submitRejectBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!textarea.value.trim()) {
                    alert('Por favor escriba el motivo del rechazo');
                    return;
                }
                
                if (textarea.value.trim().length < 10) {
                    alert('El motivo debe tener al menos 10 caracteres');
                    return;
                }
                
                statusInput.value = 'rechazado';
                submitVerification(form, docId);
            });
        }
    });

    // Submit verification via AJAX
    function submitVerification(form, docId) {
        const observations = form.querySelector('textarea[name="observations"]')?.value || '';
        const status = form.querySelector('.status-input').value;

        fetch(`/admin/solicitudes/${docId}/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                status: status,
                observations: observations
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Success - reload to see changes
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la verificación');
        });
    }
});
</script>
@endsection
