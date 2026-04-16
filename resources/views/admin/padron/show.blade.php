<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Detalles del Usuario - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalles del Usuario</h2>
            </div>
        </header>

        <main>
            <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Detalles del Usuario</h1>
                    <p class="mt-2 text-sm text-gray-600">Información completa y auditoría</p>
                </div>
                <a href="{{ route('admin.padron.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Volver
                </a>
            </div>

            <!-- Información Principal -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h2 class="text-xl font-semibold text-gray-900">Información General</h2>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Foto y Estado -->
                        <div class="flex flex-col items-center">
                            <div class="mb-4">
                                <x-avatar-image :usuario="$usuario" size="lg" />
                            </div>

                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-gray-900">
                                    @if($tipo === 'Beneficiario')
                                        {{ $beneficiario->nombre }} {{ $beneficiario->apellido_paterno }}
                                    @else
                                        {{ $personal->nombre }} {{ $personal->apellido_paterno }}
                                    @endif
                                </h3>
                                <p class="text-gray-600 mt-1">
                                    @if($tipo === 'Beneficiario')
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">Beneficiario</span>
                                    @else
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 text-purple-800">{{ ucfirst($usuario->tipo_usuario) }}</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    @if($usuario->activo)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            <span class="inline-block h-2 w-2 bg-green-500 rounded-full mr-2"></span>Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                            <span class="inline-block h-2 w-2 bg-red-500 rounded-full mr-2"></span>Inactivo
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Información de Contacto</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase">Email</label>
                                    <p class="text-gray-900">{{ $usuario->email }}</p>
                                </div>

                                @if($tipo === 'Beneficiario')
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 uppercase">Teléfono</label>
                                        <p class="text-gray-900">{{ $beneficiario->telefono ?? 'No registrado' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 uppercase">Género</label>
                                        <p class="text-gray-900">{{ ucfirst($beneficiario->genero) }}</p>
                                    </div>
                                @else
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 uppercase">Puesto</label>
                                        <p class="text-gray-900">{{ $personal->puesto ?? 'No asignado' }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Datos Específicos -->
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Datos Específicos</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($tipo === 'Beneficiario')
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">CURP</label>
                                    <input type="text" value="{{ $beneficiario->curp }}" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Fecha de Nacimiento</label>
                                    <input type="text" value="{{ $beneficiario->fecha_nacimiento->format('d/m/Y') }}" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Fecha de Registro</label>
                                    <input type="text" value="{{ optional($beneficiario->fecha_registro)->format('d/m/Y') }}" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Privacidad Aceptada</label>
                                    <input type="text" value="{{ $beneficiario->acepta_privacidad ? 'Sí' : 'No' }}" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900">
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Número de Empleado</label>
                                    <input type="text" value="{{ $personal->numero_empleado }}" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-2">Rol</label>
                                    <input type="text" value="{{ $personal->role?->nombre_rol ?? 'No asignado' }}" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auditoría -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h2 class="text-xl font-semibold text-gray-900">Registro de Auditoría</h2>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Fecha de Creación</label>
                            <p class="text-gray-900 font-semibold">
                                {{ optional($usuario->fecha_creacion)->format('d/m/Y H:i') ?? 'No disponible' }}
                            </p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-500">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Última Conexión</label>
                            <p class="text-gray-900 font-semibold">
                                {{ optional($usuario->ultima_conexion)->format('d/m/Y H:i') ?? 'Nunca' }}
                            </p>
                        </div>
                        <div class="bg-indigo-50 rounded-lg p-4 border-l-4 border-indigo-500">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cambio de Contraseña</label>
                            <p class="text-gray-900 font-semibold">
                                {{ $usuario->debe_cambiar_password ? 'Pendiente' : 'Completado' }}
                            </p>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Acciones</h4>
                        <div class="flex gap-3 flex-wrap">
                            <button class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                </svg>
                                Editar
                            </button>
                            @if($usuario->activo)
                                <button class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Desactivar
                                </button>
                            @else
                                <button class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    Reactivar
                                </button>
                            @endif
                            <button class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Historial de Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Histórico de Apoyos (solo para beneficiarios) -->
            @if($tipo === 'Beneficiario')
            <div class="bg-white rounded-lg shadow mt-8">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000-2H6a6 6 0 016 6v3h3a1 1 0 01.82.4l2.601 3.42a1 1 0 01-.454 1.659.5.5 0 00-.171.95l2.16 1.08a1 1 0 11-.896 1.79L15.75 16.07a.5.5 0 00-.171.95.5.5 0 01-.454 1.659l-2.601-3.42A1 1 0 0112 15h-3v2a1 1 0 11-2 0v-2H6a2 2 0 01-2-2V5zm12-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-gray-900">Histórico de Apoyos Solicitados</h2>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                            {{ $beneficiario->solicitudes->count() }} solicitud{{ $beneficiario->solicitudes->count() !== 1 ? 'es' : '' }}
                        </span>
                    </div>
                </div>
                <div class="px-6 py-6">
                    @if($beneficiario->solicitudes->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Folio</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Apoyo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha Solicitud</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Monto Entregado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Notas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($beneficiario->solicitudes()->orderBy('fecha_creacion', 'desc')->get() as $solicitud)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                                #{{ $solicitud->folio }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $solicitud->apoyo->nombre_apoyo ?? 'Apoyo no disponible' }}</div>
                                            <div class="text-xs text-gray-500">{{ $solicitud->apoyo->tipo_apoyo ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $estadoColor = match((int)$solicitud->fk_id_estado) {
                                                    1 => 'gray',      // Pendiente
                                                    2 => 'blue',      // Validado
                                                    3 => 'yellow',    // En Subsanación
                                                    4 => 'green',     // Aprobado
                                                    5 => 'red',       // Rechazado
                                                    default => 'gray',
                                                };
                                                $estadoTexto = match((int)$solicitud->fk_id_estado) {
                                                    1 => 'Pendiente',
                                                    2 => 'Validado',
                                                    3 => 'En Subsanación',
                                                    4 => 'Aprobado',
                                                    5 => 'Rechazado',
                                                    default => 'Desconocido',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-{{ $estadoColor }}-100 text-{{ $estadoColor }}-800">
                                                @if($solicitud->fk_id_estado == 4)
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($solicitud->fk_id_estado == 5)
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($solicitud->fk_id_estado == 4)
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                                {{ $estadoTexto }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $solicitud->fecha_creacion->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($solicitud->monto_entregado)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                    $ {{ number_format($solicitud->monto_entregado, 2, '.', ',') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            @if($solicitud->observaciones_internas)
                                                <div class="max-w-xs truncate hover:text-clip" title="{{ $solicitud->observaciones_internas }}">
                                                    {{ $solicitud->observaciones_internas }}
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Estadísticas de Apoyos -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4 pt-8 border-t border-gray-200">
                            @php
                                $totalSolicitudes = $beneficiario->solicitudes->count();
                                $aprobadas = $beneficiario->solicitudes->where('fk_id_estado', 4)->count();
                                $rechazadas = $beneficiario->solicitudes->where('fk_id_estado', 5)->count();
                                $pendientes = $beneficiario->solicitudes->whereIn('fk_id_estado', [1, 2, 3, 8, 9])->count();
                                $montoTotal = $beneficiario->solicitudes->sum('monto_entregado');
                            @endphp

                            <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-500">
                                <p class="text-gray-600 text-sm font-medium">Aprobadas</p>
                                <p class="text-2xl font-bold text-green-700">{{ $aprobadas }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ round(($aprobadas/$totalSolicitudes)*100) }}% del total</p>
                            </div>

                            <div class="bg-red-50 rounded-lg p-4 border-l-4 border-red-500">
                                <p class="text-gray-600 text-sm font-medium">Rechazadas</p>
                                <p class="text-2xl font-bold text-red-700">{{ $rechazadas }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ round(($rechazadas/$totalSolicitudes)*100) }}% del total</p>
                            </div>

                            <div class="bg-yellow-50 rounded-lg p-4 border-l-4 border-yellow-500">
                                <p class="text-gray-600 text-sm font-medium">Pendientes</p>
                                <p class="text-2xl font-bold text-yellow-700">{{ $pendientes }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ round(($pendientes/$totalSolicitudes)*100) }}% del total</p>
                            </div>

                            <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
                                <p class="text-gray-600 text-sm font-medium">Monto Entregado</p>
                                <p class="text-2xl font-bold text-blue-700">$ {{ number_format($montoTotal, 2, '.', ',') }}</p>
                                <p class="text-xs text-gray-500 mt-1">Total de recursos</p>
                            </div>
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500 font-medium">No hay solicitudes de apoyo registradas</p>
                            <p class="text-sm text-gray-400 mt-2">Este beneficiario aún no ha solicitado ningún apoyo</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </main>
    </div>
</body>
</html>
