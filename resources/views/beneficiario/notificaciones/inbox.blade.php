@extends('layouts.app')

@section('title', 'Mi Bandeja de Notificaciones')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">📬 Mi Bandeja de Notificaciones</h1>
                <p class="mt-2 text-gray-600">Ahora tienes {{ $noLeidas }} notificación{{ $noLeidas !== 1 ? 'es' : '' }} sin leer</p>
            </div>
            @if($noLeidas > 0)
                <button 
                    onclick="marcarTodasLeidas()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    Marcar todas como leídas
                </button>
            @endif
        </div>
    </div>

    <!-- Filtros y opciones -->
    <div class="mb-6 flex gap-4">
        <button 
            class="filter-tab px-4 py-2 rounded-lg font-medium transition" 
            data-filter="todas"
            onclick="filtrarNotificaciones('todas')"
        >
            Todas ({{ $notificaciones->total() }})
        </button>
        @php
            $conteoDocRechazadas = $notificaciones->getCollection()->where('tipo', 'documento_rechazado')->count();
            $conteoHito = $notificaciones->getCollection()->where('tipo', 'hito_cambio')->count();
            $conteoSolicitudRechazada = $notificaciones->getCollection()->where('tipo', 'solicitud_rechazada')->count();
        @endphp
        <button 
            class="filter-tab px-4 py-2 rounded-lg font-medium transition" 
            data-filter="documento_rechazado"
            onclick="filtrarNotificaciones('documento_rechazado')"
        >
            Documentos ({{ $conteoDocRechazadas }})
        </button>
        <button 
            class="filter-tab px-4 py-2 rounded-lg font-medium transition" 
            data-filter="hito_cambio"
            onclick="filtrarNotificaciones('hito_cambio')"
        >
            Progreso ({{ $conteoHito }})
        </button>
        <button 
            class="filter-tab px-4 py-2 rounded-lg font-medium transition" 
            data-filter="solicitud_rechazada"
            onclick="filtrarNotificaciones('solicitud_rechazada')"
        >
            Solicitudes ({{ $conteoSolicitudRechazada }})
        </button>
    </div>

    <!-- Lista de notificaciones -->
    <div class="space-y-4">
        @forelse($notificaciones as $notificacion)
            <div 
                class="notification-item p-4 border-l-4 rounded-r-lg bg-white shadow transition hover:shadow-lg"
                data-tipo="{{ $notificacion->tipo }}"
                :class="{
                    'border-l-red-500 bg-red-50': '{{ $notificacion->tipo }}' === 'documento_rechazado',
                    'border-l-green-500 bg-green-50': '{{ $notificacion->tipo }}' === 'hito_cambio',
                    'border-l-amber-500 bg-amber-50': '{{ $notificacion->tipo }}' === 'solicitud_rechazada'
                }"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Ícono y tipo -->
                        <div class="flex items-center gap-3 mb-2">
                            @switch($notificacion->tipo)
                                @case('documento_rechazado')
                                    <span class="text-2xl">❌</span>
                                    <span class="text-sm font-semibold text-red-700">Documento Rechazado</span>
                                    @break
                                @case('hito_cambio')
                                    <span class="text-2xl">✅</span>
                                    <span class="text-sm font-semibold text-green-700">Progreso de Solicitud</span>
                                    @break
                                @case('solicitud_rechazada')
                                    <span class="text-2xl">⚠️</span>
                                    <span class="text-sm font-semibold text-amber-700">Solicitud Rechazada</span>
                                    @break
                                @default
                                    <span class="text-2xl">📌</span>
                                    <span class="text-sm font-semibold text-gray-700">Notificación</span>
                            @endswitch
                            
                            @if(!$notificacion->leida)
                                <span class="ml-auto inline-block w-3 h-3 bg-blue-500 rounded-full"></span>
                            @endif
                        </div>

                        <!-- Título y mensaje -->
                        <h3 class="font-bold text-lg text-gray-900 mb-1">{{ $notificacion->titulo }}</h3>
                        <p class="text-gray-700 mb-3">{{ $notificacion->mensaje }}</p>

                        <!-- Datos adicionales si existen -->
                        @if($notificacion->datos)
                            <div class="bg-white bg-opacity-50 p-3 rounded text-sm text-gray-600 mb-3 space-y-1">
                                @if(isset($notificacion->datos['nombre_documento']))
                                    <p><strong>Documento:</strong> {{ $notificacion->datos['nombre_documento'] }}</p>
                                @endif
                                @if(isset($notificacion->datos['motivo']))
                                    <p><strong>Motivo:</strong> {{ $notificacion->datos['motivo'] }}</p>
                                @endif
                                @if(isset($notificacion->datos['hito_tipo']))
                                    <p><strong>Etapa:</strong> {{ $this->obtenerNombreHito($notificacion->datos['hito_tipo']) ?? 'Etapa' }}</p>
                                @endif
                                @if(isset($notificacion->datos['folio']))
                                    <p><strong>Folio:</strong> {{ $notificacion->datos['folio'] }}</p>
                                @endif
                            </div>
                        @endif

                        <!-- Fecha y acciones -->
                        <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-200 border-opacity-50">
                            <span class="text-sm text-gray-500">
                                {{ $notificacion->created_at->format('d/m/Y H:i') }}
                            </span>
                            <div class="flex gap-2">
                                @if($notificacion->accion_url)
                                    <a 
                                        href="{{ $notificacion->accion_url }}"
                                        class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition"
                                    >
                                        Ver Solicitud
                                    </a>
                                @endif
                                @if(!$notificacion->leida)
                                    <button 
                                        onclick="marcarLeida({{ $notificacion->id }})"
                                        class="text-sm px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition"
                                    >
                                        Marcar como leída
                                    </button>
                                @endif
                                <button 
                                    onclick="eliminarNotificacion({{ $notificacion->id }})"
                                    class="text-sm px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-gray-50 rounded-lg">
                <p class="text-gray-500 text-lg mb-2">📭 No tienes notificaciones</p>
                <p class="text-gray-400">Las notificaciones sobre tus solicitudes y documentos aparecerán aquí</p>
            </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($notificaciones->hasPages())
        <div class="mt-8">
            {{ $notificaciones->links() }}
        </div>
    @endif
</div>

<style>
    .filter-tab {
        background-color: #f3f4f6;
        color: #6b7280;
    }
    
    .filter-tab.active {
        background-color: #3b82f6;
        color: white;
    }
    
    .filter-tab:hover {
        background-color: #e5e7eb;
    }
    
    .filter-tab.active:hover {
        background-color: #2563eb;
    }
    
    .notification-item[data-tipo="documento_rechazado"] {
        border-left-color: #ef4444;
        background-color: #fef2f2;
    }
    
    .notification-item[data-tipo="hito_cambio"] {
        border-left-color: #10b981;
        background-color: #f0fdf4;
    }
    
    .notification-item[data-tipo="solicitud_rechazada"] {
        border-left-color: #f59e0b;
        background-color: #fffbeb;
    }
</style>

<script>
function marcarLeida(id) {
    fetch(`/api/notificaciones/${id}/marcar-leida`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function marcarTodasLeidas() {
    fetch(`/api/notificaciones/marcar-todas-leidas`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function eliminarNotificacion(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta notificación?')) {
        fetch(`/api/notificaciones/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success === false) {
                location.reload();
            }
        });
    }
}

function filtrarNotificaciones(tipo) {
    const items = document.querySelectorAll('.notification-item');
    const tabs = document.querySelectorAll('.filter-tab');
    
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    items.forEach(item => {
        if (tipo === 'todas' || item.dataset.tipo === tipo) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Marcar primer filtro como activo
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[data-filter="todas"]').classList.add('active');
});
</script>
@endsection
