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
                <div class="hidden lg:block">
                    <svg class="w-16 h-16 text-green-500/20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723p.5 0 11.5 0 .5.5 0 0 0 0-1A4.072 4.072 0 006 1.5v1.955z" clip-rule="evenodd" href="#" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Contenedor Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Información de la Solicitud -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000-2 4 4 0 00-4 4v10a4 4 0 004 4h12a4 4 0 004-4V5a4 4 0 00-4-4 1 1 0 000 2 2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                        </svg>
                        Información de Solicitud
                    </h2>

                    <div class="space-y-3" id="solicitud-info">
                        <div class="skeleton h-4 w-3/4"></div>
                        <div class="skeleton h-4 w-1/2"></div>
                        <div class="skeleton h-4 w-2/3"></div>
                    </div>
                </div>

                <!-- Documentos Adjuntos -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                        </svg>
                        Documentos Asociados
                    </h2>

                    <div class="space-y-2" id="documentos-list">
                        <div class="skeleton h-12"></div>
                        <div class="skeleton h-12"></div>
                        <div class="skeleton h-12"></div>
                    </div>
                </div>

                <!-- Motivo de Rechazo (solo si se rechaza) -->
                <div class="bg-white rounded-lg shadow-md p-6 hidden" id="motivo-section">
                    <label for="motivo" class="block text-sm font-semibold text-gray-900 mb-2">
                        Motivo de Rechazo <span class="text-red-600">*</span>
                    </label>
                    <textarea 
                        id="motivo"
                        rows="4"
                        placeholder="Describe el motivo del rechazo..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                    </textarea>
                    <p class="mt-2 text-sm text-gray-600">
                        Máximo 500 caracteres
                    </p>
                </div>
            </div>

            <!-- Sidebar (1/3) -->
            <div class="lg:col-span-1">
                <!-- Estado de Firma -->
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Estado</h3>

                    <div class="space-y-4">
                        <!-- Status Badge -->
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-600 font-semibold mb-1">ESTADO ACTUAL</p>
                            <p class="text-lg font-bold text-blue-900" id="estado-badge">
                                Cargando...
                            </p>
                        </div>

                        <!-- Re-autenticación Status -->
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <p class="text-xs text-gray-600 font-semibold mb-2">RE-AUTENTICACIÓN</p>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-gray-400" id="reauth-indicator"></div>
                                <span class="text-sm text-gray-700" id="reauth-status">No verificado</span>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="border-t border-gray-200 pt-4 space-y-2">
                            <button 
                                @click="openReauthModal()"
                                class="w-full px-4 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition flex items-center justify-center gap-2"
                                id="btn-verificar">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                Verificar Identidad
                            </button>

                            <button 
                                @click="firmarSolicitud()"
                                class="w-full px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition hidden"
                                id="btn-firmar">
                                <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                </svg>
                                Firmar (Aprobar)
                            </button>

                            <button 
                                @click="toggleRechazar()"
                                class="w-full px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition hidden"
                                id="btn-rechazar">
                                <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                Rechazar
                            </button>

                            <button 
                                @click="rechazarConMotivo()"
                                class="w-full px-4 py-2 bg-red-700 text-white font-semibold rounded-lg hover:bg-red-800 disabled:bg-gray-400 disabled:cursor-not-allowed transition hidden"
                                id="btn-confirmar-rechazo">
                                Confirmar Rechazo
                            </button>
                        </div>

                        <button 
                            @click="window.history.back()"
                            class="w-full px-4 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                            Volver
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Re-autenticación (componente incluido) -->
@include('modals.reauth-signature')

<!-- CSS para skeleton loading -->
<style>
    .skeleton {
        @apply bg-gray-200 rounded animate-pulse;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const folio = window.location.pathname.split('/')[2];
    
    // Cargar información de la solicitud
    try {
        const response = await fetch(`/solicitudes/${folio}/firma`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        const data = await response.json();
        if (data.success) {
            displaySolicitudInfo(data.solicitud);
        }
    } catch (error) {
        console.error('Error al cargar solicitud:', error);
        showNotification('error', 'Error al cargar la información de la solicitud');
    }

    // Escuchar evento de re-autenticación exitosa
    window.addEventListener('reauthSuccess', (event) => {
        markReauthSuccess(event.detail);
    });
});

function displaySolicitudInfo(solicitud) {
    const beneficiario = solicitud.beneficiario || {};
    const apoyo = solicitud.apoyo || {};
    
    document.getElementById('folio-number').textContent = solicitud.folio;
    document.getElementById('estado-badge').textContent = getEstadoLabel(solicitud.estado);
    
    const solicitudInfo = document.getElementById('solicitud-info');
    solicitudInfo.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-600 font-semibold mb-1">BENEFICIARIO</p>
                <p class="text-sm font-semibold text-gray-900">${beneficiario.nombre || 'N/A'}</p>
                <p class="text-xs text-gray-600">${beneficiario.curp || ''}</p>
            </div>
            <div>
                <p class="text-xs text-gray-600 font-semibold mb-1">TIPO DE APOYO</p>
                <p class="text-sm font-semibold text-gray-900">${apoyo.nombre || 'N/A'}</p>
            </div>
            <div>
                <p class="text-xs text-gray-600 font-semibold mb-1">FECHA DE CREACIÓN</p>
                <p class="text-sm font-semibold text-gray-900">${new Date(solicitud.fecha_creacion).toLocaleDateString('es-MX')}</p>
            </div>
            <div>
                <p class="text-xs text-gray-600 font-semibold mb-1">MONTO</p>
                <p class="text-sm font-semibold text-green-600">$${parseFloat(apoyo.monto || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</p>
            </div>
        </div>
    `;
    
    const documentosList = document.getElementById('documentos-list');
    if (solicitud.documentos.length > 0) {
        documentosList.innerHTML = solicitud.documentos.map(doc => `
            <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">Documento #${doc.id}</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                        doc.estado === 'Aprobado' ? 'bg-green-100 text-green-800' :
                        doc.estado === 'Rechazado' ? 'bg-red-100 text-red-800' :
                        'bg-yellow-100 text-yellow-800'
                    }">
                        ${doc.estado}
                    </span>
                </div>
            </div>
        `).join('');
    }
}

function markReauthSuccess(data) {
    document.getElementById('reauth-indicator').className = 'w-3 h-3 rounded-full bg-green-500';
    document.getElementById('reauth-status').textContent = 'Verificado';
    document.getElementById('btn-verificar').classList.add('hidden');
    document.getElementById('btn-firmar').classList.remove('hidden');
    document.getElementById('btn-rechazar').classList.remove('hidden');
    
    // Guardar el token para usarlo después
    window.currentReauthToken = data.token;
}

function toggleRechazar() {
    document.getElementById('motivo-section').classList.toggle('hidden');
    document.getElementById('btn-rechazar').classList.toggle('hidden');
    document.getElementById('btn-confirmar-rechazo').classList.toggle('hidden');
}

async function firmarSolicitud() {
    const folio = window.location.pathname.split('/')[2];
    const token = window.currentReauthToken;
    
    if (!token) {
        showNotification('error', 'Re-autenticación expirada. Por favor, verifica tu identidad de nuevo.');
        return;
    }

    try {
        const response = await fetch(`/solicitudes/${folio}/firma/firmar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                reauth_token: token
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('success', data.message);
            setTimeout(() => {
                window.location.href = `/solicitudes/proceso`;
            }, 2000);
        } else {
            showNotification('error', data.message);
        }
    } catch (error) {
        console.error('Error al firmar:', error);
        showNotification('error', 'Error al firmar la solicitud');
    }
}

async function rechazarConMotivo() {
    const folio = window.location.pathname.split('/')[2];
    const token = window.currentReauthToken;
    const motivo = document.getElementById('motivo').value;
    
    if (!token) {
        showNotification('error', 'Re-autenticación expirada. Por favor, verifica tu identidad de nuevo.');
        return;
    }

    if (!motivo || motivo.trim().length === 0) {
        showNotification('error', 'Debes ingresar un motivo de rechazo');
        return;
    }

    try {
        const response = await fetch(`/solicitudes/${folio}/firma/rechazar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                reauth_token: token,
                motivo: motivo,
                permite_correcciones: true
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('success', data.message);
            setTimeout(() => {
                window.location.href = `/solicitudes/proceso`;
            }, 2000);
        } else {
            showNotification('error', data.message);
        }
    } catch (error) {
        console.error('Error al rechazar:', error);
        showNotification('error', 'Error al rechazar la solicitud');
    }
}

function getEstadoLabel(estado) {
    const estados = {
        1: 'Pendiente de Revisión',
        2: 'En Revisión Administrativa',
        3: 'Aprobada',
        4: 'Rechazada',
        5: 'Pagada'
    };
    return estados[estado] || 'Desconocido';
}

function showNotification(type, message) {
    // Simple notification - puede ser reemplazado con librería como toast
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
